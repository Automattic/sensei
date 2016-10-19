<?php
/**
 * WordPress Coding Standard.
 *
 * @package WPCS\WordPressCodingStandards
 * @link    https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

/**
 * Flag any non-validated/sanitized input ( _GET / _POST / etc. ).
 *
 * @link    https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/69
 *
 * @package WPCS\WordPressCodingStandards
 *
 * @since   0.3.0
 * @since   0.4.0 This class now extends WordPress_Sniff.
 * @since   0.5.0 Method getArrayIndexKey() has been moved to WordPress_Sniff.
 */
class WordPress_Sniffs_VIP_ValidatedSanitizedInputSniff extends WordPress_Sniff {

	/**
	 * Check for validation functions for a variable within its own parenthesis only.
	 *
	 * @var boolean
	 */
	public $check_validation_in_scope_only = false;

	/**
	 * Custom list of functions that sanitize the values passed to them.
	 *
	 * @since 0.5.0
	 *
	 * @var string[]
	 */
	public $customSanitizingFunctions = array();

	/**
	 * Custom sanitizing functions that implicitly unslash the values passed to them.
	 *
	 * @since 0.5.0
	 *
	 * @var string[]
	 */
	public $customUnslashingSanitizingFunctions = array();

	/**
	 * Whether the custom list of functions has been added to the defaults yet.
	 *
	 * @since 0.5.0
	 *
	 * @var bool
	 */
	protected static $addedCustomFunctions = false;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_VARIABLE,
			T_DOUBLE_QUOTED_STRING,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token
	 *                                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {

		// Merge any custom functions with the defaults, if we haven't already.
		if ( ! self::$addedCustomFunctions ) {

			self::$sanitizingFunctions = array_merge(
				self::$sanitizingFunctions,
				array_flip( $this->customSanitizingFunctions )
			);

			self::$unslashingSanitizingFunctions = array_merge(
				self::$unslashingSanitizingFunctions,
				array_flip( $this->customUnslashingSanitizingFunctions )
			);

			self::$addedCustomFunctions = true;
		}

		$this->init( $phpcsFile );
		$superglobals = self::$input_superglobals;

		// Handling string interpolation.
		if ( T_DOUBLE_QUOTED_STRING === $this->tokens[ $stackPtr ]['code'] ) {
			$interpolated_variables = array_map(
				create_function( '$symbol', 'return "$" . $symbol;' ), // Replace with closure when 5.3 is minimum requirement for PHPCS.
				$this->get_interpolated_variables( $this->tokens[ $stackPtr ]['content'] )
			);
			foreach ( array_intersect( $interpolated_variables, $superglobals ) as $bad_variable ) {
				$phpcsFile->addError( 'Detected usage of a non-sanitized, non-validated input variable %s: %s', $stackPtr, 'InputNotValidatedNotSanitized', array( $bad_variable, $this->tokens[ $stackPtr ]['content'] ) );
			}

			return;
		}

		// Check if this is a superglobal.
		if ( ! in_array( $this->tokens[ $stackPtr ]['content'], $superglobals, true ) ) {
			return;
		}

		// If we're overriding a superglobal with an assignment, no need to test.
		if ( $this->is_assignment( $stackPtr ) ) {
			return;
		}

		// This superglobal is being validated.
		if ( $this->is_in_isset_or_empty( $stackPtr ) ) {
			return;
		}

		$array_key = $this->get_array_access_key( $stackPtr );

		if ( empty( $array_key ) ) {
			return;
		}

		$error_data = array( $this->tokens[ $stackPtr ]['content'] );

		// Check for validation first.
		if ( ! $this->is_validated( $stackPtr, $array_key, $this->check_validation_in_scope_only ) ) {
			$phpcsFile->addError( 'Detected usage of a non-validated input variable: %s', $stackPtr, 'InputNotValidated', $error_data );
			// return; // Should we just return and not look for sanitizing functions ?
		}

		if ( $this->has_whitelist_comment( 'sanitization', $stackPtr ) ) {
			return;
		}

		// If this is a comparison ('a' == $_POST['foo']), sanitization isn't needed.
		if ( $this->is_comparison( $stackPtr ) ) {
			return;
		}

		// Now look for sanitizing functions.
		if ( ! $this->is_sanitized( $stackPtr, true ) ) {
			$phpcsFile->addError( 'Detected usage of a non-sanitized input variable: %s', $stackPtr, 'InputNotSanitized', $error_data );
		}

	} // end process()

} // End class.

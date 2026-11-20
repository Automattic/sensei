<?php
/**
 * Sensei_Validate_Progress_Command class file.
 *
 * @package sensei
 */

use Sensei\Internal\Migration\Validations\Progress_Validation;
use Sensei\Internal\Migration\Validations\Validation_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP-CLI command that validates the progress data.
 *
 * @since 4.19.1
 */
class Sensei_Validate_Progress_Command {
	/**
	 * Validate the progress migrated data.
	 *
	 * @since 4.19.1
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( array $args = [], array $assoc_args = [] ) {
		$progress_validation = new Progress_Validation();

		$progress_validation->run();
		$warnings = $progress_validation->get_warnings();
		$this->output_validation_messages( $warnings );

		if ( ! $progress_validation->has_errors() ) {
			WP_CLI::success( $warnings ? 'No other progress mismatches found.' : 'Progress data is valid.' );
			return;
		}

		$this->output_validation_messages( $progress_validation->get_errors() );

		WP_CLI::error( 'Progress data is not valid.' );
	}

	/**
	 * Output validation errors or non-failing warnings.
	 *
	 * @since 4.19.1
	 *
	 * @param Validation_Error[] $messages Validation messages.
	 */
	private function output_validation_messages( array $messages ) {
		foreach ( $messages as $message ) {
			WP_CLI::warning( $message->get_message() );

			if ( $message->has_data() ) {
				$message_data = $message->get_data();
				$message_data = isset( $message_data[0] ) && is_array( $message_data[0] ) ? $message_data : array( $message_data );

				WP_CLI\Utils\format_items(
					'table',
					$message_data,
					array_keys( $message_data[0] )
				);
			}
		}
	}
}

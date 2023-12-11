<?php
/**
 * File containing the Sensei_Validate_Quiz_Submission_Command class.
 *
 * @package sensei
 */

use Sensei\Internal\Migration\Validations\Quiz_Submission_Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP-CLI command that validates the quiz submission data.
 *
 * @since 4.19.2
 */
class Sensei_Validate_Quiz_Submission_Command {

	/**
	 * Validate the quiz submission migrated data.
	 *
	 * @since 4.19.2
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( array $args = [], array $assoc_args = [] ) {
		$quiz_submission_validation = new Quiz_Submission_Validation();
		$quiz_submission_validation->run();

		if ( ! $quiz_submission_validation->has_errors() ) {
			WP_CLI::success( 'Quiz submission data is valid.' );
			return;
		}

		$this->output_validation_errors( $quiz_submission_validation );

		WP_CLI::error( 'Quiz submission data is not valid.' );
	}

	/**
	 * Output the validation errors.
	 *
	 * @param Quiz_Submission_Validation $quiz_submission_validation Quiz submission validation.
	 */
	private function output_validation_errors( Quiz_Submission_Validation $quiz_submission_validation ) {
		foreach ( $quiz_submission_validation->get_errors() as $error ) {
			WP_CLI::warning( $error->get_message() );

			if ( $error->has_data() ) {
				$error_data = $error->get_data();
				$error_data = isset( $error_data[0] ) && is_array( $error_data[0] ) ? $error_data : [ $error_data ];

				WP_CLI\Utils\format_items(
					'table',
					$error_data,
					array_keys( $error_data[0] )
				);
			}
		}
	}
}

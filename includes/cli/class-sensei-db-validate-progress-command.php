<?php
/**
 * Sensei_DB_Validate_Progress_Command class file.
 *
 * @package sensei
 */

use Sensei\Internal\Migration\Validations\Progress_Validation;

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI command that validates the progress data.
 *
 * @since $$next-version$$
 */
class Sensei_DB_Validate_Progress_Command {
	/**
	 * Seed the database.
	 *
	 * @since $$next-version$$
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( array $args = [], array $assoc_args = [] ) {
		$progress_validation = new Progress_Validation();

		$progress_validation->run();

		if ( ! $progress_validation->has_errors() ) {
			WP_CLI::success( 'Progress data is valid.' );
			return;
		}

		$this->output_validation_errors( $progress_validation );

		WP_CLI::error( 'Progress data is not valid.' );
	}

	/**
	 * Output the validation errors.
	 *
	 * @since $$next-version$$
	 *
	 * @param Progress_Validation $progress_validation Progress validation.
	 */
	private function output_validation_errors( Progress_Validation $progress_validation ) {
		foreach ( $progress_validation->get_errors() as $error ) {
			WP_CLI::warning( $error->get_message() );

			if ( $error->has_data() ) {
				$error_data = $error->get_data();
				$error_data = is_array( $error_data[0] ) ? $error_data : [ $error_data ];

				WP_CLI\Utils\format_items(
					'table',
					$error_data,
					array_keys( $error_data[0] )
				);
			}
		}
	}
}

<?php
/**
 * File containing the Sensei_Import_Job_CLI class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data import job CLI command.
 */
class Sensei_Import_Job_CLI extends WP_CLI_Command {
	/**
	 * Import CSV files with courses, lessons, and questions into Sensei LMS.
	 *
	 * ## OPTIONS
	 *
	 * At least one of the following file parameters are required. It is also strongly recommended that you
	 * provide the global option `--user`.
	 *
	 * [--questions=<path>]
	 * : Path to the CSV file containing the questions.
	 *
	 * [--courses=<path>]
	 * : Path to the CSV file containing the courses.
	 *
	 * [--lessons=<path>]
	 * : Path to the CSV file containing the lessons and quizzes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sensei-import --user=admin --questions=questions.csv --courses=courses.csv --lessons=lessons.csv
	 *
	 * @when after_wp_load
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( $args = [], $assoc_args = [] ) {
		if ( ! get_current_user_id() ) {
			WP_CLI::confirm( __( 'No `--user` argument was provided. Do you want to create posts as a guest?', 'sensei-lms' ) );
		}

		$files = $this->collect_files( $assoc_args );

		$job = Sensei_Import_Job::create( uniqid(), get_current_user_id() );
		$this->set_files( $job, $files );
		$job->start();

		$progress  = \WP_CLI\Utils\make_progress_bar( 'Importing', 100 );
		$last_tick = 0;
		while ( ! $job->is_complete() ) {
			$job->run();
			$status    = $job->get_status();
			$diff_tick = $status['percentage'] - $last_tick;
			$progress->tick( $diff_tick );
			$last_tick += $diff_tick;
		}

		WP_CLI::log( PHP_EOL . PHP_EOL . 'Log Entries' );
		WP_CLI::log( str_repeat( '=', 100 ) );
		$logs = $job->get_logs();

		foreach ( $logs as $log_message ) {
			$message = $log_message['message'];
			if ( ! empty( $log_message['data'] ) ) {
				$message .= ' ' . wp_json_encode( $log_message['data'] );
			}

			switch ( $log_message['level'] ) {
				case Sensei_Data_Port_Job::LOG_LEVEL_INFO:
					WP_CLI::log( $message );
					break;
				case Sensei_Data_Port_Job::LOG_LEVEL_NOTICE:
					WP_CLI::warning( $message );
					break;
				case Sensei_Data_Port_Job::LOG_LEVEL_ERROR:
					WP_CLI::error( $message, false );
					break;
			}
		}

		$job->clean_up();
	}

	/**
	 * Collect the files from passed arguments.
	 *
	 * @param array $assoc_args Arguments passed to the command.
	 *
	 * @return array
	 */
	private function collect_files( $assoc_args ) {
		$files = [];

		$file_keys = [ 'questions', 'courses', 'lessons' ];
		foreach ( $file_keys as $file_key ) {
			if ( ! empty( $assoc_args[ $file_key ] ) ) {
				$file_path = getcwd() . DIRECTORY_SEPARATOR . $assoc_args[ $file_key ];

				if ( file_exists( $file_path ) && is_readable( $file_path ) ) {
					$files[ $file_key ] = $file_path;
				} else {
					WP_CLI::error(
						sprintf(
							// translators: Placeholder %1$s is the name of the file; %2$s is the path provided.
							__( 'File provided for "%1$s" (%1$s) was not found', 'sensei-lms' ),
							$file_key,
							$file_path
						)
					);

					wp_die();
				}
			}
		}

		if ( empty( $files ) ) {
			WP_CLI::error( __( 'No file arguments were provided.', 'sensei-lms' ) );

			wp_die();
		}

		return $files;
	}

	/**
	 * Add the files ot the job.
	 *
	 * @param Sensei_Import_Job $job   Job object.
	 * @param array             $files Files to set.
	 */
	private function set_files( Sensei_Import_Job $job, $files ) {
		foreach ( $files as $file_key => $file_path ) {
			$result = $job->save_file( $file_key, $file_path, basename( $file_path ) );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error(
					sprintf(
						// translators: Placeholder %1$s is the name of the file; %2$s is the path provided; %3$s is the validation error.
						__( 'File provided for "%1$s" (%2$s) was not not valid. Error: %3$s', 'sensei-lms' ),
						$file_key,
						$file_path,
						$result->get_error_message()
					)
				);

				wp_die();
			}
		}
	}
}

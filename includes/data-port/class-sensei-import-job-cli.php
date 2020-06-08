<?php
/**
 * File containing the Sensei_Import_Job class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a data import job.
 */
class Sensei_Import_Job_CLI {
	/**
	 * Run the import command.
	 *
	 * @param array $args       Arguments passed without a name.
	 * @param array $assoc_args Arguments passed with a name.
	 */
	public function __invoke( $args = [], $assoc_args = [] ) {
		$files = [];
		if ( ! empty( $assoc_args['questions'] ) ) {
			$question_csv_file = getcwd() . DIRECTORY_SEPARATOR . $assoc_args['questions'];
			if ( file_exists( $question_csv_file ) && is_readable( $question_csv_file ) ) {
				$files['questions'] = $question_csv_file;
			} else {
				WP_CLI::error( "Questions file {$question_csv_file} does not exist." );
				die();
			}
		}

		if ( ! empty( $assoc_args['courses'] ) ) {
			$courses_csv_file = getcwd() . DIRECTORY_SEPARATOR . $assoc_args['courses'];
			if ( file_exists( $courses_csv_file ) && is_readable( $courses_csv_file ) ) {
				$files['courses'] = $courses_csv_file;
			} else {
				WP_CLI::error( "Courses file {$courses_csv_file} does not exist." );
				die();
			}
		}

		if ( ! empty( $assoc_args['lessons'] ) ) {
			$lessons_csv_file = getcwd() . DIRECTORY_SEPARATOR . $assoc_args['lessons'];
			if ( file_exists( $courses_csv_file ) && is_readable( $lessons_csv_file ) ) {
				$files['lessons'] = $lessons_csv_file;
			} else {
				WP_CLI::error( "Lessons file {$lessons_csv_file} does not exist." );
				die();
			}
		}

		if ( empty( $files ) ) {
			WP_CLI::error( 'No file arguments were provided.' );
			die();
		}

		$job = new Sensei_Import_Job( uniqid() );
		foreach ( $files as $file_key => $file_path ) {
			$result = $job->save_file( $file_key, $file_path, basename( $file_path ) );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( "File for {$file_key} was not valid. Error: " . $result->get_error_message() );
				die();
			}
		}
		$job->start();

		$progress  = \WP_CLI\Utils\make_progress_bar( 'Importing day data', 100 );
		$last_tick = 0;
		while ( ! $job->is_complete() ) {
			$job->run();
			$status    = $job->get_status();
			$diff_tick = $status['percentage'] - $last_tick;
			$progress->tick( $diff_tick );
			$last_tick = $last_tick + $diff_tick;
		}

		$job->clean_up();
	}
}

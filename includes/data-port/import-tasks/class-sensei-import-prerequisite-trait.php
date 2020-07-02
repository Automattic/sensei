<?php
/**
 * File containing the Sensei_Import_Lessons class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This trait contains shared methods related to prerequisite handling.
 */
trait Sensei_Import_Prerequisite_Trait {
	/**
	 * Helper for handling the prerequisite post-process task.
	 *
	 * @param array  $task       Raw post-process task attribute array.
	 * @param string $meta_field Meta field that holds the pre-req.
	 * @param string $post_type  Post type that is being handled.
	 * @param string $model_key  Model key.
	 */
	private function handle_prerequisite_helper( $task, $meta_field, $post_type, $model_key ) {
		$post_id           = (int) $task[0];
		$reference         = sanitize_text_field( $task[1] );
		$line_number       = (int) $task[2];
		$reference_post_id = $this->get_job()->translate_import_id( $post_type, $reference );
		$error_data        = [
			'line'    => $line_number,
			'type'    => $model_key,
			'post_id' => $post_id,
		];

		if ( ! $reference_post_id ) {
			$this->get_job()->add_log_entry(
				// translators: Placeholder is reference to another post.
				sprintf( __( 'Unable to set the prerequisite to "%s"', 'sensei-lms' ), $reference ),
				Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				$error_data
			);

			return;
		}

		if ( (int) $reference_post_id === $post_id ) {
			$this->get_job()->add_log_entry(
				__( 'Unable to set the prerequisite to the same entry', 'sensei-lms' ),
				Sensei_Data_Port_Job::LOG_LEVEL_NOTICE,
				$error_data
			);

			return;
		}

		update_post_meta( $post_id, $meta_field, $reference_post_id );
	}

	/**
	 * Add prerequisite task for lesson.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $reference   Reference to the prerequisite.
	 * @param int    $line_number Line number.
	 */
	public function add_prerequisite_task( $post_id, $reference, $line_number ) {
		return $this->add_post_process_task(
			'prerequisite',
			[
				$post_id,
				$reference,
				$line_number,
			]
		);
	}
}

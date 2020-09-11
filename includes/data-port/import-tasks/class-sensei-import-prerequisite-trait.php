<?php
/**
 * File containing the Sensei_Import_Prerequisite_Trait trait.
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
		$post_title        = $task[3];

		$error_data = [
			'line'        => $line_number,
			'type'        => $model_key,
			'post_id'     => $post_id,
			'entry_title' => $post_title,
		];

		if ( ! $reference_post_id ) {
			$this->get_job()->add_line_warning(
				$model_key,
				$line_number,
				// translators: Placeholder is reference to another post.
				sprintf( __( 'Unable to set the prerequisite to "%s"', 'sensei-lms' ), $reference ),
				array_merge(
					$error_data,
					[
						'code' => 'sensei_data_port_prerequisite_bad_reference',
					]
				)
			);

			return;
		}

		if ( (int) $reference_post_id === $post_id ) {
			$this->get_job()->add_line_warning(
				$model_key,
				$line_number,
				__( 'Unable to set the prerequisite to the same entry', 'sensei-lms' ),
				array_merge(
					$error_data,
					[
						'code' => 'sensei_data_port_prerequisite_ref_match',
					]
				)
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
	 * @param string $post_title  Post title for logging.
	 */
	public function add_prerequisite_task( $post_id, $reference, $line_number, $post_title ) {
		$this->add_post_process_task(
			'prerequisite',
			[
				$post_id,
				$reference,
				$line_number,
				$post_title,
			]
		);
	}
}

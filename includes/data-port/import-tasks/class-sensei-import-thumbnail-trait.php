<?php
/**
 * File containing the Sensei_Import_Thumbnail_Trait trait.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This trait contains shared methods for handling featured image downloads as post-process tasks.
 *
 * Downloading a remote image is a slow, network-bound operation. Running it inline while a line is
 * processed means a batch of lines can exceed `max_execution_time` mid-loop, fatal, and be replayed
 * from the start indefinitely. Deferring the download to a post-process task keeps line processing
 * network-free and lets the throttled, time-bounded post-process batch own the slow work.
 */
trait Sensei_Import_Thumbnail_Trait {
	/**
	 * Queue a featured image download as a post-process task.
	 *
	 * @param int    $post_id     The post to set the thumbnail on.
	 * @param string $source      The image source (URL or media library filename).
	 * @param array  $mime_types  Allowed mime types for the attachment.
	 * @param int    $line_number Line number, for logging.
	 * @param string $model_key   Model key, for logging.
	 */
	public function add_thumbnail_task( $post_id, $source, $mime_types, $line_number, $model_key ) {
		$this->add_post_process_task(
			'thumbnail',
			array(
				$post_id,
				$source,
				$mime_types,
				$line_number,
				$model_key,
			)
		);
	}

	/**
	 * Handle a queued featured image download.
	 *
	 * A failed or unreachable image is recorded as a per-line warning and the post keeps its
	 * existing (or empty) thumbnail — it never fails the import.
	 *
	 * @param array $task Raw post-process task attribute array.
	 */
	protected function handle_thumbnail( $task ) {
		$post_id     = (int) $task[0];
		$source      = $task[1];
		$mime_types  = $task[2];
		$line_number = (int) $task[3];
		$model_key   = $task[4];

		$attachment_id = Sensei_Data_Port_Utilities::get_attachment_from_source( $source, 0, $mime_types );

		if ( is_wp_error( $attachment_id ) ) {
			$this->get_job()->add_line_warning(
				$model_key,
				$line_number,
				$attachment_id->get_error_message(),
				array(
					'line'        => $line_number,
					'type'        => $model_key,
					'post_id'     => $post_id,
					'entry_title' => get_the_title( $post_id ),
					'code'        => $attachment_id->get_error_code(),
				)
			);

			return;
		}

		update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
	}
}

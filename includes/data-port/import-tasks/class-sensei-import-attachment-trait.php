<?php
/**
 * File containing the Sensei_Import_Attachment_Trait trait.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This trait contains shared methods for downloading a post's attachment as a post-process task.
 *
 * Downloading a remote file is a slow, network-bound operation. Running it inline while a line is
 * processed means a batch of lines can exceed `max_execution_time` mid-loop, fatal, and be replayed
 * from the start indefinitely. Deferring the download to a post-process task keeps line processing
 * network-free and lets the throttled, time-bounded post-process batch own the slow work.
 *
 * The resolved attachment id is stored in a caller-supplied meta key, so the same mechanism serves
 * both featured images (`_thumbnail_id` on lessons/courses) and question media (`_question_media`).
 */
trait Sensei_Import_Attachment_Trait {
	/**
	 * Queue an attachment download as a post-process task.
	 *
	 * @param array $args {
	 *     Task arguments.
	 *
	 *     @type int    $post_id     The post to set the resolved attachment on.
	 *     @type string $source      The attachment source (URL or media library filename).
	 *     @type array  $mime_types  Allowed mime types for the attachment.
	 *     @type int    $line_number Line number, for logging.
	 *     @type string $model_key   Model key, for logging.
	 *     @type string $meta_key    Optional. Meta key to store the resolved attachment id in. Default '_thumbnail_id'.
	 *     @type int    $parent_id   Optional. Attachment parent post id. Default 0 (unattached).
	 * }
	 */
	public function add_attachment_task( array $args ) {
		$this->add_post_process_task( 'attachment', $args );
	}

	/**
	 * Handle a queued attachment download.
	 *
	 * A failed or unreachable attachment is recorded as a per-line warning and the post keeps its
	 * existing (or empty) value — it never fails the import.
	 *
	 * @param array $task Task arguments as passed to add_attachment_task().
	 */
	protected function handle_attachment( $task ) {
		$post_id     = (int) $task['post_id'];
		$source      = $task['source'];
		$mime_types  = $task['mime_types'];
		$line_number = (int) $task['line_number'];
		$model_key   = $task['model_key'];
		$meta_key    = isset( $task['meta_key'] ) ? $task['meta_key'] : '_thumbnail_id';
		$parent_id   = isset( $task['parent_id'] ) ? (int) $task['parent_id'] : 0;

		$attachment_id = Sensei_Data_Port_Utilities::get_attachment_from_source( $source, $parent_id, $mime_types );

		if ( $attachment_id instanceof WP_Error ) {
			$job = $this->get_job();

			if ( $job instanceof Sensei_Import_Job ) {
				$job->add_line_warning(
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
			}

			return;
		}

		update_post_meta( $post_id, $meta_key, $attachment_id );
	}
}

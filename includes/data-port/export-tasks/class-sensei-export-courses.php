<?php
/**
 * File containing the Sensei_Import_File_Process_Task class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export content to a CSV file for the given type.
 */
class Sensei_Export_Courses
	extends Sensei_Export_Task {

	/**
	 * Content type of the task.
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'course';
	}

	/**
	 * Collect exported fields of the course.
	 *
	 * @param WP_Post $post The course.
	 *
	 * @return string[]
	 */
	protected function get_post_fields( $post ) {

		$teacher       = get_user_by( 'id', $post->post_author );
		$prerequisite  = get_post_meta( $post->ID, '_course_prerequisite', true );
		$featured      = get_post_meta( $post->ID, '_course_featured', true );
		$image         = get_the_post_thumbnail_url( $post );
		$video         = get_post_meta( $post->ID, '_course_video_embed', true );
		$notifications = ! get_post_meta( $post->ID, 'disable_notification', true );
		$categories    = get_the_terms( $post->ID, 'course-category' );
		$modules       = Sensei()->modules->get_course_modules( $post->ID );

		return [
			$post->ID,
			$post->post_title,
			$post->post_name,
			$post->post_content,
			$post->post_excerpt,
			$teacher ? $teacher->display_name : '',
			$teacher ? $teacher->user_email : '',
			Sensei_Data_Port_Utilities::serialize_term_list( $modules ),
			$prerequisite ? $prerequisite : '',
			$featured ? 1 : 0,
			$categories ? Sensei_Data_Port_Utilities::serialize_term_list( $categories ) : '',
			$image,
			$video,
			$notifications ? 1 : 0,
		];
	}

	/**
	 * Schema for the content type.
	 *
	 * @return Sensei_Data_Port_Schema
	 */
	protected function get_type_schema() {
		return new Sensei_Data_Port_Course_Schema();
	}

}

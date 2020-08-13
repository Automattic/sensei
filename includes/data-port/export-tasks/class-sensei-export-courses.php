<?php
/**
 * File containing the Sensei_Export_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei_Data_Port_Course_Schema as Schema;

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
	 * @return array The columns data per key.
	 */
	protected function get_post_fields( $post ) {

		$teacher       = get_user_by( 'id', $post->post_author );
		$prerequisite  = get_post_meta( $post->ID, '_course_prerequisite', true );
		$featured      = get_post_meta( $post->ID, '_course_featured', true );
		$image         = get_the_post_thumbnail_url( $post, 'full' );
		$video         = get_post_meta( $post->ID, '_course_video_embed', true );
		$notifications = ! get_post_meta( $post->ID, 'disable_notification', true );
		$categories    = get_the_terms( $post->ID, 'course-category' );
		$modules       = Sensei()->modules->get_course_modules( $post->ID );

		return [
			Schema::COLUMN_ID               => $post->ID,
			Schema::COLUMN_TITLE            => $post->post_title,
			Schema::COLUMN_SLUG             => $post->post_name,
			Schema::COLUMN_DESCRIPTION      => $post->post_content,
			Schema::COLUMN_EXCERPT          => $post->post_excerpt,
			Schema::COLUMN_TEACHER_USERNAME => $teacher ? $teacher->display_name : '',
			Schema::COLUMN_TEACHER_EMAIL    => $teacher ? $teacher->user_email : '',
			Schema::COLUMN_MODULES          => Sensei_Data_Port_Utilities::serialize_term_list( $modules ),
			Schema::COLUMN_PREREQUISITE     => $prerequisite ? Sensei_Data_Port_Utilities::serialize_id_field( $prerequisite ) : '',
			Schema::COLUMN_FEATURED         => $featured ? 1 : 0,
			Schema::COLUMN_CATEGORIES       => $categories ? Sensei_Data_Port_Utilities::serialize_term_list( $categories ) : '',
			Schema::COLUMN_IMAGE            => $image,
			Schema::COLUMN_VIDEO            => $video,
			Schema::COLUMN_NOTIFICATIONS    => $notifications ? 1 : 0,
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

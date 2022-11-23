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
		$notifications = get_post_meta( $post->ID, 'disable_notification', true );
		$categories    = get_the_terms( $post->ID, 'course-category' );
		$modules       = Sensei()->modules->get_course_modules( $post->ID );
		$lessons       = $this->get_ordered_course_lessons( $post->ID );

		return [
			Schema::COLUMN_ID               => $post->ID,
			Schema::COLUMN_TITLE            => $post->post_title,
			Schema::COLUMN_SLUG             => $post->post_name,
			Schema::COLUMN_DESCRIPTION      => $post->post_content,
			Schema::COLUMN_EXCERPT          => $post->post_excerpt,
			Schema::COLUMN_TEACHER_USERNAME => $teacher ? $teacher->display_name : '',
			Schema::COLUMN_TEACHER_EMAIL    => $teacher ? $teacher->user_email : '',
			Schema::COLUMN_LESSONS          => Sensei_Data_Port_Utilities::serialize_id_field( $lessons ),
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
	 * Returns an ordered list of a course's lessons. The lessons can be ordered either individually or depend on the
	 * order of the modules they belong to.
	 *
	 * @param int $course_id The course.
	 *
	 * @return array An ordered list of lessons ids.
	 */
	private function get_ordered_course_lessons( $course_id ) {
		$all_lessons       = Sensei()->course->course_lessons( $course_id, 'any', 'ids' );
		$no_module_lessons = wp_list_pluck( Sensei()->modules->get_none_module_lessons( $course_id, 'any' ), 'ID' );

		if ( count( $all_lessons ) === count( $no_module_lessons ) ) {
			return $all_lessons;
		}

		$course_modules = Sensei()->modules->get_course_modules( $course_id );
		$terms          = wp_list_pluck( $course_modules, 'term_id' );

		$ordered_lessons = [];
		foreach ( $terms as $term ) {
			$args = array(
				'post_type'      => 'lesson',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'post__in'       => $all_lessons,
				'fields'         => 'ids',
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- We only return the lessons which belong to a course and module.
					[
						'taxonomy' => 'module',
						'terms'    => $term,
					],
				],
				'meta_key'       => '_order_module_' . $term, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- See above.
				'orderby'        => 'meta_value_num date',
				'order'          => 'ASC',
			);

			$lessons_query   = new WP_Query( $args );
			$ordered_lessons = array_merge( $ordered_lessons, $lessons_query->get_posts() );
		}

		// We cannot use $no_module_lessons directly since it's unordered. Instead we intersect it with $all_lessons which is ordered.
		$ordered_lessons = array_merge( $ordered_lessons, array_intersect( $all_lessons, $no_module_lessons ) );

		return $ordered_lessons;
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

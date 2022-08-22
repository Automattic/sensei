<?php
/**
 * File containing the Sensei_Continue_Course_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Continue_Course_Block
 */
class Sensei_Continue_Course_Block {

	/**
	 * Sensei_Continue_Course_Block constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-continue-course',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the `sensei-lms/button-continue-course` block on the server.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block default content.
	 *
	 * @access private
	 *
	 * @return string Returns a Continue button that links to the course page.
	 */
	public function render( array $attributes, string $content ) : string {
		$course_id = get_the_ID();
		$user_id   = get_current_user_id();

		/**
		 * Whether to render the Continue Course block.
		 *
		 * @since x.x.x
		 *
		 * @param {boolean} $render     Whether to render the Continue Course block.
		 * @param {array}   $attributes Block attributes.
		 * @param {string}  $content    Block content.
		 *
		 * @return {boolean} Whether to render the Continue Course block.
		 */
		$render = apply_filters(
			'sensei_render_continue_course_block',
			Sensei()->course::is_user_enrolled( $course_id, $user_id ) && ! Sensei_Utils::user_completed_course( $course_id, $user_id ),
			$attributes,
			$content
		);

		if ( ! $render ) {
			return '';
		}

		$target_post_id = $this->get_target_page_post_id_for_continue_url( $course_id, $user_id );

		return preg_replace(
			'/<a(.*)>/',
			'<a href="' . esc_url( get_permalink( absint( $target_post_id ?? $course_id ) ) ) . '" $1>',
			$content,
			1
		);
	}

	/**
	 * Gets the id for the last lesson the user was working on, or the next lesson, or
	 * the course id as fallback for fresh users or courses with no lessons.
	 *
	 * @access private
	 *
	 * @param int $course_id Id of the course.
	 * @param int $user_id   Id of the user.
	 *
	 * @return int
	 */
	private function get_target_page_post_id_for_continue_url( $course_id, $user_id ) {
		$course_lessons = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );

		if ( empty( $course_lessons ) ) {
			return $course_id;
		}
		// First try to get the lesson the user started or updated last.
		$activity_args = [
			'post__in' => $course_lessons,
			'user_id'  => $user_id,
			'type'     => 'sensei_lesson_status',
			'number'   => 1,
			'orderby'  => 'comment_date',
			'order'    => 'DESC',
			'status'   => [ 'in-progress', 'ungraded' ],
		];

		$last_lesson_activity = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		if ( ! empty( $last_lesson_activity ) ) {
			return $last_lesson_activity->comment_post_ID;
		} else {
			// If there is no such lesson, get the first lesson that the user has not yet started.
			$completed_lessons     = Sensei()->course->get_completed_lesson_ids( $course_id, $user_id );
			$not_completed_lessons = array_diff( $course_lessons, $completed_lessons );
			if ( count( $course_lessons ) !== count( $not_completed_lessons ) && ! empty( $not_completed_lessons ) ) {
				return $not_completed_lessons[0];
			}
		}
		return $course_id;
	}
}

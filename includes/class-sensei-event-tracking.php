<?php
/**
 * Usage Tracking subclass for Sensei.
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Event Tracking class.
 **/
class Sensei_Event_Tracking {

	/**
	 * Initialize event tracking hooks.
	 *
	 * @since 2.1.0
	 */
	public static function init() {
		add_action( 'transition_post_status', [ __CLASS__, 'track_course_published' ], 10, 3 );
	}

	/**
	 * Track an event when a course is published.
	 *
	 * @access private
	 * @since 2.1.0
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $course     The Post.
	 */
	public static function track_course_published( $new_status, $old_status, $course ) {
		// Only track for courses being published.
		$publishing = ( $old_status !== $new_status && 'publish' === $new_status );
		if ( ! $publishing || 'course' !== $course->post_type ) {
			return;
		}

		Sensei_Usage_Tracking::get_instance()->send_event(
			'course_publish',
			[
				'course_id'   => $course->ID,
				'course_name' => $course->post_title,
			]
		);
	}

}

<?php
/**
 * File containing the class Sensei_Course_Pre_Publish_Panel.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles the pre-publish panel for courses.
 *
 * @since 4.22.0
 */
class Sensei_Course_Pre_Publish_Panel {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Course_Pre_Publish_Panel constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_pre_publish_panel_assets' ) );
		add_action( 'publish_course', array( $this, 'maybe_publish_lessons' ) );
	}

	/**
	 * Enqueue pre-publish panel assets.
	 */
	public function enqueue_pre_publish_panel_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-course-pre-publish-panel-script', 'admin/course-pre-publish-panel/index.js' );
	}

	/**
	 * Maybe publish associated lessons when the course is published.
	 *
	 * @internal
	 *
	 * @param int $course_id Course ID.
	 */
	public function maybe_publish_lessons( $course_id ) {
		if ( ! current_user_can( 'publish_post', $course_id ) ) {
			return;
		}

		$publish_lessons = get_post_meta( $course_id, 'sensei_course_publish_lessons', true );

		if ( ! $publish_lessons ) {
			return;
		}

		// Publish all draft lessons for this course.
		$lesson_ids = Sensei()->course->course_lessons( $course_id, 'draft', 'ids' );

		foreach ( $lesson_ids as $lesson_id ) {
			wp_update_post(
				array(
					'ID'          => (int) $lesson_id,
					'post_status' => 'publish',
				)
			);
		}
	}
}

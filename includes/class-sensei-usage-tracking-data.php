<?php
/**
 * Usage tracking data
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies the usage tracking data for logging.
 *
 * @package Usage Tracking
 * @since 1.9.20
 */
class Sensei_Usage_Tracking_Data {
	/**
	 * Get the usage tracking data to send.
	 *
	 * @since 1.9.20
	 *
	 * @return array Usage data.
	 **/
	public static function get_usage_data() {
		return array(
			'courses' => wp_count_posts( 'course' )->publish,
			'courses_with_video' => self::get_courses_with_video_count(),
			'courses_with_disabled_notification' => self::get_courses_with_disabled_notification_count(),
			'learners' => self::get_learner_count(),
			'lessons' => wp_count_posts( 'lesson' )->publish,
			'messages' => wp_count_posts( 'sensei_message' )->publish,
			'modules' => wp_count_terms( 'module' ),
			'modules_max' => self::get_max_module_count(),
			'modules_min' => self::get_min_module_count(),
			'questions' => wp_count_posts( 'question' )->publish,
			'teachers' => self::get_teacher_count(),
		);
	}

	/**
	 * Get the number of courses that have a video set.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_courses_with_video_count() {
		$query = new WP_Query( array(
			'post_type' => 'course',
			'meta_query' => array(
				array(
					'key' => '_course_video_embed',
					'value' => '',
					'compare' => '!=',
				)
			)
		) );

		return $query->post_count;
	}

	/**
	 * Get the number of courses that have disabled notifications.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_courses_with_disabled_notification_count() {
		$query = new WP_Query( array(
			'post_type' => 'course',
			'meta_query' => array(
				array(
					'key' => 'disable_notification',
					'value' => true,
				)
			),
		) );

		return $query->post_count;
	}

	/**
	 * Get the number of teachers.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of teachers.
	 **/
	private static function get_teacher_count() {
		$teacher_query = new WP_User_Query( array( 'role' => 'teacher' ) );

		return $teacher_query->total_users;
	}

	/**
	 * Get the total number of learners enrolled in at least one course.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of learners.
	 **/
	private static function get_learner_count() {
		$learner_count = 0;
		$args['fields'] = array( 'ID' );
		$user_query = new WP_User_Query( $args );
		$learners = $user_query->get_results();

		foreach( $learners as $learner ) {
			$course_args = array(
				'user_id' => $learner->ID,
				'type' => 'sensei_course_status',
				'status' => 'any',
			);

			$course_count = Sensei_Utils::sensei_check_for_activity( $course_args );

			if ( $course_count > 0 ) {
				$learner_count++;
			}
		}

		return $learner_count;
	}

	/**
	 * Get the total number of modules for the published course that has the greatest
	 * number of modules.
	 *
	 * @since 1.9.20
	 *
	 * @return int Maximum modules count.
	 **/
	private static function get_max_module_count() {
		$max_modules = 0;
		$courses = get_posts( array(
			'post_type' => 'course',
			'fields' => 'ids',
		) );

		foreach( $courses as $course ) {
			// Get modules for this course.
			$module_count = wp_count_terms( 'module', array(
				'object_ids' => $course,
			) );

			if ( $max_modules < $module_count ) {
				$max_modules = $module_count;
			}
		}

		return $max_modules;
	}

	/**
	 * Get the total number of modules for the published course that has the fewest
	 * number of modules.
	 *
	 * @since 1.9.20
	 *
	 * @return int Minimum modules count.
	 **/
	private static function get_min_module_count() {
		$min_modules = 0;

		$courses = get_posts( array(
			'post_type' => 'course',
			'fields' => 'ids',
		) );

		for( $i = 0; $i < count( $courses ); $i++ ) {
			// Get modules for this course.
			$module_count = wp_count_terms( 'module', array(
				'object_ids' => $courses[$i],
			) );

			// Set the starting count.
			if ( $i === 0 ) {
				$min_modules = $module_count;
				continue;
			}

			if ( $min_modules > $module_count ) {
				$min_modules = $module_count;
			}
		}

		return $min_modules;
	}
}

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
		$question_type_count = self::get_question_type_count();
		$usage_data = array(
			'courses' => wp_count_posts( 'course' )->publish,
			'course_videos' => self::get_course_videos_count(),
			'course_no_notifications' => self::get_course_no_notifications_count(),
			'course_prereqs' => self::get_course_prereqs_count(),
			'course_featured' => self::get_course_featured_count(),
			'learners' => self::get_learner_count(),
			'lessons' => wp_count_posts( 'lesson' )->publish,
			'lesson_modules' => self::get_lesson_module_count(),
			'lesson_prereqs' => self::get_lesson_prerequisite_count(),
			'lesson_previews' => self::get_lesson_preview_count(),
			'lesson_length' => self::get_lesson_has_length_count(),
			'lesson_complexity' => self::get_lesson_with_complexity_count(),
			'lesson_videos' => self::get_lesson_with_video_count(),
			'messages' => wp_count_posts( 'sensei_message' )->publish,
			'modules' => wp_count_terms( 'module' ),
			'modules_max' => self::get_max_module_count(),
			'modules_min' => self::get_min_module_count(),
			'questions' => wp_count_posts( 'question' )->publish,
			'question_media' => self::get_question_media_count(),
			'question_random_order' => self::get_question_random_order_count(),
			'teachers' => self::get_teacher_count(),
		);

		return array_merge( $question_type_count, $usage_data );
	}

	/**
	 * Get the number of courses that have a video set.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_course_videos_count() {
		// Match video strings with at least one non-space character
		$query = new WP_Query( array(
			'post_type' => 'course',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_course_video_embed',
					'value' => '[^[:space:]]',
					'compare' => 'REGEXP',
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of courses that have disabled notifications.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_course_no_notifications_count() {
		$query = new WP_Query( array(
			'post_type' => 'course',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'disable_notification',
					'value' => true,
				)
			),
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of courses that have a prerequisite.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_course_prereqs_count() {
		$query = new WP_Query( array(
			'post_type' => 'course',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_course_prerequisite',
					'value' => '',
					'compare' => '!=',
				),
				array(
					'key' => '_course_prerequisite',
					'value' => '0',
					'compare' => '!=',
				),
			),
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of courses that are featured.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_course_featured_count() {
		$query = new WP_Query( array(
			'post_type' => 'course',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_course_featured',
					'value' => 'featured',
				)
			),
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of teachers.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of teachers.
	 **/
	private static function get_teacher_count() {
		$teacher_query = new WP_User_Query( array(
			'fields' => 'ID',
			'role' => 'teacher',
		) );

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
		$user_query = new WP_User_Query( array( 'fields' => 'ID' ) );
		$learners = $user_query->get_results();

		foreach( $learners as $learner ) {
			$course_args = array(
				'user_id' => $learner,
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
	 * Get the total number of published lessons that have a prerequisite set.
	 *
	 * @since 1.9.20
	 *
	 * @return array Number of published lessons with a prerequisite.
	 **/
	private static function get_lesson_prerequisite_count() {
		$query = new WP_Query( array(
			'post_type' => 'lesson',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_lesson_prerequisite',
					'value' => 0,
					'compare' => '>',
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the total number of published lessons that enable previewing.
	 *
	 * @since 1.9.20
	 *
	 * @return array Number of published lessons that enable previewing.
	 **/
	private static function get_lesson_preview_count() {
		$query = new WP_Query( array(
			'post_type' => 'lesson',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_lesson_preview',
					'value' => '',
					'compare' => '!=',
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the total number of published lessons that are associated with a module.
	 *
	 * @since 1.9.20
	 *
	 * @return array Number of published lessons associated with a module.
	 **/
	private static function get_lesson_module_count() {
		$query = new WP_Query( array(
			'post_type' => 'lesson',
			'fields' => 'ids',
			'tax_query' => array(
				array(
					'taxonomy' => 'module',
					'operator' => 'EXISTS'
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of lessons for which the "lesson length" has been set.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of lessons.
	 **/
	private static function get_lesson_has_length_count() {
		$query = new WP_Query( array(
			'post_type' => 'lesson',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_lesson_length',
					'value' => '',
					'compare' => '!=',
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of lessons for which the "lesson complexity" has been set.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of lessons.
	 **/
	private static function get_lesson_with_complexity_count() {
		$query = new WP_Query( array(
			'post_type' => 'lesson',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_lesson_complexity',
					'value' => '',
					'compare' => '!=',
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the number of lessons that have a video.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of lessons.
	 **/
	private static function get_lesson_with_video_count() {
		$query = new WP_Query( array(
			'post_type' => 'lesson',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_lesson_video_embed',
					'value' => '[^[:space:]]',
					'compare' => 'REGEXP',
				)
			)
		) );

		return $query->found_posts;
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
		$query = new WP_Query( array(
			'post_type' => 'course',
			'posts_per_page' => -1,
			'fields' => 'ids',
		) );
		$courses = $query->posts;

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
		$query = new WP_Query( array(
			'post_type' => 'course',
			'posts_per_page' => -1,
			'fields' => 'ids',
		) );
		$courses = $query->posts;

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

	/**
	 * Get the total number of published questions of each type.
	 *
	 * @since 1.9.20
	 *
	 * @return array Number of published questions of each type.
	 **/
	private static function get_question_type_count() {
		$count = array();
		$question_types = Sensei()->question->question_types();

		foreach ( $question_types as $key=>$value ) {
			$count[ self::get_question_type_key( $key ) ] = 0;
		}

		$query = new WP_Query( array(
			'post_type' => 'question',
			'posts_per_page' => -1,
			'fields' => 'ids'
		) );
		$questions = $query->posts;

		foreach ( $questions as $question ) {
			$question_type = Sensei()->question->get_question_type( $question );
			$key = self::get_question_type_key( $question_type );

			if ( array_key_exists( $key, $count ) ) {
				$count[ $key ]++;
			}
		}

		return $count;
	}

	/**
	 * Get the total number of published questions that have media.
	 *
	 * @since 1.9.20
	 *
	 * @return array Number of published questions with media.
	 **/
	private static function get_question_media_count() {
		$query = new WP_Query( array(
			'post_type' => 'question',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_question_media',
					'value' => 0,
					'compare' => '>',
				)
			)
		) );

		return $query->found_posts;
	}

	/**
	 * Get the total number of multiple choice questions where "Randomise answer order" is checked.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of multiple choice questions with randomized answers.
	 **/
	private static function get_question_random_order_count() {
		$count = 0;
		$query = new WP_Query( array(
			'post_type' => 'question',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_random_order',
					'value' => 'yes',
					'compare' => '=',
				)
			)
		) );
		$questions = $query->posts;

		foreach ( $questions as $question ) {
			$question_type = Sensei()->question->get_question_type( $question );

			/*
			 * Random answer order is only applicable for multiple choice questions.
			 * Since it's possible that other question types could have a random answer order set,
			 * let's explicitly handle multiple choice.
			 */
			if ( $question_type === 'multiple-choice' ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the question type key. Replaces dashes with underscores in order to conform to
	 * Tracks naming conventions.
	 *
	 * @since 1.9.20
	 *
	 * @return array Question type key.
	 **/
	private static function get_question_type_key( $key ) {
		return str_replace( '-', '_', 'question_' . $key );
	}
}

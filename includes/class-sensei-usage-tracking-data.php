<?php
/**
 * Usage tracking data
 *
 * @package Usage Tracking
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
		$quiz_stats          = self::get_quiz_stats();
		$usage_data          = array(
			'courses'                 => wp_count_posts( 'course' )->publish,
			'course_active'           => self::get_course_active_count(),
			'course_completed'        => self::get_course_completed_count(),
			'course_completion_rate'  => self::get_course_completion_rate(),
			'course_videos'           => self::get_course_videos_count(),
			'course_no_notifications' => self::get_course_no_notifications_count(),
			'course_prereqs'          => self::get_course_prereqs_count(),
			'course_featured'         => self::get_course_featured_count(),
			'enrolments'              => self::get_course_enrolments(),
			'enrolment_first'         => self::get_first_course_enrolment(),
			'enrolment_last'          => self::get_last_course_enrolment(),
			'enrolment_calculated'    => self::get_is_enrolment_calculated() ? 1 : 0,
			'learners'                => self::get_learner_count(),
			'lessons'                 => wp_count_posts( 'lesson' )->publish,
			'lesson_modules'          => self::get_lesson_module_count(),
			'lesson_prereqs'          => self::get_lesson_prerequisite_count(),
			'lesson_previews'         => self::get_lesson_preview_count(),
			'lesson_length'           => self::get_lesson_has_length_count(),
			'lesson_complexity'       => self::get_lesson_with_complexity_count(),
			'lesson_videos'           => self::get_lesson_with_video_count(),
			'messages'                => wp_count_posts( 'sensei_message' )->publish,
			'modules'                 => wp_count_terms( 'module' ),
			'modules_max'             => self::get_max_module_count(),
			'modules_min'             => self::get_min_module_count(),
			'questions'               => wp_count_posts( 'question' )->publish,
			'question_media'          => self::get_question_media_count(),
			'question_random_order'   => self::get_question_random_order_count(),
			'teachers'                => self::get_teacher_count(),
		);

		return array_merge( $question_type_count, $usage_data, $quiz_stats );
	}

	/**
	 * Get the base fields to be sent for event logging.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	public static function get_event_logging_base_fields() {
		$base_fields = [
			'paid'     => 0,
			'courses'  => wp_count_posts( 'course' )->publish,
			'learners' => self::get_learner_count(),
		];

		/**
		 * Filter the event logging source.
		 *
		 * @param string The source (defaults to "unknown").
		 */
		$base_fields['source'] = apply_filters( 'sensei_event_logging_source', 'unknown' );

		/**
		 * Filter the fields that should be sent with every event that is logged.
		 *
		 * @param array $base_fields The default base fields.
		 */
		return apply_filters( 'sensei_event_logging_base_fields', $base_fields );
	}

	/**
	 * Get stats related to lesson quizzes.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	private static function get_quiz_stats() {
		$query = new WP_Query(
			array(
				'post_type'      => 'lesson',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => '_quiz_has_questions',
						'value' => true,
					),
					array(
						'key'     => '_lesson_course',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => '_lesson_course',
						'value'   => '0',
						'compare' => '!=',
					),
				),
			)
		);

		$stats              = array(
			'quiz_total'          => 0,
			'questions_min'       => null,
			'questions_max'       => null,
			'category_questions'  => 0,
			'quiz_pass_required'  => 0,
			'quiz_passmark'       => 0,
			'quiz_num_questions'  => 0,
			'quiz_rand_questions' => 0,
			'quiz_auto_grade'     => 0,
			'quiz_allow_retake'   => 0,
		);
		$question_counts    = array();
		$published_quiz_ids = array();

		foreach ( $query->posts as $lesson_id ) {
			$course_id = Sensei()->lesson->get_course_id( $lesson_id );
			if ( empty( $course_id ) || 'publish' !== get_post_status( $lesson_id ) || 'publish' !== get_post_status( $course_id ) ) {
				continue;
			}
			$quiz_id             = Sensei()->lesson->lesson_quizzes( $lesson_id );
			$quiz_question_posts = Sensei()->lesson->lesson_quiz_questions( $quiz_id );
			$question_count      = count( $quiz_question_posts );
			if ( 0 === $question_count ) {
				continue;
			}
			$question_counts[]    = $question_count;
			$published_quiz_ids[] = $quiz_id;
			$stats['quiz_total']++;
		}

		if ( ! empty( $published_quiz_ids ) ) {
			$stats['category_questions']  = self::get_category_question_count( $published_quiz_ids );
			$stats['quiz_num_questions']  = self::get_quiz_setting_non_empty_count( $published_quiz_ids, '_show_questions' );
			$stats['quiz_passmark']       = self::get_quiz_setting_non_empty_count( $published_quiz_ids, '_quiz_passmark' );
			$stats['quiz_pass_required']  = self::get_quiz_setting_value_count( $published_quiz_ids, '_pass_required', 'on' );
			$stats['quiz_rand_questions'] = self::get_quiz_setting_value_count( $published_quiz_ids, '_random_question_order', 'yes' );
			$stats['quiz_auto_grade']     = self::get_quiz_setting_value_count( $published_quiz_ids, '_quiz_grade_type', 'auto' );
			$stats['quiz_allow_retake']   = self::get_quiz_setting_value_count( $published_quiz_ids, '_enable_quiz_reset', 'on' );
		}

		if ( ! empty( $question_counts ) ) {
			$stats['questions_min'] = min( $question_counts );
			$stats['questions_max'] = max( $question_counts );
		}

		return $stats;
	}

	/**
	 * Get the number of quizzes with a non-empty value of a post meta.
	 *
	 * @since 1.11.0
	 *
	 * @param int[]  $published_quiz_ids
	 * @param string $meta_key
	 * @return int
	 */
	private static function get_quiz_setting_non_empty_count( $published_quiz_ids, $meta_key ) {
		global $wpdb;

		$published_quiz_ids = array_map( 'intval', $published_quiz_ids );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT count(DISTINCT `post_id`) FROM {$wpdb->postmeta} WHERE `post_id` IN (" . implode( ',', $published_quiz_ids ) . ") AND `meta_key`=%s AND `meta_value`!='' AND `meta_value`!='0'", $meta_key ) );
	}

	/**
	 * Get the number of quizzes with a non-empty value of a post meta.
	 *
	 * @since 1.11.0
	 *
	 * @param int[]  $published_quiz_ids
	 * @param string $meta_key
	 * @param string $meta_value
	 * @return int
	 */
	private static function get_quiz_setting_value_count( $published_quiz_ids, $meta_key, $meta_value ) {
		global $wpdb;

		$published_quiz_ids = array_map( 'intval', $published_quiz_ids );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT count(DISTINCT `post_id`) FROM {$wpdb->postmeta} WHERE `post_id` IN (" . implode( ',', $published_quiz_ids ) . ') AND `meta_key`=%s AND `meta_value`=%s', $meta_key, $meta_value ) );
	}

	/**
	 * Get the number of category/multiple questions assigned to published quizzes.
	 *
	 * @since 1.11.0
	 *
	 * @param int[] $published_quiz_ids
	 * @return int
	 */
	private static function get_category_question_count( $published_quiz_ids ) {
		$multiple_question_query = new WP_Query(
			array(
				'post_type'        => 'multiple_question',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => 1,
				'meta_query'       => array(
					array(
						'key'   => '_quiz_id',
						'value' => $published_quiz_ids,
					),
				),
			)
		);

		return count( $multiple_question_query->posts );
	}

	/**
	 * Get the total number of active courses across all learners.
	 *
	 * @since 1.10.0
	 *
	 * @return int Number of active courses.
	 **/
	private static function get_course_active_count() {
		$course_args     = array(
			'type'   => 'sensei_course_status',
			'status' => 'any',
		);
		$courses_started = Sensei_Utils::sensei_check_for_activity( $course_args );

		return $courses_started - self::get_course_completed_count();
	}

	/**
	 * Get the total number of completed courses across all learners.
	 *
	 * @since 1.10.0
	 *
	 * @return int Number of completed courses.
	 **/
	private static function get_course_completed_count() {
		$course_args = array(
			'type'   => 'sensei_course_status',
			'status' => 'complete',
		);

		return Sensei_Utils::sensei_check_for_activity( $course_args );
	}

	/**
	 * Calculate the average course completion rate.
	 *
	 * @since 3.6.0
	 *
	 * @return double Average course completion rate.
	 */
	private static function get_course_completion_rate() {
		$course_args             = array(
			'post_type'      => 'course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$courses                 = get_posts( $course_args );
		$course_count            = count( $courses );
		$course_completion_rates = [];

		foreach ( $courses as $course ) {
			// Calculate number of learners who are enrolled in the course.
			$learner_terms          = self::get_enrolled_learner_terms( $course->ID );
			$enrolled_learner_count = 0;

			if ( ! empty( $learner_terms ) && ! is_wp_error( $learner_terms ) ) {
				$enrolled_learner_count = count( $learner_terms );
			}

			// Don't include this course in the calculation if no learners are enrolled.
			if ( 0 === $enrolled_learner_count ) {
				$course_count--;
				continue;
			}

			// Get number of learners who are enrolled in and have completed the course.
			$completed_course_count = self::get_completed_course_count( $course->ID, $learner_terms );

			// Calculate the completion rate.
			$course_completion_rates[] = $completed_course_count / $enrolled_learner_count;
		}

		if ( 0 === $course_count ) {
			return '';
		}

		// Average course completion rate = Sum of course completion rates / # of courses.
		return round( array_sum( $course_completion_rates ) / $course_count * 100, 2 );
	}

	/**
	 * Get learner term data for non-admin learners who are enrolled in a course.
	 *
	 * @since 3.6.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array|WP_Error Learner term data or empty array if no terms found.
	 */
	private static function get_enrolled_learner_terms( $course_id ) {
		$term_args = array(
			'fields'  => 'names',
			'exclude' => self::get_admin_learner_term_ids(),
		);

		return wp_get_object_terms( $course_id, Sensei_PostTypes::LEARNER_TAXONOMY_NAME, $term_args );
	}

	/**
	 * Get number of non-admin learners who are enrolled in and have completed the course.
	 *
	 * @since 3.6.0
	 *
	 * @param int   $course_id Course ID.
	 * @param array $learner_terms Learner term data.
	 *
	 * @return int Number of learners.
	 */
	private static function get_completed_course_count( $course_id, $learner_terms ) {
		$enrolled_learner_ids = array_map( [ 'Sensei_learner', 'get_learner_id' ], $learner_terms );
		$comment_args         = array(
			'type'       => 'sensei_course_status',
			'status'     => 'complete',
			'post_id'    => $course_id,
			'author__in' => $enrolled_learner_ids,
		);

		return Sensei_Utils::sensei_check_for_activity( $comment_args );
	}

	/**
	 * Get the number of courses that have a video set.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of courses.
	 */
	private static function get_course_videos_count() {
		// Match video strings with at least one non-space character.
		$query = new WP_Query(
			array(
				'post_type'  => 'course',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_course_video_embed',
						'value'   => '[^[:space:]]',
						'compare' => 'REGEXP',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'course',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'   => 'disable_notification',
						'value' => true,
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'course',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_course_prerequisite',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => '_course_prerequisite',
						'value'   => '0',
						'compare' => '!=',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'course',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'   => '_course_featured',
						'value' => 'featured',
					),
				),
			)
		);

		return $query->found_posts;
	}

	/**
	 * Gets the total number of non-admin learners enrolled in at least one published course.
	 *
	 * @since 1.12.2
	 *
	 * @return int Number of course enrolments.
	 **/
	private static function get_course_enrolments() {
		return (int) get_terms(
			[
				'hide_empty' => true,
				'fields'     => 'count',
				'exclude'    => self::get_admin_learner_term_ids(),
				'taxonomy'   => Sensei_PostTypes::LEARNER_TAXONOMY_NAME,
			]
		);
	}

	/**
	 * Checks if enrolment has been calculated for the current Sensei version.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private static function get_is_enrolment_calculated() {
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

		return get_option( Sensei_Enrolment_Job_Scheduler::CALCULATION_VERSION_OPTION_NAME ) === $enrolment_manager->get_enrolment_calculation_version();
	}

	/**
	 * Get the learner term IDs for all admin users.
	 *
	 * @return int[]
	 */
	private static function get_admin_learner_term_ids() {
		$admins         = get_users( [ 'role' => 'administrator' ] );
		$admin_term_ids = [];
		foreach ( $admins as $admin ) {
			$learner_term     = Sensei_Learner::get_learner_term( $admin->ID );
			$admin_term_ids[] = $learner_term->term_id;
		}

		return $admin_term_ids;
	}

	/**
	 * Gets the date of the most recent enrolment by any non-admin learner in any published course.
	 *
	 * @since 1.12.2
	 *
	 * @return int Date of the most recent course enrolment.
	 **/
	private static function get_last_course_enrolment() {
		global $wpdb;

		return $wpdb->get_var(
			"SELECT IFNULL(MAX(cm.meta_value), 'N/A')
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_ID
			INNER JOIN {$wpdb->usermeta} um ON c.user_id = um.user_id
			INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
			WHERE comment_type = 'sensei_course_status' AND cm.meta_key = 'start'
				AND um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value NOT LIKE '%administrator%'
				AND post_status = 'publish' AND c.user_id <> 0"
		);
	}

	/**
	 * Gets the date of the first enrolment by any non-admin learner in any published course.
	 *
	 * @since 1.12.2
	 *
	 * @return int Date of the first course enrolment.
	 **/
	private static function get_first_course_enrolment() {
		global $wpdb;

		return $wpdb->get_var(
			"SELECT IFNULL(MIN(cm.meta_value), 'N/A')
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_ID
			INNER JOIN {$wpdb->usermeta} um ON c.user_id = um.user_id
			INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
			WHERE comment_type = 'sensei_course_status' AND cm.meta_key = 'start'
				AND um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value NOT LIKE '%administrator%'
				AND post_status = 'publish' AND c.user_id <> 0"
		);
	}

	/**
	 * Get the number of teachers.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of teachers.
	 **/
	private static function get_teacher_count() {
		$teacher_query = new WP_User_Query(
			array(
				'fields' => 'ID',
				'role'   => 'teacher',
			)
		);

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
		global $wpdb;

		return $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id)
			FROM {$wpdb->comments}
			WHERE comment_type = 'sensei_course_status'
				AND comment_approved IN ('in-progress', 'complete')
				AND user_id <> 0"
		);
	}

	/**
	 * Get the total number of published lessons that have a prerequisite set.
	 *
	 * @since 1.9.20
	 *
	 * @return array Number of published lessons with a prerequisite.
	 **/
	private static function get_lesson_prerequisite_count() {
		$query = new WP_Query(
			array(
				'post_type'  => 'lesson',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_lesson_prerequisite',
						'value'   => 0,
						'compare' => '>',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'lesson',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_lesson_preview',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type' => 'lesson',
				'fields'    => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => 'module',
						'operator' => 'EXISTS',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'lesson',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_lesson_length',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'lesson',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_lesson_complexity',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

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
		$query = new WP_Query(
			array(
				'post_type'  => 'lesson',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_lesson_video_embed',
						'value'   => '[^[:space:]]',
						'compare' => 'REGEXP',
					),
				),
			)
		);

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
		$query       = new WP_Query(
			array(
				'post_type'      => 'course',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$courses     = $query->posts;

		foreach ( $courses as $course ) {
			// Get modules for this course.
			$module_count = wp_count_terms(
				'module',
				array(
					'object_ids' => $course,
				)
			);

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
		$min_modules   = 0;
		$query         = new WP_Query(
			array(
				'post_type'      => 'course',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$courses       = $query->posts;
		$total_courses = is_array( $courses ) ? count( $courses ) : 0;

		for ( $i = 0; $i < $total_courses; $i++ ) {
			// Get modules for this course.
			$module_count = wp_count_terms(
				'module',
				array(
					'object_ids' => $courses[ $i ],
				)
			);

			// Set the starting count.
			if ( 0 === $i ) {
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
		$count          = array();
		$question_types = Sensei()->question->question_types();

		foreach ( $question_types as $key => $value ) {
			$count[ self::get_question_type_key( $key ) ] = 0;
		}

		$query     = new WP_Query(
			array(
				'post_type'      => 'question',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$questions = $query->posts;

		foreach ( $questions as $question ) {
			$question_type = Sensei()->question->get_question_type( $question );
			$key           = self::get_question_type_key( $question_type );

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
		$query = new WP_Query(
			array(
				'post_type'  => 'question',
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => '_question_media',
						'value'   => 0,
						'compare' => '>',
					),
				),
			)
		);

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
		$count     = 0;
		$query     = new WP_Query(
			array(
				'post_type'      => 'question',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_random_order',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			)
		);
		$questions = $query->posts;

		foreach ( $questions as $question ) {
			$question_type = Sensei()->question->get_question_type( $question );

			/*
			 * Random answer order is only applicable for multiple choice questions.
			 * Since it's possible that other question types could have a random answer order set,
			 * let's explicitly handle multiple choice.
			 */
			if ( 'multiple-choice' === $question_type ) {
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
	 * @param string $key Question type.
	 *
	 * @return array Question type key.
	 **/
	private static function get_question_type_key( $key ) {
		return str_replace( '-', '_', 'question_' . $key );
	}
}

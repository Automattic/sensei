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
	public function get_usage_data() {
		return (array) apply_filters( 'sensei_usage_tracking_data', array(
			'courses' => wp_count_posts( 'course' )->publish,
			'learners' => $this->get_learner_count(),
			'lessons' => wp_count_posts( 'lesson' )->publish,
			'messages' => wp_count_posts( 'sensei_message' )->publish,
			'questions' => wp_count_posts( 'question' )->publish,
			'teachers' => $this->get_teacher_count(),
		) );
	}

	/**
	 * Get the number of teachers.
	 *
	 * @since 1.9.20
	 *
	 * @return int Number of teachers.
	 **/
	private function get_teacher_count() {
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
	private function get_learner_count() {
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
}

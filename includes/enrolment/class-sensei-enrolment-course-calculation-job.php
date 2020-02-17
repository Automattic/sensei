<?php
/**
 * File containing the class Sensei_Enrolment_Course_Calculation_Job.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Sensei_Enrolment_Course_Calculation_Job is responsible for recalculating course enrolment for all users or
 * just the users who are already enrolled.
 */
class Sensei_Enrolment_Course_Calculation_Job implements Sensei_Enrolment_Job_Interface {
	const DEFAULT_BATCH_SIZE = 40;

	/**
	 * Course post ID to recalculate.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Recalculate for just the invalidated (set to empty string) course enrolment results.
	 *
	 * @var bool
	 */
	private $invalidated_only;

	/**
	 * Number of learners to calculate per batch.
	 *
	 * @var int
	 */
	private $batch_size = self::DEFAULT_BATCH_SIZE;

	/**
	 * Sensei_Enrolment_Course_Calculation_Job constructor.
	 *
	 * @param array $args Arguments to run for the job.
	 */
	public function __construct( $args ) {
		$this->course_id        = isset( $args['course_id'] ) ? intval( $args['course_id'] ) : null;
		$this->invalidated_only = isset( $args['invalidated_only'] ) ? boolval( $args['invalidated_only'] ) : false;
		$this->batch_size       = isset( $args['batch_size'] ) ? intval( $args['batch_size'] ) : self::DEFAULT_BATCH_SIZE;
	}

	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'sensei_calculate_course_enrolments';
	}

	/**
	 * Get the arguments to run with the job.
	 *
	 * @return array
	 */
	public function get_args() {
		return [
			'course_id'        => $this->course_id,
			'invalidated_only' => $this->invalidated_only,
			'batch_size'       => $this->batch_size,
		];
	}

	/**
	 * Run the job and return `true` if the job should be immediately rescheduled (for another batch) or `false`
	 * if the job can be considered complete.
	 *
	 * @return bool
	 */
	public function run() {
		if ( empty( $this->course_id ) ) {
			return false;
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $this->course_id );
		$user_ids         = get_users( $this->get_query_args( $course_enrolment ) );

		if ( empty( $user_ids ) ) {
			return false;
		}

		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $this->course_id );
		foreach ( $user_ids as $user_id ) {
			$course_enrolment->is_enrolled( $user_id, false );
		}

		return true;
	}

	/**
	 * Get the query arguments for the user query.
	 *
	 * @param Sensei_Course_Enrolment $course_enrolment Course enrolment handler.
	 *
	 * @return array
	 */
	private function get_query_args( $course_enrolment ) {
		$user_args = [
			'fields' => 'ID',
			'number' => $this->batch_size,
		];

		$meta_key = $course_enrolment->get_course_results_meta_key();

		// When querying for just currently enrolled users, we invalidated the results for all current users first.
		if ( $this->invalidated_only ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Ran inside of async job.
			$user_args['meta_query'] = [
				[
					'key'   => $meta_key,
					'value' => '',
				],
			];
		} else {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Ran inside of async job.
			$user_args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'   => $meta_key,
					'value' => '',
				],
				[
					'key'     => $meta_key,
					'compare' => 'NOT EXISTS',
				],
			];
		}

		return $user_args;
	}
}

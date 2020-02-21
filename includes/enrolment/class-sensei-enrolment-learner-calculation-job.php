<?php
/**
 * File containing the class Sensei_Enrolment_Learner_Calculation_Job.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Sensei_Enrolment_Learner_Calculation_Job is responsible for running jobs of user enrolment calculations. It is set
 * up to run once after a Sensei version upgrade or when Sensei_Course_Enrolment_Manager::get_site_salt() is updated.
 */
class Sensei_Enrolment_Learner_Calculation_Job implements Sensei_Enrolment_Job_Interface {
	/**
	 * Number of users for each job run.
	 *
	 * @var integer
	 */
	private $batch_size;

	/**
	 * Sensei_Enrolment_Learner_Calculation_Job constructor.
	 *
	 * @param integer $batch_size The scheduler's batch size.
	 */
	public function __construct( $batch_size ) {
		$this->batch_size = $batch_size;
	}

	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'sensei_calculate_learner_enrolments';
	}

	/**
	 * Get the arguments to run with the job.
	 *
	 * @return array
	 */
	public function get_args() {
		return [];
	}

	/**
	 * Run the job and return `true` if the job should be immediately rescheduled (for another batch) or `false`
	 * if the job can be considered complete.
	 *
	 * @return bool
	 */
	public function run() {

		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
				'value'   => Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version(),
				'compare' => '!=',
			],
			[
				'key'     => Sensei_Course_Enrolment_Manager::LEARNER_CALCULATION_META_NAME,
				'compare' => 'NOT EXISTS',
			],
		];

		$user_args = [
			'fields'     => 'ID',
			'number'     => $this->batch_size,
			'meta_query' => $meta_query, // phpcs:ignore  WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The results are limited by the batch size.
		];

		$users = get_users( $user_args );

		if ( empty( $users ) ) {
			return false;
		}

		foreach ( $users as $user ) {
			Sensei_Course_Enrolment_Manager::instance()->recalculate_enrolments( $user );
		}

		return true;
	}
}

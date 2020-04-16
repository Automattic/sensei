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
class Sensei_Enrolment_Learner_Calculation_Job implements Sensei_Background_Job_Interface {
	const NAME                      = 'sensei_calculate_learner_enrolments';
	const DEFAULT_BATCH_SIZE        = 20;
	const OPTION_TRACK_LAST_USER_ID = 'sensei_calculate_learner_enrolments_job_last_user_id';
	const OPTION_TRACK_VERSION_CALC = 'sensei_calculate_learner_enrolments_job_calculating_version';

	/**
	 * Number of users for each job run.
	 *
	 * @var integer
	 */
	private $batch_size;

	/**
	 * Whether the job is complete.
	 *
	 * @var bool
	 */
	private $is_complete = false;

	/**
	 * Sensei_Enrolment_Learner_Calculation_Job constructor.
	 */
	public function __construct() {
		/**
		 * Filter the batch size for the number of users to query per run in the learner calculation job.
		 *
		 * @since 3.0.0
		 *
		 * @param int $batch_size Batch size to filter.
		 */
		$this->batch_size = apply_filters( 'sensei_enrolment_learner_calculation_job_batch_size', self::DEFAULT_BATCH_SIZE );
	}

	/**
	 * Get the action name for the scheduled job.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::NAME;
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
	 * Run the job.
	 */
	public function run() {
		$user_args = [
			'fields'  => 'ID',
			'number'  => $this->batch_size,
			'order'   => 'ASC',
			'orderby' => 'ID',
		];

		add_action( 'pre_user_query', [ $this, 'modify_user_query_add_user_id' ] );
		$user_query = new WP_User_Query( $user_args );
		remove_action( 'pre_user_query', [ $this, 'modify_user_query_add_user_id' ] );

		$user_ids = $user_query->get_results();

		add_filter( 'sensei_course_enrolment_store_results', [ Sensei_Course_Enrolment::class, 'do_not_store_negative_enrolment_results' ], 10, 5 );
		foreach ( $user_ids as $user_id ) {
			Sensei_Course_Enrolment_Manager::instance()->recalculate_enrolments( $user_id );
			$this->set_last_user_id( $user_id );
		}
		remove_filter( 'sensei_course_enrolment_store_results', [ Sensei_Course_Enrolment::class, 'do_not_store_negative_enrolment_results' ], 10 );

		if (
			empty( $user_ids )
			|| (int) $user_query->get_total() <= (int) $this->batch_size
		) {
			$this->end();
		}
	}

	/**
	 * Set up job before it is scheduled for the first time.
	 *
	 * @param string $current_version Setting up current version.
	 */
	public function setup( $current_version ) {
		update_option( self::OPTION_TRACK_VERSION_CALC, $current_version, false );

		$this->set_last_user_id( 0 );
	}

	/**
	 * Check if version that is running is for the current version.
	 *
	 * @param string $version_check Version to check.
	 *
	 * @return bool
	 */
	public function is_calculating_version( $version_check ) {
		$version = get_option( self::OPTION_TRACK_VERSION_CALC, false );

		return $version === $version_check;
	}

	/**
	 * After the job runs, check to see if it needs to be re-queued for the next batch.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->is_complete;
	}

	/**
	 * Modify user query to add the user ID check.
	 *
	 * @access private
	 *
	 * @param WP_User_Query $user_query User query to modify.
	 */
	public function modify_user_query_add_user_id( WP_User_Query $user_query ) {
		global $wpdb;

		$user_query->query_where .= $wpdb->prepare( ' AND ID>%d', $this->get_last_user_id() );
	}

	/**
	 * Set the last calculated user ID.
	 *
	 * @param int $user_id User ID.
	 */
	private function set_last_user_id( $user_id ) {
		update_option( self::OPTION_TRACK_LAST_USER_ID, (int) $user_id, false );
	}

	/**
	 * Get the last user ID that was calculated.
	 *
	 * @return int
	 */
	public function get_last_user_id() {
		return (int) get_option( self::OPTION_TRACK_LAST_USER_ID, 0 );
	}

	/**
	 * Clean up after the job ends.
	 */
	public function end() {
		$this->is_complete = true;

		delete_option( self::OPTION_TRACK_LAST_USER_ID );
		delete_option( self::OPTION_TRACK_VERSION_CALC );
	}
}

<?php
/**
 * File containing the class Sensei_Enrolment_Calculation_Scheduler.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Sensei_Enrolment_Calculation_Scheduler is responsible for running jobs of user enrolment calculations. It is set
 * up to run once after a Sensei version upgrade or when Sensei_Course_Enrolment_Manager::get_site_salt() is updated.
 */
class Sensei_Enrolment_Calculation_Scheduler {
	const CALCULATION_VERSION_OPTION_NAME = 'sensei-scheduler-calculation-version';

	/**
	 * Number of users for each job run.
	 *
	 * @var integer
	 */
	private $batch_size;

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;


	/**
	 * Sensei_Enrolment_Calculation_Scheduler constructor.
	 *
	 * @param integer $batch_size The scheduler's batch size.
	 */
	private function __construct( $batch_size ) {
		$this->batch_size = $batch_size;

		add_action( 'sensei_calculate_enrolments', [ $this, 'calculate_enrolments' ], 10, 0 );
		add_action( 'init', [ $this, 'start' ], 101 );
	}

	/**
	 * Fetches the instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self( 20 );
		}

		return self::$instance;
	}

	/**
	 * This method starts the scheduler if the run is not completed for the current Sensei version.
	 */
	public function start() {
		if ( get_option( self::CALCULATION_VERSION_OPTION_NAME ) === Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version() ) {
			return;
		}

		if ( ! wp_next_scheduled( 'sensei_calculate_enrolments' ) ) {
			wp_schedule_single_event( time(), 'sensei_calculate_enrolments' );
		}
	}

	/**
	 * Stops the scheduler.
	 */
	public function stop() {
		wp_clear_scheduled_hook( 'sensei_calculate_enrolments' );
	}


	/**
	 * Marks the run as completed and stops the scheduler.
	 */
	public function complete() {
		update_option(
			self::CALCULATION_VERSION_OPTION_NAME,
			Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
		);
		$this->stop();
	}

	/**
	 * This method will be called at each scheduled job. It calculates the enrolments for a batch of users.
	 *
	 * @access private
	 */
	public function calculate_enrolments() {

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
			$this->complete();
			return;
		}

		foreach ( $users as $user ) {
			Sensei_Course_Enrolment_Manager::instance()->recalculate_enrolments( $user );
		}

		$this->start();
	}
}

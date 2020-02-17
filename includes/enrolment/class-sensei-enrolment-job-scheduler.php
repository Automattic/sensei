<?php
/**
 * File containing the class Sensei_Enrolment_Course_Calculation_Scheduler.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei_Enrolment_Job_Scheduler is a class that handles the async jobs for calculating enrolment.
 */
class Sensei_Enrolment_Job_Scheduler {
	const CALCULATION_VERSION_OPTION_NAME = 'sensei-scheduler-calculation-version';

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Fetches the instance of the class.
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
	 * Sensei_Enrolment_Job_Scheduler constructor.
	 */
	private function __construct() {
		// Handle job that ensures all learners have up-to-date enrolment calculations.
		add_action( 'init', [ $this, 'maybe_start_learner_calculation' ], 101 );
		add_action( Sensei_Enrolment_Learner_Calculation_Job::get_name(), [ $this, 'run_learner_calculation' ] );
	}

	/**
	 * Stops all jobs that this class is responsible for.
	 */
	public function stop_all_jobs() {
		wp_clear_scheduled_hook( Sensei_Enrolment_Learner_Calculation_Job::get_name() );
	}

	/**
	 * Check to see if we need to start learner calculation job.
	 */
	public function maybe_start_learner_calculation() {
		if ( get_option( self::CALCULATION_VERSION_OPTION_NAME ) === Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version() ) {
			return;
		}

		$job = new Sensei_Enrolment_Learner_Calculation_Job( 20 );
		$this->schedule_single_job( $job );
	}

	/**
	 * Run batch of learner calculations.
	 */
	public function run_learner_calculation() {
		$job                 = new Sensei_Enrolment_Learner_Calculation_Job( 20 );
		$completion_callback = function() {
			update_option(
				self::CALCULATION_VERSION_OPTION_NAME,
				Sensei_Course_Enrolment_Manager::get_enrolment_calculation_version()
			);
		};

		$this->handle_self_scheduling_job( $job, $completion_callback );
	}

	/**
	 * Handle the scheduling of a job that might need to be rescheduled after a run.
	 *
	 * @param Sensei_Enrolment_Job_Interface $job
	 * @param callable|null                  $completion_callback
	 */
	private function handle_self_scheduling_job( Sensei_Enrolment_Job_Interface $job, $completion_callback = null ) {
		$reschedule_job = $job->run();

		if ( $reschedule_job ) {
			$this->schedule_single_job( $job );
		} elseif ( is_callable( $completion_callback ) ) {
			call_user_func( $completion_callback );
		}
	}

	/**
	 * Schedule a single job to run as soon as possible.
	 *
	 * @param Sensei_Enrolment_Job_Interface $job
	 */
	private function schedule_single_job( Sensei_Enrolment_Job_Interface $job ) {
		$class_name = get_class( $job );
		$name       = $class_name::get_name();
		$args       = $job->get_args();

		if ( ! wp_next_scheduled( $name, $args ) ) {
			wp_schedule_single_event( time(), $name, $args );
		}
	}
}

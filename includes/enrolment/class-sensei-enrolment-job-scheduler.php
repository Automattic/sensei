<?php
/**
 * File containing the class Sensei_Enrolment_Job_Scheduler.
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
	private function __construct() {}

	/**
	 * Initialize the hooks.
	 */
	public function init() {
		// Handle job that ensures all learners have up-to-date enrolment calculations.
		add_action( 'init', [ $this, 'maybe_start_learner_calculation' ], 101 );
		add_action( Sensei_Enrolment_Learner_Calculation_Job::get_name(), [ $this, 'run_learner_calculation' ] );

		// Handle job that ensures a course's enrolment is up-to-date.
		add_action( Sensei_Enrolment_Course_Calculation_Job::get_name(), [ $this, 'run_course_calculation' ] );

	}

	/**
	 * Start a job to recalculate enrolments for a course.
	 *
	 * @param int  $course_id        Course post ID.
	 * @param bool $invalidated_only Recalculate just the results that have been invalidated (set to an empty string).
	 * @param int  $batch_size       Batch size for the job. Null will use default batch size set by job handler.
	 *
	 * @return Sensei_Enrolment_Course_Calculation_Job Job object.
	 */
	public function start_course_calculation_job( $course_id, $invalidated_only, $batch_size = null ) {
		$args = [
			'course_id'        => $course_id,
			'invalidated_only' => $invalidated_only,
			'batch_size'       => $batch_size,
		];

		$job = new Sensei_Enrolment_Course_Calculation_Job( $args );
		$this->schedule_single_job( $job );

		return $job;
	}

	/**
	 * Stops all jobs that this class is responsible for.
	 */
	public function stop_all_jobs() {
		wp_unschedule_hook( Sensei_Enrolment_Learner_Calculation_Job::get_name() );
		wp_unschedule_hook( Sensei_Enrolment_Course_Calculation_Job::get_name() );
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
	 * Run batch of course calculations.
	 *
	 * @param array $args Arguments for the job.
	 */
	public function run_course_calculation( $args ) {
		$job = new Sensei_Enrolment_Course_Calculation_Job( $args );
		$this->handle_self_scheduling_job( $job );
	}

	/**
	 * Handle the scheduling of a job that might need to be rescheduled after a run.
	 *
	 * @param Sensei_Enrolment_Job_Interface $job
	 * @param callable|null                  $completion_callback
	 */
	private function handle_self_scheduling_job( Sensei_Enrolment_Job_Interface $job, $completion_callback = null ) {
		// Immediately schedule the next job just in case the process times out.
		$this->schedule_single_job( $job );

		$reschedule_job = $job->run();

		if ( ! $reschedule_job ) {
			$this->cancel_scheduled_job( $job );

			if ( is_callable( $completion_callback ) ) {
				call_user_func( $completion_callback );
			}
		}
	}

	/**
	 * Schedule a single job to run as soon as possible.
	 *
	 * @param Sensei_Enrolment_Job_Interface $job Job to schedule.
	 */
	private function schedule_single_job( Sensei_Enrolment_Job_Interface $job ) {
		$class_name = get_class( $job );
		$name       = $class_name::get_name();
		$args       = [ $job->get_args() ];

		if ( ! wp_next_scheduled( $name, $args ) ) {
			wp_schedule_single_event( time(), $name, $args );
		}
	}

	/**
	 * Cancel a scheduled job.
	 *
	 * @param Sensei_Enrolment_Job_Interface $job Job to schedule.
	 */
	private function cancel_scheduled_job( Sensei_Enrolment_Job_Interface $job ) {
		$class_name = get_class( $job );
		$name       = $class_name::get_name();
		$args       = [ $job->get_args() ];

		wp_clear_scheduled_hook( $name, $args );
	}
}

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
 * Sensei_Enrolment_Job_Scheduler is a class that handles the background jobs for calculating enrolment.
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
		add_filter( 'sensei_background_job_actions', [ $this, 'get_background_jobs' ] );

		add_action( Sensei_Enrolment_Learner_Calculation_Job::NAME, [ $this, 'run_learner_calculation' ] );
		add_action( Sensei_Enrolment_Course_Calculation_Job::NAME, [ $this, 'run_course_calculation' ] );
	}

	/**
	 * Check if an enrolment background job is enabled.
	 *
	 * @since 3.1.0
	 *
	 * @param string $enrolment_job_name Job handler name.
	 *
	 * @return bool
	 */
	public function is_background_job_enabled( $enrolment_job_name ) {
		/**
		 * Check if a specific enrolment background job is enabled.
		 *
		 * @since 3.1.0
		 *
		 * @param bool   $is_job_enabled     True if the job is enabled.
		 * @param string $enrolment_job_name Name of the job.
		 */
		return apply_filters( 'sensei_is_enrolment_background_job_enabled', true, $enrolment_job_name );
	}

	/**
	 * Start a job to recalculate enrolments for a course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return Sensei_Enrolment_Course_Calculation_Job|null Job object.
	 */
	public function start_course_calculation_job( $course_id ) {
		if ( ! $this->is_background_job_enabled( Sensei_Enrolment_Course_Calculation_Job::NAME ) ) {
			return null;
		}

		$args = [
			'course_id' => $course_id,
		];

		// Make sure any previous job for this course is stopped.
		$this->cancel_course_calculation_job( $course_id );

		$job = new Sensei_Enrolment_Course_Calculation_Job( $args );
		$job->setup();

		Sensei_Scheduler::instance()->schedule_job( $job );

		return $job;
	}

	/**
	 * Cancel any current course calculation job.
	 *
	 * @param int $course_id Course post ID.
	 */
	private function cancel_course_calculation_job( $course_id ) {
		$args = [
			'course_id' => $course_id,
		];
		$job  = new Sensei_Enrolment_Course_Calculation_Job( $args );

		// If we are able to resume a current job, cancel it.
		if ( $job->resume() ) {
			$job->end();
			Sensei_Scheduler::instance()->cancel_scheduled_job( $job );
		}
	}

	/**
	 * Check to see if we need to start learner calculation job.
	 *
	 * @access private
	 */
	public function maybe_start_learner_calculation() {
		if ( ! $this->is_background_job_enabled( Sensei_Enrolment_Learner_Calculation_Job::NAME ) ) {
			return;
		}

		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();
		$current_version   = $enrolment_manager->get_enrolment_calculation_version();

		if ( get_option( self::CALCULATION_VERSION_OPTION_NAME ) === $current_version ) {
			return;
		}

		$job = new Sensei_Enrolment_Learner_Calculation_Job();

		// If we aren't running for this version, restart the job.
		if ( ! $job->is_calculating_version( $current_version ) ) {
			$job->setup( $current_version );
		}

		Sensei_Scheduler::instance()->schedule_job( $job );
	}

	/**
	 * Run batch of learner calculations.
	 *
	 * @access private
	 */
	public function run_learner_calculation() {
		if ( ! $this->is_background_job_enabled( Sensei_Enrolment_Learner_Calculation_Job::NAME ) ) {
			return;
		}

		$job                 = new Sensei_Enrolment_Learner_Calculation_Job();
		$completion_callback = function() {
			$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

			update_option(
				self::CALCULATION_VERSION_OPTION_NAME,
				$enrolment_manager->get_enrolment_calculation_version()
			);
		};

		Sensei_Scheduler::instance()->run( $job, $completion_callback );
	}

	/**
	 * Run batch of course calculations.
	 *
	 * @access private
	 *
	 * @param array $args Arguments for the job.
	 */
	public function run_course_calculation( $args ) {
		if ( ! $this->is_background_job_enabled( Sensei_Enrolment_Course_Calculation_Job::NAME ) ) {
			return;
		}

		$job = new Sensei_Enrolment_Course_Calculation_Job( $args );
		Sensei_Scheduler::instance()->run( $job );
	}

	/**
	 * Returns all the background jobs this class is responsible for. Used for cancelling in WP Cron.
	 *
	 * @param string[] $jobs List of job action names.
	 *
	 * @return string[]
	 */
	public function get_background_jobs( $jobs ) {
		$jobs[] = Sensei_Enrolment_Learner_Calculation_Job::NAME;
		$jobs[] = Sensei_Enrolment_Course_Calculation_Job::NAME;

		return $jobs;
	}
}

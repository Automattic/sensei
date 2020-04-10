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

		// Handle job that ensures a course's enrolment is up-to-date.
		add_action( Sensei_Enrolment_Course_Calculation_Job::NAME, [ $this, 'run_course_calculation' ] );

	}

	/**
	 * Start a job to recalculate enrolments for a course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return Sensei_Enrolment_Course_Calculation_Job Job object.
	 */
	public function start_course_calculation_job( $course_id ) {
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
		$enrolment_manager = Sensei_Course_Enrolment_Manager::instance();

		if ( get_option( self::CALCULATION_VERSION_OPTION_NAME ) === $enrolment_manager->get_enrolment_calculation_version() ) {
			return;
		}

		$job = new Sensei_Enrolment_Learner_Calculation_Job( 20 );
		Sensei_Scheduler::instance()->schedule_job( $job );
	}

	/**
	 * Run batch of learner calculations.
	 *
	 * @access private
	 */
	public function run_learner_calculation() {
		$job                 = new Sensei_Enrolment_Learner_Calculation_Job( 20 );
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

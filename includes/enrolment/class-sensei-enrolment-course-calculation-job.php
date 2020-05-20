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
class Sensei_Enrolment_Course_Calculation_Job implements Sensei_Background_Job_Interface {
	const NAME                             = 'sensei_calculate_course_enrolments';
	const OPTION_TRACK_LAST_USER_ID_PREFIX = 'sensei_calculate_course_enrolments_last_user_id_';
	const OPTION_TRACK_CURRENT_JOB_PREFIX  = 'sensei_calculate_course_enrolments_current_job_';

	const DEFAULT_BATCH_SIZE = 40;

	/**
	 * Current job unique identifier.
	 *
	 * @var string
	 */
	private $job_id;

	/**
	 * Course post ID to recalculate.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Number of learners to calculate per batch.
	 *
	 * @var int
	 */
	private $batch_size = self::DEFAULT_BATCH_SIZE;

	/**
	 * Whether the job is complete.
	 *
	 * @var bool
	 */
	private $is_complete = false;

	/**
	 * Sensei_Enrolment_Course_Calculation_Job constructor.
	 *
	 * @param array $args Arguments to run for the job.
	 */
	public function __construct( $args ) {
		$this->job_id    = isset( $args['job_id'] ) ? sanitize_text_field( $args['job_id'] ) : null;
		$this->course_id = isset( $args['course_id'] ) ? intval( $args['course_id'] ) : null;

		/**
		 * Filter the batch size for the number of users to query per run in the course calculation job.
		 *
		 * @since 3.0.0
		 *
		 * @param int  $batch_size       Batch size to filter.
		 * @param int  $course_id        Course ID we're running.
		 */
		$this->batch_size = apply_filters( 'sensei_enrolment_course_calculation_job_batch_size', self::DEFAULT_BATCH_SIZE, $this->course_id );
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
		return [
			'job_id'    => $this->job_id,
			'course_id' => $this->course_id,
		];
	}

	/**
	 * Run the job.
	 */
	public function run() {
		$current_job_id = $this->get_current_job_id();

		if (
			empty( $this->course_id )
			|| ! $current_job_id
			|| $current_job_id !== $this->get_job_id()
		) {
			$this->end();

			return;
		}

		add_action( 'pre_user_query', [ $this, 'modify_user_query_add_user_id' ] );
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $this->course_id );
		$user_query       = new WP_User_Query( $this->get_query_args() );
		remove_action( 'pre_user_query', [ $this, 'modify_user_query_add_user_id' ] );

		$user_ids = $user_query->get_results();

		add_filter( 'sensei_course_enrolment_store_results', [ Sensei_Course_Enrolment::class, 'do_not_store_negative_enrolment_results' ], 10, 5 );
		foreach ( $user_ids as $user_id ) {
			$course_enrolment->is_enrolled( $user_id, false );

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
	 * After the job runs, check to see if it needs to be re-queued for the next batch.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->is_complete;
	}

	/**
	 * Get the query arguments for the user query.
	 *
	 * @return array
	 */
	private function get_query_args() {
		$user_args = [
			'fields'  => 'ID',
			'number'  => $this->batch_size,
			'orderby' => 'ID',
			'order'   => 'ASC',
		];

		return $user_args;
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
	 * Get the option name for tracking the current job.
	 *
	 * @return string
	 */
	private function get_current_job_option_name() {
		return self::OPTION_TRACK_CURRENT_JOB_PREFIX . $this->course_id;
	}

	/**
	 * Get the option name for tracking the last user ID.
	 *
	 * @return string
	 */
	private function get_last_user_id_option_name() {
		$job_id = $this->get_job_id();

		if ( ! $job_id ) {
			return false;
		}

		return self::OPTION_TRACK_LAST_USER_ID_PREFIX . $job_id;
	}

	/**
	 * Set the last calculated user ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function set_last_user_id( $user_id ) {
		$current_user_option_name = $this->get_last_user_id_option_name();

		if ( ! $current_user_option_name ) {
			return;
		}

		update_option( $current_user_option_name, (int) $user_id, false );
	}

	/**
	 * Get the last user ID that was calculated.
	 *
	 * @return int
	 */
	public function get_last_user_id() {
		$current_user_option_name = $this->get_last_user_id_option_name();

		if ( ! $current_user_option_name ) {
			return 0;
		}

		return (int) get_option( $current_user_option_name, 0 );
	}

	/**
	 * Get the current job ID.
	 *
	 * @return string|false
	 */
	public function get_current_job_id() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Avoiding cache issues with multiple jobs running.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $this->get_current_job_option_name() ) );

		if ( is_object( $row ) && ! empty( $row->option_value ) ) {
			return sanitize_text_field( $row->option_value );
		}

		return false;
	}

	/**
	 * Get the job ID for this job.
	 *
	 * @return string
	 */
	public function get_job_id() {
		return $this->job_id;
	}

	/**
	 * Set up job before it is scheduled for the first time.
	 */
	public function setup() {
		$this->job_id = md5( uniqid() );

		update_option( $this->get_current_job_option_name(), $this->job_id, false );

		$this->set_last_user_id( 0 );
	}

	/**
	 * Resume the current job. Useful for fetching before cancelling.
	 *
	 * @return bool True if we were able to restore the current job.
	 */
	public function resume() {
		$this->job_id = $this->get_current_job_id();
		if ( ! $this->job_id ) {
			return false;
		}

		return true;
	}

	/**
	 * Clean up when a job is finished or has been cancelled.
	 */
	public function end() {
		$this->is_complete = true;

		$current_user_option_name = $this->get_last_user_id_option_name();
		if ( $current_user_option_name ) {
			delete_option( $current_user_option_name );
		}

		if (
			$this->job_id
			&& $this->job_id === $this->get_current_job_id()
		) {
			delete_option( $this->get_current_job_option_name() );
		}
	}
}

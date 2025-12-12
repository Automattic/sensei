<?php
/**
 * File containing the Sensei_Enrolment_Course_Calculation_Command class.
 *
 * @package sensei
 */

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP-CLI command that runs the course enrolment calculation job.
 *
 * @since $$next-version$$
 */
class Sensei_Enrolment_Course_Calculation_Command {

	/**
	 * Run the course enrolment calculation job for a course.
	 *
	 * ## OPTIONS
	 *
	 * <course_id>
	 * : Course post ID to recalculate.
	 *
	 * [--restart]
	 * : Start a new job even if one already exists.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sensei enrolment calculate-course 123
	 *     wp sensei enrolment calculate-course 123 --restart
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( array $args = [], array $assoc_args = [] ) {
		$course_id = isset( $args[0] ) ? absint( $args[0] ) : 0;

		if ( ! $course_id ) {
			WP_CLI::error( __( 'You must provide a course ID.', 'sensei-lms' ) );
		}

		if ( 'course' !== get_post_type( $course_id ) ) {
			WP_CLI::error( __( 'The provided course ID is not valid.', 'sensei-lms' ) );
		}

		$job_scheduler = Sensei_Enrolment_Job_Scheduler::instance();

		if ( ! $job_scheduler->is_background_job_enabled( Sensei_Enrolment_Course_Calculation_Job::NAME ) ) {
			WP_CLI::error( __( 'The course enrolment calculation job is disabled.', 'sensei-lms' ) );
		}

		$should_restart = isset( $assoc_args['restart'] );
		$job            = new Sensei_Enrolment_Course_Calculation_Job(
			[
				'course_id' => $course_id,
			]
		);

		if ( $should_restart || ! $job->resume() ) {
			$job = $job_scheduler->start_course_calculation_job( $course_id );

			if ( ! $job ) {
				WP_CLI::error( __( 'Unable to start the course enrolment calculation job.', 'sensei-lms' ) );
			}

			WP_CLI::log(
				sprintf(
					/* translators: Placeholder is the course ID. */
					__( 'Starting enrolment calculation for course %d.', 'sensei-lms' ),
					$course_id
				)
			);
		} else {
			WP_CLI::log(
				sprintf(
					/* translators: Placeholder is the course ID. */
					__( 'Resuming enrolment calculation for course %d.', 'sensei-lms' ),
					$course_id
				)
			);
		}

		do {
			Sensei_Scheduler::instance()->run( $job );

			$last_user_id = $job->get_last_user_id();
			if ( $last_user_id ) {
				WP_CLI::log(
					sprintf(
						/* translators: Placeholder is the last processed user ID. */
						__( 'Last processed user ID: %d', 'sensei-lms' ),
						$last_user_id
					)
				);
			}
		} while ( ! $job->is_complete() );

		WP_CLI::success(
			sprintf(
				/* translators: Placeholder is the course ID. */
				__( 'Finished calculating enrolment for course %d.', 'sensei-lms' ),
				$course_id
			)
		);
	}
}

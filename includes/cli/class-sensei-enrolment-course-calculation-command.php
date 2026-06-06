<?php
/**
 * File containing the Sensei_Enrolment_Course_Calculation_Command class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP-CLI command that runs the course enrolment calculation job.
 *
 * @since 4.25.2
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

		$course = get_post( $course_id );
		if ( ! $course || 'course' !== get_post_type( $course ) ) {
			/* translators: Placeholder is the course ID. */
			WP_CLI::error( sprintf( __( 'The course with ID %d does not exist.', 'sensei-lms' ), $course_id ) );
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

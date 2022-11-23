<?php
/**
 * Sensei_DB_Seed_Command class file.
 *
 * @package sensei
 */

defined( 'ABSPATH' ) || exit;

/**
 * WP-CLI command that helps with seeding the database.
 *
 * @since 4.3.0
 */
class Sensei_DB_Seed_Command {
	/**
	 * The timestamp at which the timer has started.
	 *
	 * @var float $timer_start
	 */
	private $timer_start;

	/**
	 * Seed the database.
	 *
	 * ## OPTIONS
	 *
	 * --users=<user_count>
	 * : The number of users to be inserted.
	 *
	 * --courses=<course_count>
	 * : The number of courses to be inserted.
	 *
	 * --lessons=<lesson_count>
	 * : The number of lessons per course.
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command arguments with names.
	 */
	public function __invoke( array $args = [], array $assoc_args = [] ) {
		$this->start_timer();

		$target_user_count   = (int) $assoc_args['users'];
		$target_course_count = (int) $assoc_args['courses'];
		$lessons_per_course  = (int) $assoc_args['lessons'];

		// Disable some hooks that get in the way.
		remove_action( 'sensei_course_status_updated', array( Sensei()->frontend, 'redirect_to_course_completed_page' ), 1000 );

		// Queries.
		$users_query = new WP_User_Query(
			[
				'number'      => 1, // We need only the total count.
				'count_total' => true,
				'fields'      => 'ids',
				'meta_key'    => '_seeded', // phpcs:ignore WordPress.DB.SlowDBQuery -- Filter seeded users only.
			]
		);

		$course_query = new WP_Query(
			[
				'posts_per_page' => -1,
				'post_type'      => 'course',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_key'       => '_seeded', // phpcs:ignore WordPress.DB.SlowDBQuery -- Filter seeded courses only.
			]
		);

		// Counters.
		$total_user_count    = $users_query->get_total();
		$total_course_count  = $course_query->found_posts;
		$seeded_user_count   = 0;
		$seeded_course_count = 0;

		// Insert the courses and lessons.
		while ( $total_course_count < $target_course_count ) {
			$total_course_count++;
			$seeded_course_count++;

			$course_title = "Course $total_course_count";
			$course_id    = wp_insert_post(
				[
					'post_title'  => $course_title,
					'post_type'   => 'course',
					'post_status' => 'publish',
					'meta_input'  => [
						'_seeded' => true,
					],
				]
			);

			$this->log( "Inserted '$course_title' ($course_id)." );

			for ( $current_lesson_count = 1; $current_lesson_count <= $lessons_per_course; $current_lesson_count++ ) {
				$lesson_title = "Lesson $current_lesson_count in course $total_course_count";
				$lesson_id    = wp_insert_post(
					[
						'post_title'  => $lesson_title,
						'post_type'   => 'lesson',
						'post_status' => 'publish',
						'meta_input'  => [
							'_lesson_course' => $course_id,
							'_seeded'        => true,
						],
					]
				);

				$this->log( "Inserted '$lesson_title' ($lesson_id)." );
			}
		}

		// Fetch the courses after inserting them. We need this in case the seeder is re-run.
		$course_ids = $course_query->get_posts();

		// Insert and enroll the users.
		while ( $total_user_count < $target_user_count ) {
			$total_user_count++;
			$seeded_user_count++;

			$user_login = "user_$total_user_count";
			$user_id    = wp_insert_user(
				[
					'user_login' => $user_login,
					'user_email' => "$user_login@example.com",
					'meta_input' => [
						'_seeded' => true,
					],
				]
			);

			$this->log( "Inserted '$user_login' ($user_id)." );

			// Enroll.
			foreach ( $course_ids as $course_id ) {
				$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
				$course_enrolment->enrol( $user_id );
				Sensei_Utils::force_complete_user_course( $user_id, $course_id );
			}

			// Avoid memory issues due to cache.
			wp_cache_flush();

			$this->log( "Enrolled '$user_login' ($user_id)." );
		}

		WP_CLI::success(
			"
Seeded users: $seeded_user_count ($total_user_count total)
Seeded courses: $seeded_course_count ($total_course_count total)
Execution time: {$this->get_execution_time()}
"
		);
	}

	/**
	 * Output the log message prefixed by a datetime.
	 *
	 * @param string $message The logged message.
	 */
	private function log( string $message ) {
		$date = wp_date( 'Y-m-d H:i:s' );

		WP_CLI::log( "[$date] $message" );
	}

	/**
	 * Start the timer.
	 */
	private function start_timer() {
		$this->timer_start = microtime( true );
	}

	/**
	 * Return the execution time.
	 *
	 * @return string The execution time.
	 */
	private function get_execution_time(): string {
		$now            = microtime( true );
		$execution_time = gmdate( 'H:i:s', $now - $this->timer_start );

		return $execution_time;
	}
}

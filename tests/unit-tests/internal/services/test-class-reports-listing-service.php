<?php
/**
 * Shared report student population coverage.
 *
 * @package sensei-tests
 */

use Sensei\Internal\Services\Reports_Listing_Service_Interface;

/**
 * Shared comments and HPPS student-listing contract.
 */
abstract class Reports_Listing_Service_Test extends WP_UnitTestCase {
	public function testGetCourseStudents_TemporaryProgressInterspersed_ReturnsFullEligiblePages(): void {
		$this->assert_temporary_progress_interspersed_returns_full_eligible_pages( 'course', 'get_course_students' );
	}

	public function testGetLessonStudents_TemporaryProgressInterspersed_ReturnsFullEligiblePages(): void {
		$this->assert_temporary_progress_interspersed_returns_full_eligible_pages( 'lesson', 'get_lesson_students' );
	}

	public function testGetCourseStudents_OnlyTemporaryProgressCreated_ReturnsEmptyPopulation(): void {
		$this->assert_only_temporary_progress_created_returns_empty_population( 'course', 'get_course_students' );
	}

	public function testGetLessonStudents_OnlyTemporaryProgressCreated_ReturnsEmptyPopulation(): void {
		$this->assert_only_temporary_progress_created_returns_empty_population( 'lesson', 'get_lesson_students' );
	}

	public function testGetCourseStudents_UserStatusAndDateRestrictionsGiven_PreservesRestrictions(): void {
		$this->assert_user_status_and_date_restrictions_given_preserves_restrictions( 'course', 'get_course_students' );
	}

	public function testGetLessonStudents_UserStatusAndDateRestrictionsGiven_PreservesRestrictions(): void {
		$this->assert_user_status_and_date_restrictions_given_preserves_restrictions( 'lesson', 'get_lesson_students' );
	}

	public function testGetUserCourses_TemporaryStudentRequested_KeepsSelectedUserProgress(): void {
		$factory = new Sensei_Factory();
		$post    = $factory->course->create();
		$user    = $factory->user->create( array( 'user_login' => 'sensei_guest_student' ) );
		$this->seed_report_progress( $post, $user, 'course', 'in-progress', '2022-01-01 00:00:00' );

		$actual = $this->get_report_service()->get_user_courses(
			array(
				'user_id' => $user,
				'type'    => 'sensei_course_status',
			)
		);

		$this->assertSame( 1, $actual['total_count'], 'Selected-user operations keep their population.' );
		$this->assertSame( array( $post ), wp_list_pluck( $actual['items'], 'post_id' ), 'The temporary student still has their course progress.' );
	}

	public function testGetUserLessonProgress_TemporaryStudentRequested_KeepsSelectedUserProgress(): void {
		$factory = new Sensei_Factory();
		$post    = $factory->lesson->create();
		$user    = $factory->user->create( array( 'user_login' => 'sensei_preview_student' ) );
		$this->seed_report_progress( $post, $user, 'lesson', 'in-progress', '2022-01-01 00:00:00' );

		$actual = $this->get_report_service()->get_user_lesson_progress(
			array(
				'post_id' => $post,
				'user_id' => $user,
				'type'    => 'sensei_lesson_status',
			)
		);

		$this->assertNotNull( $actual, 'Selected-user operations keep their population.' );
		$this->assertSame( $user, $actual->user_id, 'The temporary student still has their lesson progress.' );
	}

	abstract protected function get_report_service(): Reports_Listing_Service_Interface;

	abstract protected function seed_report_progress( int $post, int $user, string $type, string $status, string $date ): void;

	public function testGetCourseStudents_TranslatedCourseQueried_ReturnsOriginalProgress(): void {
		$this->assert_translated_post_returns_original_progress( 'course', 'get_course_students', 'sensei_course_progress_get_course_id' );
	}

	public function testGetLessonStudents_TranslatedLessonQueried_ReturnsOriginalProgress(): void {
		$this->assert_translated_post_returns_original_progress( 'lesson', 'get_lesson_students', 'sensei_lesson_progress_get_lesson_id' );
	}

	private function assert_translated_post_returns_original_progress( string $type, string $method, string $filter_name ): void {
		$factory      = new Sensei_Factory();
		$original     = $factory->post->create( array( 'post_type' => $type ) );
		$translated   = $factory->post->create( array( 'post_type' => $type ) );
		$user         = $factory->user->create();
		$map_progress = static function ( int $post_id ) use ( $original, $translated ): int {
			return $translated === $post_id ? $original : $post_id;
		};
		$this->seed_report_progress( $original, $user, $type, 'in-progress', '2022-01-01 00:00:00' );
		add_filter( $filter_name, $map_progress );

		try {
			$actual = $this->get_report_service()->$method(
				array(
					'post_id' => $translated,
					'type'    => 'sensei_' . $type . '_status',
					'status'  => 'any',
				)
			);
		} finally {
			remove_filter( $filter_name, $map_progress );
		}

		$this->assertSame( 1, $actual['total_count'] );
		$this->assertSame( array( $user ), wp_list_pluck( $actual['items'], 'user_id' ) );
		$this->assertSame( $translated, $actual['items'][0]->post_id );
	}

	private function assert_temporary_progress_interspersed_returns_full_eligible_pages( string $type, string $method ): void {
		$factory = new Sensei_Factory();
		$post    = $factory->post->create( array( 'post_type' => $type ) );
		$users   = array();
		foreach ( array( 'registered_first', 'sensei_guest_student', 'registered_second', 'sensei_preview_student', 'registered_third' ) as $index => $login ) {
			$user = $factory->user->create( array( 'user_login' => $login ) );
			$this->seed_report_progress( $post, $user, $type, 'ungraded', '2022-01-0' . ( $index + 1 ) . ' 00:00:00' );
			$users[] = $user;
		}
		$args    = array(
			'post_id'                     => $post,
			'type'                        => 'sensei_' . $type . '_status',
			'status'                      => 'any',
			'exclude_user_login_prefixes' => array(),
			'include_statuses_override'   => array( 'ungraded' ),
			'number'                      => 2,
			'order'                       => 'ASC',
			'orderby'                     => 'comment_date',
		);
		$service = $this->get_report_service();

		$first = $service->$method( $args );
		$last  = $service->$method( array_merge( $args, array( 'offset' => 2 ) ) );

		$this->assertSame( 3, $first['total_count'], 'First page total excludes temporary progress, including ungraded work.' );
		$this->assertSame( array( $users[0], $users[2] ), wp_list_pluck( $first['items'], 'user_id' ), 'The first page must be full.' );
		$this->assertSame( 3, $last['total_count'], 'Last page uses the same population.' );
		$this->assertSame( array( $users[4] ), wp_list_pluck( $last['items'], 'user_id' ), 'Offset applies to eligible students.' );
	}

	private function assert_only_temporary_progress_created_returns_empty_population( string $type, string $method ): void {
		$factory = new Sensei_Factory();
		$post    = $factory->post->create( array( 'post_type' => $type ) );
		foreach ( array( 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user = $factory->user->create( array( 'user_login' => $login ) );
			$this->seed_report_progress( $post, $user, $type, 'ungraded', '2022-01-01 00:00:00' );
		}

		$actual = $this->get_report_service()->$method(
			array(
				'post_id' => $post,
				'type'    => 'sensei_' . $type . '_status',
				'status'  => 'any',
				'number'  => 1,
			)
		);

		$this->assertSame(
			array(
				'items'       => array(),
				'total_count' => 0,
			),
			$actual
		);
	}

	private function assert_user_status_and_date_restrictions_given_preserves_restrictions( string $type, string $method ): void {
		$factory  = new Sensei_Factory();
		$post     = $factory->post->create( array( 'post_type' => $type ) );
		$eligible = $factory->user->create();
		$other    = $factory->user->create();
		$guest    = $factory->user->create( array( 'user_login' => 'sensei_guest_student' ) );
		foreach ( array( $eligible, $other, $guest ) as $user ) {
			$this->seed_report_progress( $post, $user, $type, 'in-progress', '2022-01-02 00:00:00' );
		}
		$args    = array(
			'post_id'    => $post,
			'type'       => 'sensei_' . $type . '_status',
			'user_id'    => $eligible,
			'status'     => 'in-progress',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Exercise the supported start-date restriction.
				array(
					array(
						'key'     => 'start',
						'value'   => '2022-01-01',
						'compare' => '>=',
					),
				),
			),
		);
		$service = $this->get_report_service();

		$matching     = $service->$method( $args );
		$wrong_status = $service->$method( array_merge( $args, array( 'status' => 'complete' ) ) );
		$wrong_date   = $service->$method(
			array_merge(
				$args,
				array(
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Exercise the supported start-date restriction.
						array(
							array(
								'key'     => 'start',
								'value'   => '2022-01-03',
								'compare' => '>=',
							),
						),
					),
				)
			)
		);

		$this->assertSame( array( $eligible ), wp_list_pluck( $matching['items'], 'user_id' ), 'User restriction is retained.' );
		$this->assertSame( 1, $matching['total_count'], 'Restricted total matches rows.' );
		$this->assertSame(
			array(
				'items'       => array(),
				'total_count' => 0,
			),
			$wrong_status,
			'Status restriction is retained.'
		);
		$this->assertSame(
			array(
				'items'       => array(),
				'total_count' => 0,
			),
			$wrong_date,
			'Start date restriction is retained.'
		);
	}
}

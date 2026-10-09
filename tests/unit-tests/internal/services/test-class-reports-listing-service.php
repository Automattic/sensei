<?php
/**
 * Shared report student listing coverage.
 *
 * @package sensei-tests
 */

use Sensei\Internal\Services\Reports_Listing_Service_Interface;

/**
 * Shared comments and HPPS student-listing contract.
 */
abstract class Reports_Listing_Service_Test extends WP_UnitTestCase {
	public function testGetLessonStudents_RegisteredAndTemporaryProgressCreated_PaginatesOnlyRegisteredStudents(): void {
		$this->assert_registered_and_temporary_progress_paginates_registered_students( 'lesson', 'get_lesson_students' );
	}

	public function testGetLessonStudents_OffsetBeyondTotalGiven_ReturnsLastPage(): void {
		$factory = new Sensei_Factory();
		$lesson  = $factory->lesson->create();
		$user    = $factory->user->create();
		$this->seed_report_progress( $lesson, $user, 'lesson', 'in-progress', '2022-01-01 00:00:00' );

		$actual = $this->get_report_service()->get_lesson_students(
			array(
				'post_id' => $lesson,
				'type'    => 'sensei_lesson_status',
				'number'  => 10,
				'offset'  => 100,
				'status'  => 'any',
			)
		);

		$this->assertSame( 1, $actual['total_count'] );
		$this->assertSame( array( $user ), wp_list_pluck( $actual['items'], 'user_id' ) );
	}

	public function testGetLessonStudents_OnlyTemporaryProgressCreated_ReturnsNoStudents(): void {
		$this->assert_only_temporary_progress_created_returns_no_students( 'lesson', 'get_lesson_students' );
	}

	public function testGetLessonStudents_UserIdsGiven_ReturnsMatchingStudent(): void {
		$this->assert_user_ids_filter_returns_matching_student( 'lesson', 'get_lesson_students' );
	}

	public function testGetLessonStudents_StatusGiven_ReturnsMatchingStudent(): void {
		$this->assert_status_filter_returns_matching_student( 'lesson', 'get_lesson_students' );
	}

	public function testGetLessonStudents_TranslatedLessonQueried_ReturnsOriginalProgress(): void {
		$this->assert_translated_post_returns_original_progress( 'lesson', 'get_lesson_students', 'sensei_lesson_progress_get_lesson_id' );
	}

	public function testGetCourseStudents_RegisteredAndTemporaryProgressCreated_PaginatesOnlyRegisteredStudents(): void {
		$this->assert_registered_and_temporary_progress_paginates_registered_students( 'course', 'get_course_students' );
	}

	public function testGetCourseStudents_OnlyTemporaryProgressCreated_ReturnsNoStudents(): void {
		$this->assert_only_temporary_progress_created_returns_no_students( 'course', 'get_course_students' );
	}

	public function testGetCourseStudents_UserIdsGiven_ReturnsMatchingStudent(): void {
		$this->assert_user_ids_filter_returns_matching_student( 'course', 'get_course_students' );
	}

	public function testGetCourseStudents_StatusGiven_ReturnsMatchingStudent(): void {
		$this->assert_status_filter_returns_matching_student( 'course', 'get_course_students' );
	}

	public function testGetCourseStudents_StartDateGiven_ReturnsMatchingStudent(): void {
		$factory = new Sensei_Factory();
		$post    = $factory->course->create();
		$before  = $factory->user->create();
		$after   = $factory->user->create();
		$this->seed_report_progress( $post, $before, 'course', 'in-progress', '2022-01-01 00:00:00' );
		$this->seed_report_progress( $post, $after, 'course', 'in-progress', '2022-01-03 00:00:00' );

		$actual = $this->get_report_service()->get_course_students(
			array(
				'post_id'    => $post,
				'type'       => 'sensei_course_status',
				'status'     => 'any',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Exercise the course start-date filter.
					array(
						array(
							'key'     => 'start',
							'value'   => '2022-01-02',
							'compare' => '>=',
							'type'    => 'DATE',
						),
					),
				),
			)
		);

		$this->assertSame( 1, $actual['total_count'] );
		$this->assertSame( array( $after ), wp_list_pluck( $actual['items'], 'user_id' ) );
	}

	public function testGetCourseStudents_TranslatedCourseQueried_ReturnsOriginalProgress(): void {
		$this->assert_translated_post_returns_original_progress( 'course', 'get_course_students', 'sensei_course_progress_get_course_id' );
	}

	public function testGetUserLessonProgress_TemporaryStudentLessonRequested_ReturnsProgress(): void {
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

		$this->assertNotNull( $actual, 'The preview student still has lesson progress.' );
		$this->assertSame( $user, $actual->user_id, 'The temporary student still has their lesson progress.' );
	}

	/**
	 * Temporary student course reports are empty.
	 *
	 * @dataProvider temporary_user_login_provider
	 *
	 * @param string $login Temporary user login.
	 */
	public function testGetUserCourses_TemporaryStudentCoursesRequested_ReturnsNoCourses( string $login ): void {
		$factory = new Sensei_Factory();
		$post    = $factory->course->create();
		$user    = $factory->user->create( array( 'user_login' => $login ) );
		$this->seed_report_progress( $post, $user, 'course', 'in-progress', '2022-01-01 00:00:00' );

		$actual = $this->get_report_service()->get_user_courses(
			array(
				'user_id' => $user,
				'type'    => 'sensei_course_status',
			)
		);

		$this->assertSame( 0, $actual['total_count'], 'Temporary student courses should not count in Reports.' );
		$this->assertSame( array(), $actual['items'], 'Temporary student courses should not appear in Reports.' );
	}

	abstract protected function get_report_service(): Reports_Listing_Service_Interface;

	abstract protected function seed_report_progress( int $post, int $user, string $type, string $status, string $date ): void;

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

	private function assert_registered_and_temporary_progress_paginates_registered_students( string $type, string $method ): void {
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
		$this->assertSame( 3, $last['total_count'], 'Last page total counts registered students.' );
		$this->assertSame( array( $users[4] ), wp_list_pluck( $last['items'], 'user_id' ), 'Offset skips guest and preview students.' );
	}

	private function assert_only_temporary_progress_created_returns_no_students( string $type, string $method ): void {
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

	private function assert_user_ids_filter_returns_matching_student( string $type, string $method ): void {
		$factory  = new Sensei_Factory();
		$post     = $factory->post->create( array( 'post_type' => $type ) );
		$selected = $factory->user->create();
		$other    = $factory->user->create();
		$this->seed_report_progress( $post, $selected, $type, 'in-progress', '2022-01-01 00:00:00' );
		$this->seed_report_progress( $post, $other, $type, 'in-progress', '2022-01-01 00:00:00' );

		$actual = $this->get_report_service()->$method(
			array(
				'post_id' => $post,
				'type'    => 'sensei_' . $type . '_status',
				'user_id' => array( $selected ),
				'status'  => 'any',
			)
		);

		$this->assertSame( 1, $actual['total_count'] );
		$this->assertSame( array( $selected ), wp_list_pluck( $actual['items'], 'user_id' ) );
	}

	private function assert_status_filter_returns_matching_student( string $type, string $method ): void {
		$factory     = new Sensei_Factory();
		$post        = $factory->post->create( array( 'post_type' => $type ) );
		$in_progress = $factory->user->create();
		$completed   = $factory->user->create();
		$this->seed_report_progress( $post, $in_progress, $type, 'in-progress', '2022-01-01 00:00:00' );
		$this->seed_report_progress( $post, $completed, $type, 'complete', '2022-01-01 00:00:00' );

		$actual = $this->get_report_service()->$method(
			array(
				'post_id' => $post,
				'type'    => 'sensei_' . $type . '_status',
				'status'  => 'complete',
			)
		);

		$this->assertSame( 1, $actual['total_count'] );
		$this->assertSame( array( $completed ), wp_list_pluck( $actual['items'], 'user_id' ) );
	}

	public function temporary_user_login_provider(): array {
		return array(
			'guest student'   => array( 'sensei_guest_student' ),
			'preview student' => array( 'sensei_preview_student' ),
		);
	}
}

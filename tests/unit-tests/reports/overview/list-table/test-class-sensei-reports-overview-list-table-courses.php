<?php

use Sensei\Internal\Services\Progress_Aggregation_Service_Interface;
use Sensei\Internal\Services\Progress_Query_Service_Factory;

/**
 * Tests for Sensei_Reports_Overview_List_Table_Courses class.
 *
 * @covers Sensei_Reports_Overview_List_Table_Courses
 */
class Sensei_Reports_Overview_List_Table_Courses_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	private static $initial_hook_suffix;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$this->maybe_enable_hpps_tables_repository();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->maybe_reset_hpps_repository();

		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGenerateReport_CourseWithStudentProgressCreated_ReturnsMatchingRowValues() {
		/* Arrange. */
		$course_id     = $this->factory->course->create( array( 'post_title' => 'Exported course' ) );
		$lesson_ids    = $this->factory->lesson->create_many(
			2,
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$activity_date = new DateTimeImmutable( '2022-01-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$user_ids      = $this->factory->user->create_many( 2 );
		$started_at    = new DateTimeImmutable( '2022-01-01 12:00:00', wp_timezone() );

		foreach ( $user_ids as $user_id ) {
			$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$course_progress->start( $started_at );
			Sensei()->course_progress_repository->save( $course_progress );
		}

		$this->seed_course_completion_with_dates( $course_id, $user_ids[0], '2022-01-01 12:00:00', '2022-01-01 12:00:00' );

		$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_ids[0], $user_ids[0] );
		$lesson_progress->complete( $activity_date );
		Sensei()->lesson_progress_repository->save( $lesson_progress );
		$this->set_lesson_progress_activity_date( $lesson_ids[0], $user_ids[0], $activity_date );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		/* Act. */
		$actual = $list_table->generate_report();

		/* Assert. */
		$expected = array(
			'title'              => 'Exported course',
			'last_activity'      => Sensei_Utils::format_last_activity_date( $activity_date->format( 'Y-m-d H:i:s' ) ),
			'enrolled'           => '2',
			'completions'        => '1',
			'completion_rate'    => '50%',
			// One lesson completion / ( two students * two lessons ) = 25%.
			'average_progress'   => '25%',
			'average_percent'    => 'N/A',
			// Starting and completing on the same local date counts as one inclusive day.
			'days_to_completion' => '1',
		);
		self::assertSame( array( $expected ), array_slice( $actual, 1 ) );
	}

	public function testGetColumns_TemporaryCompletionsGiven_UsesRegisteredCompletionDays(): void {
		/* Arrange. */
		$course_id = $this->create_course_with_completion_days();
		$table     = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		/* Act. */
		$actual = $table->get_columns();

		/* Assert. */
		// With one eligible course, the header is its four-day registered average.
		self::assertSame( 'Days to Completion (4)', $actual['days_to_completion'] );
	}

	public function testGetColumns_NoCompletionsFound_ReturnsMatchingArray() {
		$user_id = $this->factory->user->create();

		$course_id = $this->factory->course->create();

		/* Arrange. */
		$course        = $this->createMock( Sensei_Course::class );
		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $course_id ) );
		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_courses_average_grade' )->willReturn( 2 );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			$service,
			( new Progress_Query_Service_Factory( \Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		$list_table->total_items = 1;

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = array(
			'title'              => 'Course (1)',
			'last_activity'      => 'Last Activity',
			'enrolled'           => 'Enrolled (0)',
			'completions'        => 'Completions (0)',
			'completion_rate'    => 'Completion Rate (N/A)',
			'average_progress'   => 'Average Progress (0%)',
			'average_percent'    => 'Average Grade (2%)',
			'days_to_completion' => 'Days to Completion (0)',
		);

		self::assertSame( $expected, $actual );
	}

	public function testGetColumns_RegisteredAndTemporaryProgressCreated_ExcludesTemporaryCompletions() {
		/* Arrange. */
		$completed_user_id   = $this->factory->user->create();
		$in_progress_user_id = $this->factory->user->create();

		$course_id = $this->factory->course->create();

		$course_progress = Sensei()->course_progress_repository->create( $course_id, $completed_user_id );
		$course_progress->complete();
		Sensei()->course_progress_repository->save( $course_progress );

		$course_progress = Sensei()->course_progress_repository->create( $course_id, $in_progress_user_id );
		$course_progress->start();
		Sensei()->course_progress_repository->save( $course_progress );

		foreach ( array( 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$temporary_user_id = $this->factory->user->create( array( 'user_login' => $login ) );
			$course_progress   = Sensei()->course_progress_repository->create( $course_id, $temporary_user_id );
			$course_progress->complete();
			Sensei()->course_progress_repository->save( $course_progress );
		}

		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_total_average_progress' )->willReturn( 0.0 );
		$service->method( 'get_total_enrollments' )->willReturn( 2 );
		$service->method( 'get_courses_average_grade' )->willReturn( 0 );
		$service->method( 'get_average_days_to_completion' )->willReturn( 0.0 );

		$course = $this->createMock( Sensei_Course::class );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $course_id ) );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			$service,
			( new Progress_Query_Service_Factory( \Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = array(
			'completions'     => 'Completions (1)',
			'completion_rate' => 'Completion Rate (50%)',
		);

		self::assertSame( $expected, array_intersect_key( $actual, $expected ) );
	}

	public function testGetSortableColumns_WhenCalled_ReturnsMatchingArray() {
		/* Arrange. */
		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Courses::class ),
			$this->createMock( Progress_Aggregation_Service_Interface::class )
		);

		/* Act. */
		$actual = $list_table->get_sortable_columns();

		/* Assert. */
		$expected = array(
			'title'       => array( 'title', false ),
			'completions' => array( 'count_of_completions', false ),
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetRowData_CourseWithStudentProgressGiven_ReturnsMatchingArray() {
		/* Arrange. */
		$course_id     = $this->factory->course->create( array( 'post_title' => 'Course' ) );
		$lesson_id     = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$user_ids      = $this->factory->user->create_many( 2 );
		$started_at    = new DateTimeImmutable( '2022-01-01 12:00:00', wp_timezone() );
		$activity_date = new DateTimeImmutable( '2022-01-01 00:00:00', new DateTimeZone( 'UTC' ) );
		foreach ( $user_ids as $user_id ) {
			$progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$progress->start( $started_at );
			Sensei()->course_progress_repository->save( $progress );
		}
		$this->seed_course_completion_with_dates( $course_id, $user_ids[0], '2022-01-01 12:00:00', '2022-01-04 12:00:00' );
		$progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_ids[0] );
		$progress->complete( $activity_date );
		Sensei()->lesson_progress_repository->save( $progress );
		$this->set_lesson_progress_activity_date( $lesson_id, $user_ids[0], $activity_date );

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->willReturn( array( $lesson_id ) );
		$course->method( 'course_quizzes' )->willReturn( true );

		// Grade counts still use the legacy activity lookup; stub it without artificial comment grades.
		add_filter(
			'sensei_check_for_activity',
			static function ( $count, array $args ) {
				return 'sensei_lesson_status' === ( $args['type'] ?? '' ) && 'grade' === ( $args['meta_key'] ?? '' ) ? 1 : $count;
			},
			10,
			2
		);
		$service = $this->getMockBuilder( Sensei_Reports_Overview_Service_Courses::class )
			->onlyMethods( array( 'get_grade_sum_for_lessons' ) )->getMock();
		$service->method( 'get_grade_sum_for_lessons' )->willReturn( 50 );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			$service,
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);
		$method     = new ReflectionMethod( $list_table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );

		/* Act. */
		$list_table->prepare_items();
		$actual = $method->invoke( $list_table, $list_table->items[0] );

		/* Assert. */
		$course_url = esc_url(
			add_query_arg(
				array(
					'page'      => Sensei_Analysis::PAGE_SLUG,
					'course_id' => $course_id,
				),
				admin_url( 'admin.php' )
			)
		);
		// One of two students completed the course and its lesson: 50%; four inclusive completion days.
		// Grade total 50 / one graded lesson = 50%.
		$expected = array(
			'title'              => wp_kses_post( '<strong><a class="row-title" href="' . $course_url . '">Course</a></strong>' ),
			'last_activity'      => Sensei_Utils::format_last_activity_date( $activity_date->format( 'Y-m-d H:i:s' ) ),
			'enrolled'           => '2',
			'completions'        => '1',
			'completion_rate'    => '50%',
			'average_progress'   => '50%',
			'average_percent'    => '50%',
			'days_to_completion' => '4',
		);
		self::assertSame( $expected, $actual );
	}

	/**
	 * Table and CSV rows use the same eligible student population.
	 *
	 * @dataProvider completion_days_output_modes
	 */
	public function testGetRowData_TemporaryProgressGiven_UsesRegisteredProgress( bool $csv ): void {
		/* Arrange. */
		$course    = $this->factory->course->create();
		$temporary = $this->factory->course->create();
		$lessons   = $this->factory->lesson->create_many( 2, array( 'meta_input' => array( '_lesson_course' => $course ) ) );
		$user      = $this->factory->user->create();
		$guest     = $this->factory->user->create( array( 'user_login' => 'sensei_guest_student' ) );
		$this->seed_course_completion_with_dates( $course, $user, '2022-01-01 12:00:00', '2022-01-04 12:00:00' );
		foreach ( array( $course, $temporary ) as $id ) {
			$this->seed_course_completion_with_dates( $id, $guest, '2022-01-01 12:00:00', '2022-01-20 12:00:00' );
		}
		$activity = new DateTimeImmutable( '2022-01-04 12:00:00', new DateTimeZone( 'UTC' ) );
		foreach ( array(
			$user  => array( $lessons[0] ),
			$guest => $lessons,
		) as $id => $completed_lessons ) {
			$date = $user === $id ? $activity : $activity->modify( '+16 days' );
			foreach ( $completed_lessons as $lesson ) {
				$progress = Sensei()->lesson_progress_repository->create( $lesson, $id );
				$progress->complete( $date );
				Sensei()->lesson_progress_repository->save( $progress );
				$this->set_lesson_progress_activity_date( $lesson, $id, $date );
			}
		}
		$table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		/* Act. */
		if ( $csv ) {
			$rows = array_slice( $table->generate_report(), 1 );
		} else {
			$table->prepare_items();
			$method = new ReflectionMethod( $table, 'get_row_data' );
			Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );
			$rows = array();
			foreach ( $table->items as $item ) {
				$rows[] = $method->invoke( $table, $item );
			}
		}
		$actual = array();
		foreach ( $rows as $row ) {
			$actual[] = array_intersect_key( $row, array_flip( array( 'last_activity', 'enrolled', 'average_progress', 'days_to_completion' ) ) );
		}
		usort(
			$actual,
			static function ( $a, $b ) {
				return strcmp( $b['enrolled'], $a['enrolled'] );
			}
		);

		/* Assert. */
		// Only the registered student's four days, one enrollment, and one of two lessons count.
		self::assertSame(
			array(
				array(
					'last_activity'      => Sensei_Utils::format_last_activity_date( $activity->format( 'Y-m-d H:i:s' ) ),
					'enrolled'           => '1',
					'average_progress'   => '50%',
					'days_to_completion' => '4',
				),
				array(
					'last_activity'      => 'N/A',
					'enrolled'           => '0',
					'average_progress'   => 'N/A',
					'days_to_completion' => 'N/A',
				),
			),
			$actual
		);
	}

	public function testSearchButton_WhenCalled_ReturnsMatchingString() {
		/* Arrange. */
		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Courses::class ),
			$this->createMock( Progress_Aggregation_Service_Interface::class )
		);

		/* Act. */
		$actual = $list_table->search_button();

		/* Assert. */
		self::assertSame( 'Search Courses', $actual );
	}

	public static function completion_days_output_modes(): array {
		return array(
			'table' => array( false ),
			'csv'   => array( true ),
		);
	}

	/**
	 * Create contrasting registered, guest, and preview completion durations.
	 *
	 * @return int Course ID.
	 */
	private function create_course_with_completion_days(): int {
		$course_id = $this->factory->course->create();
		foreach ( array(
			'registered_student'     => 4,
			'sensei_guest_student'   => 20,
			'sensei_preview_student' => 30,
		) as $login => $days ) {
			$user_id = $this->factory->user->create( array( 'user_login' => $login ) );
			$this->seed_course_completion_with_dates( $course_id, $user_id, '2022-01-01 12:00:00', sprintf( '2022-01-%02d 12:00:00', $days ) );
		}

		return $course_id;
	}

	/**
	 * Store fixed local completion dates in the selected progress backend.
	 *
	 * @param int    $course_id Course ID.
	 * @param int    $user_id User ID.
	 * @param string $start Local start date.
	 * @param string $end Local completion date.
	 */
	private function seed_course_completion_with_dates( int $course_id, int $user_id, string $start, string $end ): void {
		$progress = Sensei()->course_progress_repository->get( $course_id, $user_id )
			?? Sensei()->course_progress_repository->create( $course_id, $user_id );
		$progress->start( new DateTimeImmutable( $start, wp_timezone() ) );
		$progress->complete( new DateTimeImmutable( $end, wp_timezone() ) );
		Sensei()->course_progress_repository->save( $progress );
		if ( ! self::is_hpps_tables_mode() ) {
			// Comments storage saves completion at the current time; restore the fixed fixture date.
			$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, 'complete' );
			wp_update_comment(
				array(
					'comment_ID'   => $comment_id,
					'comment_date' => $end,
				)
			);
		}
	}

	private function set_lesson_progress_activity_date( int $lesson_id, int $user_id, DateTimeInterface $activity_date ): void {
		global $wpdb;

		if ( self::is_hpps_tables_mode() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set deterministic fixture data in the backend under test.
			$wpdb->update(
				$wpdb->prefix . 'sensei_lms_progress',
				array( 'updated_at' => $activity_date->format( 'Y-m-d H:i:s' ) ),
				array(
					'post_id' => $lesson_id,
					'user_id' => $user_id,
					'type'    => 'lesson',
				)
			);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set deterministic fixture data in the backend under test.
		$wpdb->update(
			$wpdb->comments,
			array(
				'comment_date'     => $activity_date->format( 'Y-m-d H:i:s' ),
				'comment_date_gmt' => $activity_date->format( 'Y-m-d H:i:s' ),
			),
			array(
				'comment_post_ID' => $lesson_id,
				'user_id'         => $user_id,
				'comment_type'    => 'sensei_lesson_status',
			)
		);
	}
}

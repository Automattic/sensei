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

	public function testGenerateReport_CourseGiven_ReturnsPlainCourseTitle(): void {
		/* Arrange. */
		$item     = (object) array(
			'ID'                 => 123,
			'post_title'         => 'Exported course',
			'last_activity_date' => null,
		);
		$provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$provider->method( 'get_items' )->willReturn( array( $item ) );
		$provider->method( 'get_last_total_items' )->willReturn( 1 );
		$table = $this->getMockBuilder( Sensei_Reports_Overview_List_Table_Courses::class )
			->setConstructorArgs(
				array(
					$this->createMock( Sensei_Grading::class ),
					$this->createMock( Sensei_Course::class ),
					$provider,
					$this->createMock( Sensei_Reports_Overview_Service_Courses::class ),
					$this->createMock( Progress_Aggregation_Service_Interface::class ),
				)
			)
			->onlyMethods( array( 'get_columns' ) )->getMock();
		$table->method( 'get_columns' )->willReturn( array( 'title' => 'Course' ) );

		/* Act. */
		$actual = $table->generate_report();

		/* Assert. */
		self::assertSame( 'Exported course', $actual[1]['title'] );
	}

	/**
	 * Exclude guest and preview progress while retaining registered progress.
	 *
	 * @dataProvider temporary_user_logins
	 */
	public function testGenerateReport_TemporaryProgressGiven_UsesRegisteredProgress( string $temporary_user_login ): void {
		/* Arrange. */
		// Registered: four completion days and half the lessons; temporary user: twenty days and all lessons.
		// A second course has only temporary progress and must show no eligible progress.
		$this->seed_registered_and_temporary_progress( $temporary_user_login );
		$table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		/* Act. */
		$actual = $table->generate_report();

		/* Assert. */
		// Only the registered student's four days, one enrollment, and one of two lessons count.
		$expected = array(
			array(
				'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-04 12:00:00' ),
				'enrolled'           => '1',
				'average_progress'   => '50%',
				'days_to_completion' => '4',
			),
			// The temporary-only course remains visible, with no eligible enrollment or progress.
			array(
				'last_activity'      => 'N/A',
				'enrolled'           => '0',
				'average_progress'   => 'N/A',
				'days_to_completion' => 'N/A',
			),
		);

		self::assertSame( $expected, $this->get_progress_fields_from_rows( array_slice( $actual, 1 ) ) );
	}

	public function testGetColumns_TemporaryCompletionsGiven_RoundsRegisteredCompletionDaysUp(): void {
		/* Arrange. */
		// First course: four registered days, with longer guest and preview completions excluded.
		$this->create_course_with_completion_days();
		// Second course: one registered day makes the overall average fractional.
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();
		$this->seed_course_completion_with_dates( $course_id, $user_id, '2022-01-01 12:00:00', '2022-01-01 12:00:00' );
		$table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		/* Act. */
		$actual = $table->get_columns();

		/* Assert. */
		// The eligible course averages are 4 and 1: ceil((4 + 1) / 2) = 3.
		self::assertSame( 'Days to Completion (3)', $actual['days_to_completion'] );
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

	public function testGetRowData_CourseAndAggregateValuesGiven_ReturnsFormattedRow(): void {
		/* Arrange. */
		$item   = (object) array(
			'ID'                 => 123,
			'post_title'         => 'Course',
			'last_activity_date' => '2022-01-01 00:00:00',
		);
		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->willReturn( array( 456 ) );
		$course->method( 'course_quizzes' )->willReturn( true );
		$provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$provider->method( 'get_items' )->willReturn( array( $item ) );
		$provider->method( 'get_last_total_items' )->willReturn( 1 );
		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_grade_sum_for_lessons' )->willReturn( 50 );
		$service->method( 'get_total_enrollments' )->willReturn( 2 );
		$service->method( 'get_average_progress_by_course' )->willReturn( array( 123 => 50.0 ) );
		$service->method( 'get_average_days_to_completion_by_course' )->willReturn( array( 123 => 4.0 ) );
		// Supply one course completion and one graded lesson without storage fixtures.
		add_filter(
			'sensei_check_for_activity',
			static function () {
				return 1;
			}
		);
		$table  = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$provider,
			$service,
			$this->createMock( Progress_Aggregation_Service_Interface::class )
		);
		$method = new ReflectionMethod( $table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );
		$table->prepare_items();

		/* Act. */
		$actual = $method->invoke( $table, $item );

		/* Assert. */
		$course_url = esc_url(
			add_query_arg(
				array(
					'page'      => Sensei_Analysis::PAGE_SLUG,
					'course_id' => 123,
				),
				admin_url( 'admin.php' )
			)
		);
		$expected   = array(
			'title'              => '<strong><a class="row-title" href="' . $course_url . '">Course</a></strong>',
			'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-01 00:00:00' ),
			'enrolled'           => '2',
			'completions'        => '1',
			// One supplied completion / two enrollments = 50%.
			'completion_rate'    => '50%',
			'average_progress'   => '50%',
			// Supplied grade total 50 / one graded lesson = 50%.
			'average_percent'    => '50%',
			'days_to_completion' => '4',
		);
		self::assertSame( $expected, $actual );
	}

	/**
	 * Exclude guest and preview progress while retaining registered progress.
	 *
	 * @dataProvider temporary_user_logins
	 */
	public function testGetRowData_TemporaryProgressGiven_UsesRegisteredProgress( string $temporary_user_login ): void {
		/* Arrange. */
		// Registered: four completion days and half the lessons; temporary user: twenty days and all lessons.
		// A second course has only temporary progress and must show no eligible progress.
		$this->seed_registered_and_temporary_progress( $temporary_user_login );
		$table  = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);
		$method = new ReflectionMethod( $table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );
		$table->prepare_items();

		/* Act. */
		$rows = array();
		foreach ( $table->items as $item ) {
			$rows[] = $method->invoke( $table, $item );
		}

		/* Assert. */
		// Only the registered student's four days, one enrollment, and one of two lessons count.
		$expected = array(
			array(
				'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-04 12:00:00' ),
				'enrolled'           => '1',
				'average_progress'   => '50%',
				'days_to_completion' => '4',
			),
			// The temporary-only course remains visible, with no eligible enrollment or progress.
			array(
				'last_activity'      => 'N/A',
				'enrolled'           => '0',
				'average_progress'   => 'N/A',
				'days_to_completion' => 'N/A',
			),
		);

		self::assertSame( $expected, $this->get_progress_fields_from_rows( $rows ) );
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

	public static function temporary_user_logins(): array {
		return array(
			'guest'   => array( 'sensei_guest_student' ),
			'preview' => array( 'sensei_preview_student' ),
		);
	}

	/**
	 * Seed registered and temporary progress in one course, and temporary-only progress in another.
	 *
	 * @param string $temporary_user_login Guest or preview login to exclude.
	 */
	private function seed_registered_and_temporary_progress( string $temporary_user_login ): void {
		// One course has registered and temporary progress; the other has only temporary progress.
		$registered_course_id     = $this->factory->course->create();
		$temporary_only_course_id = $this->factory->course->create();
		$lesson_ids               = $this->factory->lesson->create_many( 2, array( 'meta_input' => array( '_lesson_course' => $registered_course_id ) ) );
		$registered_user_id       = $this->factory->user->create();
		$temporary_user_id        = $this->factory->user->create( array( 'user_login' => $temporary_user_login ) );

		// Four registered completion days contrast with twenty excluded temporary-user days.
		$this->seed_course_completion_with_dates( $registered_course_id, $registered_user_id, '2022-01-01 12:00:00', '2022-01-04 12:00:00' );
		foreach ( array( $registered_course_id, $temporary_only_course_id ) as $course_id ) {
			$this->seed_course_completion_with_dates( $course_id, $temporary_user_id, '2022-01-01 12:00:00', '2022-01-20 12:00:00' );
		}

		// The registered student completes one of two lessons on January 4.
		$registered_activity = new DateTimeImmutable( '2022-01-04 12:00:00', new DateTimeZone( 'UTC' ) );
		$progress            = Sensei()->lesson_progress_repository->create( $lesson_ids[0], $registered_user_id );
		$progress->complete( $registered_activity );
		Sensei()->lesson_progress_repository->save( $progress );
		$this->set_lesson_progress_activity_date( $lesson_ids[0], $registered_user_id, $registered_activity );

		// The temporary user completes both lessons later; these must affect neither progress nor last activity.
		$temporary_activity = new DateTimeImmutable( '2022-01-20 12:00:00', new DateTimeZone( 'UTC' ) );
		foreach ( $lesson_ids as $lesson_id ) {
			$progress = Sensei()->lesson_progress_repository->create( $lesson_id, $temporary_user_id );
			$progress->complete( $temporary_activity );
			Sensei()->lesson_progress_repository->save( $progress );
			$this->set_lesson_progress_activity_date( $lesson_id, $temporary_user_id, $temporary_activity );
		}
	}
	/**
	 * Select progress fields and order rows consistently for table and CSV output.
	 *
	 * @param array $rows Report rows.
	 * @return array Eligible progress fields.
	 */
	private function get_progress_fields_from_rows( array $rows ): array {
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

		return $actual;
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

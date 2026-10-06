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

		foreach ( $user_ids as $user_id ) {
			$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$course_progress->start( $activity_date );
			Sensei()->course_progress_repository->save( $course_progress );
		}

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
			'completions'        => '0',
			'completion_rate'    => '0%',
			// One lesson completion / ( two students * two lessons ) = 25%.
			'average_progress'   => '25%',
			'average_percent'    => 'N/A',
			'days_to_completion' => 'N/A',
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

	public function testGetRowData_CourseWithGradedQuizGiven_ReturnsMatchingArray() {
		/* Arrange. */
		$course_id        = $this->factory->course->create();
		$lesson_id        = $this->factory->lesson->create();
		$user_id          = $this->factory->user->create();
		$lesson_status_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $lesson_id,
				'comment_type'     => 'sensei_lesson_status',
				'comment_approved' => 'graded',
				'user_id'          => $user_id,
			)
		);
		update_comment_meta( $lesson_status_id, 'grade', 50 );
		$item = (object) array(
			'ID'                   => $course_id,
			'post_title'           => 'Course',
			'last_activity_date'   => null,
			'count_of_completions' => 0,
			'days_to_completion'   => 0,
		);

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->willReturn( array( $lesson_id ) );
		$course->method( 'course_quizzes' )->willReturn( true );

		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_grade_sum_for_lessons' )->willReturn( 50 );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$service,
			$this->createMock( Progress_Aggregation_Service_Interface::class )
		);
		$method     = new ReflectionMethod( $list_table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );

		/* Act. */
		$actual = $method->invoke( $list_table, $item );

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
		$expected   = array(
			'title'              => wp_kses_post( '<strong><a class="row-title" href="' . $course_url . '">Course</a></strong>' ),
			'last_activity'      => 'N/A',
			'enrolled'           => '0',
			'completions'        => '0',
			'completion_rate'    => 'N/A',
			'average_progress'   => 'N/A',
			'average_percent'    => '50%',
			'days_to_completion' => 'N/A',
		);
		self::assertSame( $expected, $actual );
	}

	public function testGetRowData_RegisteredAndTemporaryProgressCreated_ExcludesTemporaryUsersFromRowValues() {
		/* Arrange. */
		$course_id           = $this->factory->course->create();
		$lesson_id           = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$registered_activity = new DateTimeImmutable( '2022-01-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$temporary_activity  = new DateTimeImmutable( '2022-01-02 00:00:00', new DateTimeZone( 'UTC' ) );
		$users               = array(
			'registered_complete' => $this->factory->user->create( array( 'user_login' => 'registered_complete' ) ),
			'registered_started'  => $this->factory->user->create( array( 'user_login' => 'registered_started' ) ),
			'guest_complete'      => $this->factory->user->create( array( 'user_login' => 'sensei_guest_complete' ) ),
			'preview_complete'    => $this->factory->user->create( array( 'user_login' => 'sensei_preview_complete' ) ),
		);

		foreach ( $users as $user_id ) {
			$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$course_progress->start( $registered_activity );
			Sensei()->course_progress_repository->save( $course_progress );
		}

		$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $users['registered_complete'] );
		$lesson_progress->complete( $registered_activity );
		Sensei()->lesson_progress_repository->save( $lesson_progress );
		$this->set_lesson_progress_activity_date( $lesson_id, $users['registered_complete'], $registered_activity );

		$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $users['registered_started'] );
		$lesson_progress->start( $registered_activity );
		Sensei()->lesson_progress_repository->save( $lesson_progress );

		foreach ( array( 'guest_complete', 'preview_complete' ) as $temporary_user ) {
			$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $users[ $temporary_user ] );
			$lesson_progress->complete( $temporary_activity );
			Sensei()->lesson_progress_repository->save( $lesson_progress );
			$this->set_lesson_progress_activity_date( $lesson_id, $users[ $temporary_user ], $temporary_activity );
		}

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			Sensei()->course,
			$data_provider,
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);
		$method     = new ReflectionMethod( $list_table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );

		/* Act. */
		$list_table->prepare_items();
		$item   = $list_table->items[0];
		$actual = $method->invoke( $list_table, $item );

		/* Assert. */
		$expected = array(
			'last_activity'    => Sensei_Utils::format_last_activity_date( $registered_activity->format( 'Y-m-d H:i:s' ) ),
			'enrolled'         => '2',
			// One completion / ( two registered students * one lesson ) = 50%.
			'average_progress' => '50%',
		);
		self::assertSame( $expected, array_intersect_key( $actual, $expected ) );
	}

	/**
	 * Completion days use the eligible population in both output modes.
	 *
	 * @dataProvider completion_days_output_modes
	 */
	public function testGetRowData_TemporaryCompletionsGiven_UsesRegisteredDaysInTableAndCsv( bool $csv ): void {
		/* Arrange. */
		$course_id         = $this->create_course_with_completion_days();
		$provider          = new Sensei_Reports_Overview_Data_Provider_Courses();
		$items             = $provider->get_items(
			array(
				'number'  => 10,
				'offset'  => 0,
				'orderby' => '',
				'order'   => 'ASC',
			)
		);
		$table             = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			$provider,
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);
		$table->csv_output = $csv;
		$method            = new ReflectionMethod( $table, 'get_row_data' );
		$method->setAccessible( true );

		/* Act. */
		$actual = $method->invoke( $table, $items[0] );

		/* Assert. */
		// The eligible completion takes four inclusive days; 20/30-day temporary completions are excluded.
		self::assertSame( '4', $actual['days_to_completion'] );
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
			$user_id  = $this->factory->user->create( array( 'user_login' => $login ) );
			$progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$progress->start( new DateTimeImmutable( '2022-01-01 12:00:00', wp_timezone() ) );
			$progress->complete( new DateTimeImmutable( sprintf( '2022-01-%02d 12:00:00', $days ), wp_timezone() ) );
			Sensei()->course_progress_repository->save( $progress );
			if ( ! self::is_hpps_tables_mode() ) {
				$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, 'complete' );
				wp_update_comment(
					array(
						'comment_ID'   => $comment_id,
						'comment_date' => sprintf( '2022-01-%02d 12:00:00', $days ),
					)
				);
				update_comment_meta( $comment_id, 'start', '2022-01-01 12:00:00' );
			}
		}

		return $course_id;
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

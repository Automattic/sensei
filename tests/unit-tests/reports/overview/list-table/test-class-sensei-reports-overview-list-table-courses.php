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

	/**
	 * CSV exports use the plain course title.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::generate_report
	 */
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
	 * Last Activity date selection is shared by translated rows, CSV, and headers.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::generate_report
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_row_data
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
	public function testGenerateReport_TranslatedActivityDateFiltered_ReturnsMatchingRowAndTotals(): void {
		$original    = $this->factory->course->create();
		$translation = $this->factory->course->create();
		$unmatched   = $this->factory->course->create();
		$user        = $this->factory->user->create();
		$date        = '2022-01-02 12:00:00';
		$activities  = array(
			$original  => $date,
			$unmatched => '2022-01-01 12:00:00',
		);
		foreach ( $activities as $course => $activity ) {
			$progress = Sensei()->course_progress_repository->create( $course, $user );
			$progress->start();
			$progress->complete();
			Sensei()->course_progress_repository->save( $progress );
			$lesson   = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course ) ) );
			$progress = Sensei()->lesson_progress_repository->create( $lesson, $user );
			$progress->start();
			$progress->complete();
			Sensei()->lesson_progress_repository->save( $progress );
			$this->set_lesson_progress_activity_date( $lesson, $user, new DateTimeImmutable( $activity, new DateTimeZone( 'UTC' ) ) );
		}
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $id ) use ( $original, $translation ) {
				return $translation === $id ? $original : $id;
			}
		);
		// Select the secondary-language course and a course outside the date range.
		add_filter(
			'sensei_analysis_overview_filter_courses',
			static function ( $args ) use ( $translation, $unmatched ) {
				$args['post__in'] = array( $translation, $unmatched );
				return $args;
			}
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve request state for this report test.
		$initial_get = $_GET;

		$_GET['start_date'] = '2022-01-02';
		$_GET['end_date']   = '2022-01-02';
		$_GET['timezone']   = 'UTC';

		$table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			new Sensei_Reports_Overview_Data_Provider_Courses(),
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		try {
			$table->prepare_items();
			$columns = $table->get_columns();
			$items   = $table->items;
			$csv     = $table->generate_report();
		} finally {
			$_GET = $initial_get;
		}

		self::assertSame( array( $translation ), wp_list_pluck( $items, 'ID' ), 'Rows should retain only the matching translated course ID.' );
		self::assertSame( 'Course (1)', $columns['title'], 'The header should count the selected translation.' );
		self::assertSame( 'Enrolled (1)', $columns['enrolled'], 'Header enrollments should use only the selected course.' );
		self::assertSame( 'Completions (1)', $columns['completions'], 'Header completions should use only the selected course.' );
		self::assertCount( 2, $csv, 'CSV should contain a header and the selected course.' );
		self::assertSame( Sensei_Utils::format_last_activity_date( $date ), $csv[1]['last_activity'], 'CSV should display the original lesson activity.' );
	}

	/**
	 * Header, row, and CSV grades use the same registered attempts in either language.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::generate_report
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_row_data
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 * @dataProvider grade_report_courses
	 */
	public function testGenerateReport_RegisteredAndTemporaryGradesGiven_MatchesDisplayedGrade( bool $translated ): void {
		list( $table, $item ) = $this->create_graded_course_table( $translated );
		$method               = new ReflectionMethod( $table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );
		$table->prepare_items();

		$columns = $table->get_columns();
		$row     = $method->invoke( $table, $item );
		$csv     = $table->generate_report();

		self::assertSame( 'Average Grade (40%)', $columns['average_percent'], 'The header must use only registered attempts.' );
		self::assertSame( '40%', $row['average_percent'], 'The displayed grade must use only registered attempts.' );
		self::assertSame( '40%', $csv[1]['average_percent'], 'CSV must use the same grade inputs as the row.' );
	}

	/**
	 * Courses without quiz questions omit historical grades consistently from rows and the header.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::generate_report
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
	public function testGenerateReport_QuizQuestionsRemovedGiven_MatchesHeaderEligibility(): void {
		list( $table, $item ) = $this->create_graded_course_table( false );
		$lessons              = Sensei()->course->course_lessons( $item->ID, 'any', 'ids' );
		update_post_meta( $lessons[0], '_quiz_has_questions', false );

		$columns = $table->get_columns();
		$csv     = $table->generate_report();

		self::assertSame( 'Average Grade (0%)', $columns['average_percent'], 'The header should omit courses whose rows have no eligible quiz.' );
		self::assertSame( 'N/A', $csv[1]['average_percent'], 'The course row should preserve its no-quiz result despite historical grades.' );
	}

	/**
	 * Exclude guest and preview progress while retaining registered progress.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::generate_report
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
				'completions'        => '1',
				'completion_rate'    => '100%',
				'average_progress'   => '50%',
				'days_to_completion' => '4',
			),
			// The temporary-only course remains visible, with no eligible enrollment or progress.
			array(
				'last_activity'      => 'N/A',
				'enrolled'           => '0',
				'completions'        => '0',
				'completion_rate'    => 'N/A',
				'average_progress'   => 'N/A',
				'days_to_completion' => 'N/A',
			),
		);

		self::assertSame( $expected, $this->get_progress_fields_from_rows( array_slice( $actual, 1 ) ) );
	}

	/**
	 * CSV rows use original course progress for translated courses and exclude temporary users.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::generate_report
	 */
	public function testGenerateReport_TranslatedCourseGiven_UsesOriginalRegisteredCompletions(): void {
		/* Arrange. */
		list( $table ) = $this->create_translated_course_table();

		/* Act. */
		$actual = $table->generate_report();

		/* Assert. */
		$expected = array(
			'completions'     => '1',
			'completion_rate' => '50%',
		);
		self::assertSame( $expected, array_intersect_key( $actual[1], $expected ) );
	}

	/**
	 * Column headers use original course progress for translated courses and exclude temporary users.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
	public function testGetColumns_TranslatedCourseGiven_UsesOriginalRegisteredCompletions(): void {
		/* Arrange. */
		list( $table ) = $this->create_translated_course_table();

		/* Act. */
		$actual = $table->get_columns();

		/* Assert. */
		self::assertSame( 'Enrolled (2)', $actual['enrolled'], 'The header should count registered enrollments.' );
		self::assertSame( 'Completions (1)', $actual['completions'], 'The header should count the original course completion.' );
		self::assertSame( 'Completion Rate (50%)', $actual['completion_rate'], 'The header rate should use registered completions and enrollments.' );
	}

	/**
	 * The grade header rounds up only after averaging the unrounded course grades.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
	public function testGetColumns_FractionalCourseGradesGiven_RoundsOverallGradeUp(): void {
		$provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$provider->method( 'get_items' )->willReturn( array( 11, 22 ) );
		$service = $this->getMockBuilder( Sensei_Reports_Overview_Service_Courses::class )
			->onlyMethods( array( 'get_grade_totals_by_course', 'get_total_enrollments', 'get_total_average_progress', 'get_average_days_to_completion' ) )
			->getMock();
		$service->method( 'get_grade_totals_by_course' )->willReturn(
			array(
				11 => array(
					'count' => 3,
					'sum'   => 100.0,
				),
				22 => array(
					'count' => 1,
					'sum'   => 66.67,
				),
			)
		);
		$table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$provider,
			$service,
			$this->createMock( Progress_Aggregation_Service_Interface::class )
		);

		$actual = $table->get_columns();

		self::assertSame( 'Average Grade (51%)', $actual['average_percent'] );
	}

	/**
	 * Completion days in the header include only registered students and round up.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
	public function testGetColumns_TemporaryCompletionsGiven_RoundsRegisteredCompletionDaysUp(): void {
		/* Arrange. */
		// First course: four registered days, with longer guest and preview completions excluded.
		$first_course_id = $this->factory->course->create();
		foreach ( array(
			'registered_student'     => 4,
			'sensei_guest_student'   => 20,
			'sensei_preview_student' => 30,
		) as $login => $days ) {
			$user_id = $this->factory->user->create( array( 'user_login' => $login ) );
			$this->seed_course_completion_with_dates( $first_course_id, $user_id, '2022-01-01 12:00:00', sprintf( '2022-01-%02d 12:00:00', $days ) );
		}

		// Second course: one registered day makes the overall average fractional.
		$second_course_id = $this->factory->course->create();
		$user_id          = $this->factory->user->create();
		$this->seed_course_completion_with_dates( $second_course_id, $user_id, '2022-01-01 12:00:00', '2022-01-01 12:00:00' );

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

	/**
	 * Column headers show zero or N/A when no course completions exist.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
	public function testGetColumns_NoCompletionsFound_ReturnsHeadersWithZeroCompletions() {
		/* Arrange. */
		$course_id     = $this->factory->course->create();
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

	/**
	 * Completion headers exclude guest and preview progress.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_columns
	 */
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

	/**
	 * Only course titles can be sorted.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_sortable_columns
	 */
	public function testGetSortableColumns_DefaultColumnsRequested_ReturnsOnlyTitle() {
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
		$expected = array( 'title' => array( 'title', false ) );
		self::assertSame( $expected, $actual );
	}

	/**
	 * Course rows format the supplied aggregate values.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_row_data
	 * @dataProvider row_average_grades
	 */
	public function testGetRowData_CourseAndAggregateValuesGiven_ReturnsFormattedRow( array $course_grades, string $average_grade ): void {
		/* Arrange. */
		$item     = (object) array(
			'ID'                 => 123,
			'post_title'         => 'Course',
			'last_activity_date' => '2022-01-01 00:00:00',
		);
		$course   = $this->createMock( Sensei_Course::class );
		$provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$provider->method( 'get_items' )->willReturn( array( $item ) );
		$provider->method( 'get_last_total_items' )->willReturn( 1 );
		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_average_grades_by_course' )->willReturn( $course_grades );
		$service->method( 'get_total_enrollments' )->willReturn( 2 );
		$service->method( 'get_completion_counts_by_course' )->willReturn( array( 123 => 1 ) );
		$service->method( 'get_average_progress_by_course' )->willReturn( array( 123 => 50.0 ) );
		$service->method( 'get_average_days_to_completion_by_course' )->willReturn( array( 123 => 4.0 ) );
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
			'title'              => wp_kses_post( '<strong><a class="row-title" href="' . $course_url . '">Course</a></strong>' ),
			'last_activity'      => Sensei_Utils::format_last_activity_date( '2022-01-01 00:00:00' ),
			'enrolled'           => '2',
			'completions'        => '1',
			// One supplied completion / two enrollments = 50%.
			'completion_rate'    => '50%',
			'average_progress'   => '50%',
			'average_percent'    => $average_grade,
			'days_to_completion' => '4',
		);
		self::assertSame( $expected, $actual );
	}

	/**
	 * Report rows use original course progress for translated courses and exclude temporary users.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::get_row_data
	 */
	public function testGetRowData_TranslatedCourseGiven_UsesOriginalRegisteredCompletions(): void {
		/* Arrange. */
		list( $table, $item ) = $this->create_translated_course_table();
		$method               = new ReflectionMethod( $table, 'get_row_data' );
		Sensei_Unit_Tests_Bootstrap::make_reflection_accessible( $method );
		$table->prepare_items();

		/* Act. */
		$actual = $method->invoke( $table, $item );

		/* Assert. */
		$expected = array(
			'completions'     => '1',
			'completion_rate' => '50%',
		);
		self::assertSame( $expected, array_intersect_key( $actual, $expected ) );
	}

	/**
	 * Course reports use a course-specific search label.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Courses::search_button
	 */
	public function testSearchButton_CourseTableCreated_ReturnsCourseSearchLabel() {
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

	public static function row_average_grades(): array {
		return array(
			'graded'     => array( array( 123 => 50.0 ), '50%' ),
			'fractional' => array( array( 123 => 20.125 ), '20.13%' ),
			'zero grade' => array( array( 123 => 0.0 ), '0%' ),
			'no grades'  => array( array(), 'N/A' ),
		);
	}

	public static function grade_report_courses(): array {
		return array(
			'original'   => array( false ),
			'translated' => array( true ),
		);
	}

	/**
	 * Create one graded quiz and a report selecting its original or translated course.
	 *
	 * @param bool $translated Whether to select the translated course.
	 * @return array Table and requested course post.
	 */
	private function create_graded_course_table( bool $translated ): array {
		$created  = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);
		$course   = $created['course_id'];
		$lesson   = $created['lesson_ids'][0];
		$quiz     = $created['quiz_ids'][0];
		$question = Sensei()->quiz->get_questions( $quiz )[0]->ID;
		foreach ( array(
			'registered'             => 40,
			'sensei_guest_student'   => 80,
			'sensei_preview_student' => 100,
		) as $login => $grade ) {
			$user = $this->factory->user->create( array( 'user_login' => $login ) );
			Sensei()->lesson_progress_repository->create( $lesson, $user );
			$progress = Sensei()->quiz_progress_repository->create( $quiz, $user );
			$progress->grade();
			Sensei()->quiz_progress_repository->save( $progress );
			$submission = Sensei()->quiz_submission_repository->create( $quiz, $user, $grade );
			Sensei()->quiz_answer_repository->create( $submission, $question, 'Answer' );
		}
		$requested = $translated ? $this->factory->course->create() : $course;
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $id ) use ( $course, $requested ) {
				return $requested === $id ? $course : $id;
			}
		);
		// Model the current-language query restriction that hides the original lessons.
		add_filter(
			'posts_where',
			static function ( $where, $query ) use ( $translated ) {
				return $translated && 'lesson' === $query->get( 'post_type' ) ? $where . ' AND 1=0' : $where;
			},
			10,
			2
		);
		$item                     = get_post( $requested );
		$item->last_activity_date = null;
		$provider                 = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$provider->method( 'get_items' )->willReturnCallback(
			static function ( $filters ) use ( $item ) {
				return 'ids' === ( $filters['fields'] ?? '' ) ? array( $item->ID ) : array( $item );
			}
		);
		$provider->method( 'get_last_total_items' )->willReturn( 1 );
		$table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			$provider,
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		return array( $table, $item );
	}

	/**
	 * Create a translated course with one completed and one active registered student, plus guest and preview completions.
	 *
	 * @return array Report table and translated course post.
	 */
	private function create_translated_course_table(): array {
		$original    = $this->factory->course->create();
		$translation = $this->factory->course->create();
		foreach ( array( 'registered_complete', 'registered_started', 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user     = $this->factory->user->create( array( 'user_login' => $login ) );
			$progress = Sensei()->course_progress_repository->create( $original, $user );
			$progress->start();
			if ( 'registered_started' !== $login ) {
				$progress->complete();
			}
			Sensei()->course_progress_repository->save( $progress );
		}
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $id ) use ( $original, $translation ) {
				return $translation === $id ? $original : $id;
			}
		);
		$item                     = get_post( $translation );
		$item->last_activity_date = null;
		$provider                 = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$provider->method( 'get_items' )->willReturnCallback(
			static function ( $filters ) use ( $item ) {
				return 'ids' === ( $filters['fields'] ?? '' ) ? array( $item->ID ) : array( $item );
			}
		);
		$provider->method( 'get_last_total_items' )->willReturn( 1 );
		$table = new Sensei_Reports_Overview_List_Table_Courses(
			Sensei()->grading,
			Sensei()->course,
			$provider,
			new Sensei_Reports_Overview_Service_Courses(),
			( new Progress_Query_Service_Factory( Sensei()->progress_storage_configuration ) )->create_aggregation_service()
		);

		return array( $table, $item );
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
			$actual[] = array_intersect_key( $row, array_flip( array( 'last_activity', 'enrolled', 'completions', 'completion_rate', 'average_progress', 'days_to_completion' ) ) );
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

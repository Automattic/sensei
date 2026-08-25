<?php

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
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->factory->tearDown();
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
			$service
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

	public function testGetColumns_CompletionsFound_ReturnsMatchingArray() {
		/* Arrange. */
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$user_id = $this->factory->user->create();

		$course_id = $this->factory->course->create();

		$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
		$course_progress->complete();
		Sensei()->course_progress_repository->save( $course_progress );

		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_courses_average_grade' )->willReturn( 2 );
		$service->method( 'get_total_enrollments' )->willReturn( 4 );

		$course = $this->createMock( Sensei_Course::class );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $course_id ) );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			$service
		);

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = array(
			'title'              => 'Course (1)',
			'last_activity'      => 'Last Activity',
			'enrolled'           => 'Enrolled (4)',
			'completions'        => 'Completions (1)',
			'completion_rate'    => 'Completion Rate (25%)',
			'average_progress'   => 'Average Progress (0%)',
			'average_percent'    => 'Average Grade (2%)',
			'days_to_completion' => 'Days to Completion (0)',
		);

		self::assertSame( $expected, $actual );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	public function testGetSortableColumns_WhenCalled_ReturnsMatchingArray() {
		/* Arrange. */
		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Courses::class )
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

	public function testSearchButton_WhenCalled_ReturnsMatchingString() {
		/* Arrange. */
		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Courses::class )
		);

		/* Act. */
		$actual = $list_table->search_button();

		/* Assert. */
		self::assertSame( 'Search Courses', $actual );
	}

	public function testGetRowData_WhenCalledAfterPrepareItems_UsesPrimedGradeTotals() {
		/* Arrange. */
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$course_id = $this->factory->course->create();
		$user_1    = $this->factory->user->create();
		$user_2    = $this->factory->user->create();

		$lesson_id = $this->grade_lesson_for_course( $course_id, $user_1, 80 );
		$this->grade_lesson_for_course( $course_id, $user_2, 60, $lesson_id );

		$item                       = new stdClass();
		$item->ID                   = $course_id;
		$item->post_title           = get_the_title( $course_id );
		$item->count_of_completions = 0;
		$item->days_to_completion   = 0;
		$item->last_activity_date   = null;

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->willReturn( array( $lesson_id ) );
		$course->method( 'course_quizzes' )->willReturn( true );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $item ) );
		$data_provider->method( 'get_last_total_items' )->willReturn( 1 );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			$this->createMock( Sensei_Reports_Overview_Service_Courses::class )
		);

		/* Act. */
		$list_table->prepare_items();
		$row = $this->invoke_get_row_data( $list_table, $item );

		/* Assert. */
		self::assertSame( '70%', $row['average_percent'] );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	public function testGetRowData_WhenCalledAfterPrepareItems_UsesPrimedCompletionsAndAverageProgress() {
		/* Arrange. */
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$course_id = $this->factory->course->create();
		$user_1    = $this->factory->user->create();
		$user_2    = $this->factory->user->create();

		$lesson_1 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);
		$lesson_2 = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		// Enroll and complete the course for user_1, and complete both lessons.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_1, true );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_1, true );
		Sensei_Utils::update_course_status( $user_1, $course_id, 'complete' );

		// Enroll user_2 in the course, but don't complete any lessons or the course.
		Sensei_Utils::sensei_start_lesson( $lesson_1, $user_2 );
		Sensei_Utils::sensei_start_lesson( $lesson_2, $user_2 );
		Sensei_Utils::update_course_status( $user_2, $course_id, 'in-progress' );

		$item                       = new stdClass();
		$item->ID                   = $course_id;
		$item->post_title           = get_the_title( $course_id );
		$item->count_of_completions = 0;
		$item->days_to_completion   = 0;
		$item->last_activity_date   = null;

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->willReturn( array( $lesson_1, $lesson_2 ) );
		$course->method( 'course_quizzes' )->willReturn( false );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $item ) );
		$data_provider->method( 'get_last_total_items' )->willReturn( 1 );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			new Sensei_Reports_Overview_Service_Courses()
		);

		/* Act. */
		$list_table->prepare_items();
		$row = $this->invoke_get_row_data( $list_table, $item );

		/* Assert. */
		self::assertSame( '1', $row['completions'], 'One student completed the course.' );
		self::assertSame( '50%', $row['completion_rate'], '1 of 2 enrolled students completed the course.' );
		self::assertSame( '50%', $row['average_progress'], '2 of 4 possible lesson completions were made.' );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	public function testGetRowData_CourseWithNoEnrolledStudents_ReturnsNAForAverageProgress() {
		/* Arrange. */
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$course_id = $this->factory->course->create();
		$lesson    = $this->factory->lesson->create(
			array( 'meta_input' => array( '_lesson_course' => $course_id ) )
		);

		$item                       = new stdClass();
		$item->ID                   = $course_id;
		$item->post_title           = get_the_title( $course_id );
		$item->count_of_completions = 0;
		$item->days_to_completion   = 0;
		$item->last_activity_date   = null;

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'course_lessons' )->willReturn( array( $lesson ) );
		$course->method( 'course_quizzes' )->willReturn( false );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $item ) );
		$data_provider->method( 'get_last_total_items' )->willReturn( 1 );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			new Sensei_Reports_Overview_Service_Courses()
		);

		/* Act. */
		$list_table->prepare_items();
		$row = $this->invoke_get_row_data( $list_table, $item );

		/* Assert. */
		self::assertSame( 'N/A', $row['average_progress'], 'A course with no enrolled students has no computable average progress.' );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	/**
	 * Seed a graded lesson (with a quiz submission and grade) belonging to a
	 * course, for a user, via the progress/quiz submission repositories so
	 * both storage modes have data.
	 *
	 * @param int      $course_id Course ID.
	 * @param int      $user_id   User ID.
	 * @param int      $grade     Grade to assign.
	 * @param int|null $lesson_id Optional. Reuse an existing lesson/quiz.
	 *
	 * @return int The lesson ID.
	 */
	private function grade_lesson_for_course( int $course_id, int $user_id, int $grade, ?int $lesson_id = null ): int {
		if ( null === $lesson_id ) {
			$lesson_id = $this->factory->lesson->create(
				array(
					'meta_input' => array(
						'_lesson_course' => $course_id,
					),
				)
			);
			$quiz_id   = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
			update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		} else {
			$quiz_id = get_post_meta( $lesson_id, '_lesson_quiz', true );
		}

		Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );

		$submission = Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id, $grade );
		Sensei()->quiz_answer_repository->create( $submission, 0, 'answer' );

		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
		$quiz_progress->grade();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		return $lesson_id;
	}

	/**
	 * Helper to call the protected get_row_data() method.
	 *
	 * @param Sensei_Reports_Overview_List_Table_Courses $list_table List table instance.
	 * @param object                                      $item Row item.
	 *
	 * @return array
	 */
	private function invoke_get_row_data( $list_table, $item ) {
		$method = new ReflectionMethod( $list_table, 'get_row_data' );
		$method->setAccessible( true );

		return $method->invoke( $list_table, $item );
	}
}

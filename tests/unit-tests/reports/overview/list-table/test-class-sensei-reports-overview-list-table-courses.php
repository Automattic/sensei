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

	public function testGetColumns_RegisteredAndTemporaryCompletionsCreated_ExcludesTemporaryCompletions() {
		/* Arrange. */
		$user_id = $this->factory->user->create();

		$course_id = $this->factory->course->create();

		$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
		$course_progress->complete();
		Sensei()->course_progress_repository->save( $course_progress );

		foreach ( array( 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$temporary_user_id = $this->factory->user->create( array( 'user_login' => $login ) );
			$course_progress   = Sensei()->course_progress_repository->create( $course_id, $temporary_user_id );
			$course_progress->complete();
			Sensei()->course_progress_repository->save( $course_progress );
		}

		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_courses_average_grade' )->willReturn( 2 );
		$service->method( 'get_total_enrollments' )->willReturn( 1 );

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
			'title'              => 'Course (1)',
			'last_activity'      => 'Last Activity',
			'enrolled'           => 'Enrolled (1)',
			'completions'        => 'Completions (1)',
			'completion_rate'    => 'Completion Rate (100%)',
			'average_progress'   => 'Average Progress (0%)',
			'average_percent'    => 'Average Grade (2%)',
			'days_to_completion' => 'Days to Completion (0)',
		);

		self::assertSame( $expected, $actual );
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
}

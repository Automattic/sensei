<?php

/**
 * Tests for Sensei_Reports_Overview_List_Table_Courses class.
 *
 * @covers Sensei_Reports_Overview_List_Table_Courses
 */
class Sensei_Reports_Overview_List_Table_Courses_Test extends WP_UnitTestCase {

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
		$data_provider->method( 'get_items' )->willReturn( [ $course_id ] );
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
		$expected = [
			'title'              => 'Course (1)',
			'last_activity'      => 'Last Activity',
			'enrolled'           => 'Enrolled (0)',
			'completions'        => 'Completions (0)',
			'completion_rate'    => 'Completion Rate (N/A)',
			'average_progress'   => 'Average Progress (0%)',
			'average_percent'    => 'Average Grade (2%)',
			'days_to_completion' => 'Days to Completion (0)',
		];

		self::assertSame( $expected, $actual );
	}

	public function testGetColumns_CompletionsFound_ReturnsMatchingArray() {
		/* Arrange. */
		$user_id = $this->factory->user->create();

		$course_id = $this->factory->course->create();
		Sensei_Utils::update_course_status( $user_id, $course_id, 'complete' );

		$service = $this->createMock( Sensei_Reports_Overview_Service_Courses::class );
		$service->method( 'get_courses_average_grade' )->willReturn( 2 );
		$service->method( 'get_total_enrollments' )->willReturn( 4 );

		$course = $this->createMock( Sensei_Course::class );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( [ $course_id ] );

		$list_table = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$course,
			$data_provider,
			$service
		);

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = [
			'title'              => 'Course (1)',
			'last_activity'      => 'Last Activity',
			'enrolled'           => 'Enrolled (4)',
			'completions'        => 'Completions (1)',
			'completion_rate'    => 'Completion Rate (25%)',
			'average_progress'   => 'Average Progress (0%)',
			'average_percent'    => 'Average Grade (2%)',
			'days_to_completion' => 'Days to Completion (0)',
		];

		self::assertSame( $expected, $actual );
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
		$expected = [
			'title'       => [ 'title', false ],
			'completions' => [ 'count_of_completions', false ],
		];
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
}

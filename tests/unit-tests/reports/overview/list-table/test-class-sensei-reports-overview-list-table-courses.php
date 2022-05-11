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
	private $factory;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Set up before each test.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGetColumns_NoCompletionsFound_ReturnsMatchingArray() {
		/* Arrange. */
		$grading = $this->createMock( Sensei_Grading::class );
		$grading->method( 'get_courses_average_grade_filter_courses' )->willReturn( 2 );

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'get_average_days_to_completion' )->willReturn( 2.2 );

		$list_table              = new Sensei_Reports_Overview_List_Table_Courses(
			$grading,
			$course,
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class )
		);
		$list_table->total_items = 1;

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = [
			'title'              => 'Course (1)',
			'last_activity'      => 'Last Activity',
			'completions'        => 'Completed (0)',
			'average_progress'   => 'Average Progress',
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

		$grading = $this->createMock( Sensei_Grading::class );
		$grading->method( 'get_courses_average_grade_filter_courses' )->willReturn( 2 );

		$course = $this->createMock( Sensei_Course::class );
		$course->method( 'get_average_days_to_completion' )->willReturn( 3.0 );

		$data_provider           = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$list_table              = new Sensei_Reports_Overview_List_Table_Courses(
			$grading,
			$course,
			$data_provider
		);
		$list_table->total_items = 4;

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = [
			'title'              => 'Course (4)',
			'last_activity'      => 'Last Activity',
			'completions'        => 'Completed (1)',
			'average_progress'   => 'Average Progress',
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
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class )
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
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class )
		);

		/* Act. */
		$actual = $list_table->search_button();

		/* Assert. */
		self::assertSame( 'Search Courses', $actual );
	}


	public function testGetAverageDaysToCompletionWhenOneCourseExistsReturnsMatchingValue() {
		$user1_id  = $this->factory->user->create();
		$user2_id  = $this->factory->user->create();
		$user3_id  = $this->factory->user->create();
		$course_id = $this->factory->course->create();

		$comment1_id = Sensei_Utils::update_course_status( $user1_id, $course_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment1_id,
				'comment_date' => '2022-01-07 00:00:00',
			]
		);
		update_comment_meta( $comment1_id, 'start', '2022-01-01 00:00:01' );

		$comment2_id = Sensei_Utils::update_course_status( $user2_id, $course_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment2_id,
				'comment_date' => '2022-01-10 00:00:00',
			]
		);
		update_comment_meta( $comment2_id, 'start', '2022-01-01 00:00:01' );

		$comment3_id = Sensei_Utils::update_course_status( $user3_id, $course_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment3_id,
				'comment_date' => '2022-01-30 00:00:00',
			]
		);
		update_comment_meta( $comment3_id, 'start', '2022-01-01 00:00:01' );

		$instance = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class )
		);

		$method = new ReflectionMethod( $instance, 'get_average_days_to_completion' );
		$method->setAccessible( true );
		$actual = $method->invoke( $instance, [ $course_id ] );

		// 2022-01-07 00:00:00 - 2022-01-01 00:00:01 + 1 = 7 days.
		// 2022-01-10 00:00:00 - 2022-01-01 00:00:01 + 1 = 10 days.
		// 2022-01-30 00:00:00 - 2022-01-01 00:00:01 + 1 = 30 days.
		// As these completions are for the single course:
		// ceil(7 + 10 + 30/ 3)  = 16 days.
		self::assertSame( 16.0, $actual );
	}

	public function testGetAverageDaysToCompletionWhenMoreThanOneCourseExistReturnsMatchingValue() {
		$user1_id   = $this->factory->user->create();
		$user2_id   = $this->factory->user->create();
		$course1_id = $this->factory->course->create();
		$course2_id = $this->factory->course->create();

		$comment1_id = Sensei_Utils::update_course_status( $user1_id, $course1_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment1_id,
				'comment_date' => '2022-03-11 23:29:06',
			]
		);
		update_comment_meta( $comment1_id, 'start', '2022-03-11 23:27:51' );

		$comment2_id = Sensei_Utils::update_course_status( $user2_id, $course1_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment2_id,
				'comment_date' => '2022-03-14 21:34:37',
			]
		);
		update_comment_meta( $comment2_id, 'start', '2022-03-14 21:34:27' );

		$comment3_id = Sensei_Utils::update_course_status( $user1_id, $course2_id, 'complete' );
		wp_update_comment(
			[
				'comment_ID'   => $comment3_id,
				'comment_date' => '2022-03-12 00:22:37',
			]
		);
		update_comment_meta( $comment3_id, 'start', '2022-03-09 00:22:34' );

		$instance = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class )
		);

		$method = new ReflectionMethod( $instance, 'get_average_days_to_completion' );
		$method->setAccessible( true );
		$actual = $method->invoke( $instance, [ $course1_id, $course2_id ] );

		// Average for the first course: (1 + 1) / 2 = 1.
		// Average for the second course: 4 / 1 = 4.
		// Total: (1 + 4) / 2 = 2.5.
		self::assertSame( 2.5, $actual );
	}

	public function testGetAverageDaysToCompletionTotalWithoutCompletionsReturnsZero() {
		$instance = new Sensei_Reports_Overview_List_Table_Courses(
			$this->createMock( Sensei_Grading::class ),
			$this->createMock( Sensei_Course::class ),
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class )
		);

		$method = new ReflectionMethod( $instance, 'get_average_days_to_completion' );
		$method->setAccessible( true );
		$actual = $method->invoke( $instance, [] );

		self::assertSame( 0.0, $actual );
	}
}

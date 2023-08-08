<?php

/**
 * Tests for Sensei_Reports_Overview_List_Table_Students class.
 *
 * @covers Sensei_Reports_Overview_List_Table_Students
 */
class Sensei_Reports_Overview_List_Table_Students_Test extends WP_UnitTestCase {

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

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

	public function testGetColumns_WithGradingAndCompletions_ReturnsColumnsWithCorrectTotals() {
		/* Arrange. */
		$user_id             = $this->factory->user->create();
		$active_course_id    = $this->factory->course->create();
		$completed_course_id = $this->factory->course->create();

		Sensei_Utils::update_course_status( $user_id, $active_course_id, 'in-progress' );
		Sensei_Utils::update_course_status( $user_id, $completed_course_id, 'complete' );

		$student_service = $this->createMock( Sensei_Reports_Overview_Service_Students::class );
		$student_service->method( 'get_graded_lessons_average_grade' )->willReturn( 50 );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( [ $user_id ] );
		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$data_provider,
			$student_service
		);

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = [
			'title'             => 'Student (1)',
			'email'             => 'Email',
			'date_registered'   => 'Date Registered',
			'last_activity'     => 'Last Activity',
			'active_courses'    => 'Active Courses (1)',
			'completed_courses' => 'Completed Courses (1)',
			'average_grade'     => 'Average Grade (50%)',
		];

		self::assertSame( $expected, $actual );
	}

	public function testGetSortableColumns_WhenCalled_ReturnsMatchingArray() {
		/* Arrange. */
		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Students::class )
		);

		/* Act. */
		$actual = $list_table->get_sortable_columns();

		/* Assert. */
		$expected = [
			'title'           => [ 'display_name', false ],
			'email'           => [ 'user_email', false ],
			'date_registered' => [ 'user_registered', false ],
			'last_activity'   => [ 'last_activity_date', false ],
		];
		self::assertSame( $expected, $actual );
	}

	public function testGetSortableColumns_NoUsersRelationship_ReturnsNoLastActivityDateColumn() {
		/* Arrange. */
		tests_add_filter( 'sensei_can_use_users_relationship', '__return_false' );
		Sensei_No_Users_Table_Relationship::instance()->init();

		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Students::class )
		);

		/* Act. */
		$actual = $list_table->get_sortable_columns();

		/* Assert. */
		$this->assertFalse( isset( $actual['last_activity'] ) );
	}

	public function testSearchButton_WhenCalled_ReturnsMatchingString() {
		/* Arrange. */
		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class ),
			$this->createMock( Sensei_Reports_Overview_Service_Students::class )
		);

		/* Act. */
		$actual = $list_table->search_button();

		/* Assert. */
		self::assertSame( 'Search Students', $actual );
	}
}

<?php

/**
 * Tests for Sensei_Reports_Overview_List_Table_Students class.
 *
 * @covers Sensei_Reports_Overview_List_Table_Students
 */
class Sensei_Reports_Overview_List_Table_Students_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

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
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$user_id             = $this->factory->user->create();
		$active_course_id    = $this->factory->course->create();
		$completed_course_id = $this->factory->course->create();

		Sensei()->course_progress_repository->save( Sensei()->course_progress_repository->create( $active_course_id, $user_id ) );

		$completed_course_progress = Sensei()->course_progress_repository->create( $completed_course_id, $user_id );
		$completed_course_progress->complete();
		Sensei()->course_progress_repository->save( $completed_course_progress );

		$student_service = $this->createMock( Sensei_Reports_Overview_Service_Students::class );
		$student_service->method( 'get_graded_lessons_average_grade' )->willReturn( 50 );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $user_id ) );
		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$data_provider,
			$student_service
		);

		/* Act. */
		$actual = $list_table->get_columns();

		/* Assert. */
		$expected = array(
			'title'             => 'Student (1)',
			'email'             => 'Email',
			'date_registered'   => 'Date Registered',
			'last_activity'     => 'Last Activity',
			'active_courses'    => 'Active Courses (1)',
			'completed_courses' => 'Completed Courses (1)',
			'average_grade'     => 'Average Grade (50%)',
		);

		self::assertSame( $expected, $actual );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
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
		$expected = array(
			'title'           => array( 'display_name', false ),
			'email'           => array( 'user_email', false ),
			'date_registered' => array( 'user_registered', false ),
			'last_activity'   => array( 'last_activity_date', false ),
		);
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

	public function testGetRowData_WhenCalledAfterPrepareItems_UsesPrimedCourseCounts() {
		/* Arrange. */
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$user_id               = $this->factory->user->create();
		$completed_course_id   = $this->factory->course->create();
		$in_progress_course_id = $this->factory->course->create();

		$completed_course_progress = Sensei()->course_progress_repository->create( $completed_course_id, $user_id );
		$completed_course_progress->complete();
		Sensei()->course_progress_repository->save( $completed_course_progress );

		Sensei()->course_progress_repository->save( Sensei()->course_progress_repository->create( $in_progress_course_id, $user_id ) );

		$user = get_user_by( 'id', $user_id );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $user ) );
		$data_provider->method( 'get_last_total_items' )->willReturn( 1 );

		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$data_provider,
			$this->createMock( Sensei_Reports_Overview_Service_Students::class )
		);

		/* Act. */
		$list_table->prepare_items();
		$row = $this->invoke_get_row_data( $list_table, $user );

		/* Assert. */
		self::assertSame( '1', (string) $row['completed_courses'] );
		self::assertSame( '1', (string) $row['active_courses'] );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	public function test_get_row_data_uses_primed_grade_totals() {
		/* Arrange. */
		if ( self::is_hpps_tables_mode() ) {
			$this->enable_hpps_tables_repository();
		}

		$user_id = $this->factory->user->create();

		$this->grade_lesson_for_user( $user_id, 80 );
		$this->grade_lesson_for_user( $user_id, 60 );

		$user = get_user_by( 'id', $user_id );

		$data_provider = $this->createMock( Sensei_Reports_Overview_Data_Provider_Interface::class );
		$data_provider->method( 'get_items' )->willReturn( array( $user ) );
		$data_provider->method( 'get_last_total_items' )->willReturn( 1 );

		$list_table = new Sensei_Reports_Overview_List_Table_Students(
			$data_provider,
			$this->createMock( Sensei_Reports_Overview_Service_Students::class )
		);

		/* Act. */
		$list_table->prepare_items();
		$row = $this->invoke_get_row_data( $list_table, $user );

		/* Assert. */
		self::assertSame( '70%', $row['average_grade'] );

		if ( self::is_hpps_tables_mode() ) {
			$this->reset_hpps_repository();
		}
	}

	/**
	 * Seed a graded lesson (with a quiz submission and grade) for a user, via the
	 * progress/quiz submission repositories so both storage modes have data.
	 *
	 * @param int $user_id User ID.
	 * @param int $grade   Grade to assign.
	 */
	private function grade_lesson_for_user( int $user_id, int $grade ): void {
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );

		$submission = Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id, $grade );
		Sensei()->quiz_answer_repository->create( $submission, 0, 'answer' );

		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
		$quiz_progress->grade();
		Sensei()->quiz_progress_repository->save( $quiz_progress );
	}

	/**
	 * Helper to call the protected get_row_data() method.
	 *
	 * @param Sensei_Reports_Overview_List_Table_Students $list_table List table instance.
	 * @param WP_User                                      $item Row item.
	 *
	 * @return array
	 */
	private function invoke_get_row_data( $list_table, $item ) {
		$method = new ReflectionMethod( $list_table, 'get_row_data' );
		$method->setAccessible( true );

		return $method->invoke( $list_table, $item );
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

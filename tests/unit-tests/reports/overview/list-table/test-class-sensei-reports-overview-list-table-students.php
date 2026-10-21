<?php

/**
 * Tests for Sensei_Reports_Overview_List_Table_Students class.
 *
 * @covers Sensei_Reports_Overview_List_Table_Students
 */
class Sensei_Reports_Overview_List_Table_Students_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;

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

	public function testGetColumns_WithGradingAndCompletions_ReturnsColumnsWithCorrectTotals() {
		/* Arrange. */
		$user_id = $this->factory->user->create();

		$student_service = $this->createMock( Sensei_Reports_Overview_Service_Students::class );
		$student_service->method( 'get_graded_lessons_average_grade' )->willReturn( 50 );
		$student_service->method( 'get_total_course_counts' )->willReturn(
			array(
				'active'    => 1,
				'completed' => 1,
			)
		);

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
	}

	/**
	 * Last Activity selects and orders the same students for displayed rows, totals, and CSV.
	 *
	 * @covers Sensei_Reports_Overview_List_Table_Students::prepare_items
	 * @covers Sensei_Reports_Overview_List_Table_Students::get_columns
	 * @covers Sensei_Reports_Overview_List_Table_Students::get_row_data
	 * @covers Sensei_Reports_Overview_List_Table_Students::generate_report
	 */
	public function testGenerateReport_ActivityDateFiltered_ReturnsMatchingRowsAndTotals(): void {
		global $wpdb;

		$this->resetCourseEnrolmentManager();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$user_ids  = array();
		foreach ( array( '2022-02-28 12:00:00', '2022-03-01 12:00:00', '2022-03-02 12:00:00' ) as $date ) {
			$user_id    = $this->factory->user->create();
			$user_ids[] = $user_id;
			$this->manuallyEnrolStudentInCourse( $user_id, $course_id );
			$progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			$progress->complete( new DateTimeImmutable( $date, new DateTimeZone( 'UTC' ) ) );
			Sensei()->lesson_progress_repository->save( $progress );
			if ( self::is_hpps_tables_mode() ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set the activity fixture date.
				$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'updated_at' => $date ), array( 'id' => $progress->get_id() ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verify HPPS without mirrored lesson comments.
				$wpdb->delete(
					$wpdb->comments,
					array(
						'user_id'      => $user_id,
						'comment_type' => 'sensei_lesson_status',
					)
				);
			}
		}
		add_filter(
			'sensei_analysis_overview_filter_users',
			static function ( $args ) use ( $user_ids ) {
				$args['include'] = $user_ids;
				return $args;
			}
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the test request state.
		$initial_get = $_GET;
		$_GET        = array(
			'start_date' => '2022-03-01',
			'end_date'   => '2022-03-02',
			'timezone'   => 'UTC',
			'orderby'    => 'last_activity_date',
			'order'      => 'desc',
		);
		$table       = ( new Sensei_Reports_Overview_List_Table_Factory() )->create( 'students' );
		$row_method  = new ReflectionMethod( $table, 'get_row_data' );
		$row_method->setAccessible( true );

		try {
			$table->prepare_items();
			$items   = $table->items;
			$columns = $table->get_columns();
			$row     = $row_method->invoke( $table, $items[0] );
			$csv     = $table->generate_report();
		} finally {
			$_GET = $initial_get;
		}

		self::assertSame( array( $user_ids[2], $user_ids[1] ), array_map( 'intval', wp_list_pluck( $items, 'ID' ) ), 'Rows should retain the requested users in activity order.' );
		self::assertSame( 'Student (2)', $columns['title'], 'The header should count the date-filtered users.' );
		self::assertSame( Sensei_Utils::format_last_activity_date( '2022-03-02 12:00:00' ), $row['last_activity'], 'The screen should format the selected activity date.' );
		self::assertCount( 3, $csv, 'CSV should contain the header and both matching users.' );
		self::assertSame( array( '2022-03-02 12:00:00', '2022-03-01 12:00:00' ), array_column( array_slice( $csv, 1 ), 'last_activity' ), 'CSV should retain exact timestamps in the same order.' );
		self::assertSame( get_userdata( $user_ids[2] )->user_email, $csv[1]['email'], 'CSV should retain the identity of the selected student.' );
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

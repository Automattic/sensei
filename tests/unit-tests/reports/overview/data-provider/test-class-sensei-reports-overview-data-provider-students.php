<?php

/**
 * Sensei Reports Overview Data Provider Students Test Class
 *
 * @covers Sensei_Reports_Overview_Data_Provider_Students
 */
class Sensei_Reports_Overview_Data_Provider_Students_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
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
		$this->resetCourseEnrolmentManager();

		$this->factory = new Sensei_Factory();
		$this->maybe_enable_hpps_tables_repository();

		add_filter( 'sensei_analysis_overview_filter_users', [ $this, 'excludeAdminFromUserQuery' ] );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->maybe_reset_hpps_repository();

		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsAllStudents() {
		/* Arrange. */
		$no_last_activity_user   = $this->createUserWithCourseEnrollment();
		$user_with_last_activity = $this->createUserWithActivity( '2022-03-01 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1, 'orderby' => 'last_activity_date', 'order' => 'ASC' ] );

		/* Assert. */
		$this->assertSame( array( $no_last_activity_user, $user_with_last_activity ), array_map( 'intval', wp_list_pluck( $students, 'ID' ) ), 'Ascending activity order should retain the student without activity.' );
	}

	/**
	 * Student fields include the latest completed lesson activity.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Students::get_items
	 */
	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsStudentsWithCorrectFields() {
		/* Arrange. */
		$user_id = $this->createUserWithActivity(
			'2022-03-01 00:00:00',
			[
				'user_login'      => 'test',
				'user_email'      => 'test@example.org',
				'display_name'    => 'test',
				'user_registered' => '2022-03-01 00:00:00',
			]
		);

		$this->createUserWithActivity( '2022-02-28 00:00:00', array( 'ID' => $user_id ) );
		$this->createUserWithActivity( '2022-03-02 00:00:00', array( 'ID' => $user_id ), 'in-progress' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1 ] );

		/* Assert. */
		$this->assertEquals( $user_id, $students[0]->ID );
		$this->assertEquals( 'test', $students[0]->user_login );
		$this->assertEquals( 'test@example.org', $students[0]->user_email );
		$this->assertEquals( 'test', $students[0]->display_name );
		$this->assertEquals( '2022-03-01 00:00:00', $students[0]->last_activity_date, 'Last Activity should use the latest completed lesson, excluding newer incomplete activity.' );
		$this->assertEquals( '2022-03-01 00:00:00', $students[0]->user_registered );
	}

	public function testGetItems_FiltersWithSearchGiven_ReturnsTheSearchedStudents() {
		/* Arrange. */
		$searched_user = $this->createUserWithCourseEnrollment( [ 'user_login' => 'awesome_nickname' ] );
		$other_user    = $this->createUserWithCourseEnrollment( [ 'user_login' => 'cool_nickname' ] );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'search' => 'awesome_nickname' ] );

		/* Assert. */
		$expected = [
			[
				'id'                 => $searched_user,
				'last_activity_date' => null,
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithLastActivityDateGiven_ReturnsStudentsWithActivityOnly() {
		/* Arrange. */
		$user_with_activity    = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_with_no_activity = $this->factory->user->create();

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-01',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_with_activity,
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithTheSameFromAndToLastActivityDateGiven_ReturnsActiveStudentsInThatDay() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-01',
				'last_activity_date_to'   => '2022-03-01',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_1,
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithOnlyLastActivityDateFromGiven_ReturnsActiveStudentsFromThatDate() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-02',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_2,
				'last_activity_date' => '2022-03-02 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithOnlyLastActivityDateToGiven_ReturnsActiveStudentsToThatDate() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_to' => '2022-03-01',
			]
		);

		/* Assert. */
		$expected = [
			[
				'id'                 => $user_1,
				'last_activity_date' => '2022-03-01 00:00:00',
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithLastActivityDateFromBiggerThanLastActivityDateToGiven_ReturnsNoStudents() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			[
				'last_activity_date_from' => '2022-03-02',
				'last_activity_date_to'   => '2022-03-01',
			]
		);

		/* Assert. */
		$this->assertEmpty( $students );
	}

	public function testGetItems_WithNoFiltersGiven_ReturnsOnlyCourseEnrolledStudents() {
		/* Arrange. */
		$course_enrolled_user = $this->createUserWithCourseEnrollment();
		$not_enrolled_user    = $this->factory->user->create();

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [] );

		/* Assert. */
		$expected = [
			[
				'id'                 => $course_enrolled_user,
				'last_activity_date' => null,
			],
		];

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetLastTotalItems_WithNoItems_ReturnsZero() {
		/* Arrange. */
		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Assert. */
		$this->assertSame( 0, $data_provider->get_last_total_items() );
	}

	public function testGetLastTotalItems_WithItems_ReturnsTheTotalNumberOfItems() {
		/* Arrange. */
		$this->createUserWithCourseEnrollment();
		$this->createUserWithCourseEnrollment();

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1 ] );

		/* Assert. */
		$this->assertSame( 2, $data_provider->get_last_total_items() );
	}

	public function testGetItems_NoUsersRelationship_ReturnsNoLastActivityDate() {
		/* Arrange. */
		tests_add_filter( 'sensei_can_use_users_relationship', '__return_false' );
		Sensei_No_Users_Table_Relationship::instance()->init();

		$user_with_last_activity = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$data_provider           = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( [ 'number' => -1 ] );

		/* Assert. */
		$this->assertFalse( isset( $students[0]->last_activity_date ) );
	}

	/**
	 * Export the students to an array that is easier to assert against.
	 *
	 * @param array $students The students array containing the user objects.
	 *
	 * @return array
	 */
	private function exportStudents( array $students ): array {
		$result = [];

		foreach ( $students as $student ) {
			$result[] = [
				'id'                 => $student->ID,
				'last_activity_date' => $student->last_activity_date,
			];
		}

		return $result;
	}

	/**
	 * Create a user that has an activity.
	 *
	 * @param string $activity_date The activity date.
	 * @param array  $user_args The user args, or ID of an existing user.
	 * @param string $status The lesson status: complete or in-progress.
	 *
	 * @return int The user ID.
	 */
	private function createUserWithActivity( string $activity_date, array $user_args = [], string $status = 'complete' ): int {
		$user_id   = $user_args['ID'] ?? $this->factory->user->create( $user_args );
		$course_id = $this->factory->course->create();

		$lesson_id = $this->factory->lesson->create(
			[
				'meta_input' => [ '_lesson_course' => $course_id ],
			]
		);

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );
		$progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
		if ( 'in-progress' !== $status ) {
			$progress->complete( new DateTimeImmutable( $activity_date, new DateTimeZone( 'UTC' ) ) );
		}
		Sensei()->lesson_progress_repository->save( $progress );

		global $wpdb;
		// Saves stamp the current time; fix the date read by Last Activity in the active backend.
		if ( self::is_hpps_tables_mode() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set the activity fixture date.
			$wpdb->update( $wpdb->prefix . 'sensei_lms_progress', array( 'updated_at' => $activity_date ), array( 'id' => $progress->get_id() ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verify HPPS without mirrored lesson comments.
			$wpdb->delete( $wpdb->comments, array( 'user_id' => $user_id, 'comment_type' => 'sensei_lesson_status' ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Set the activity fixture date.
			$wpdb->update( $wpdb->comments, array( 'comment_date' => $activity_date, 'comment_date_gmt' => $activity_date ), array( 'comment_ID' => $progress->get_id() ) );
		}

		return $user_id;
	}

	/**
	 * Create a user that is enrolled to a course.
	 *
	 * @param array    $user_args The user args.
	 * @param int|null $course_id The course ID.
	 *
	 * @return int The user ID.
	 */
	private function createUserWithCourseEnrollment( array $user_args = [], ?int $course_id = null ): int {
		$user_id   = $this->factory->user->create( $user_args );
		$course_id = $course_id ?? $this->factory->course->create();

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		return $user_id;
	}

	/**
	 * Exclude the default admin user from the WP_User_Query args.
	 *
	 * @param array $query_args The WP_User_Query args.
	 *
	 * @return array
	 */
	public function excludeAdminFromUserQuery( array $query_args ): array {
		$query_args['exclude'] = [ 1 ];

		return $query_args;
	}
}

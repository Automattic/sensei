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

		add_filter( 'sensei_analysis_overview_filter_users', array( $this, 'excludeAdminFromUserQuery' ) );

		// Keep the active repositories in sync with the storage mode reported by settings
		// (see the Sensei_HPPS_Helpers bootstrap quirk note).
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

	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsAllStudents() {
		/* Arrange. */
		$no_last_activity_user   = $this->createUserWithCourseEnrollment();
		$user_with_last_activity = $this->createUserWithActivity( '2022-03-01 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( array( 'number' => -1 ) );

		/* Assert. */
		$this->assertCount( 2, $students );
	}

	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsStudentsWithCorrectFields() {
		/* Arrange. */
		$user_id = $this->createUserWithActivity(
			'2022-03-01 00:00:00',
			array(
				'user_login'      => 'test',
				'user_email'      => 'test@example.org',
				'display_name'    => 'test',
				'user_registered' => '2022-03-01 00:00:00',
			)
		);

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( array( 'number' => -1 ) );

		/* Assert. */
		$this->assertEquals( $user_id, $students[0]->ID );
		$this->assertEquals( 'test', $students[0]->user_login );
		$this->assertEquals( 'test@example.org', $students[0]->user_email );
		$this->assertEquals( 'test', $students[0]->display_name );
		$this->assertEquals( '2022-03-01 00:00:00', $students[0]->last_activity_date );
		$this->assertEquals( '2022-03-01 00:00:00', $students[0]->user_registered );
	}

	public function testGetItems_FiltersWithSearchGiven_ReturnsTheSearchedStudents() {
		/* Arrange. */
		$searched_user = $this->createUserWithCourseEnrollment( array( 'user_login' => 'awesome_nickname' ) );
		$other_user    = $this->createUserWithCourseEnrollment( array( 'user_login' => 'cool_nickname' ) );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( array( 'search' => 'awesome_nickname' ) );

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $searched_user,
				'last_activity_date' => null,
			),
		);

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithLastActivityDateGiven_ReturnsStudentsWithActivityOnly() {
		/* Arrange. */
		$user_with_activity    = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_with_no_activity = $this->factory->user->create();

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			array(
				'last_activity_date_from' => '2022-03-01',
			)
		);

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $user_with_activity,
				'last_activity_date' => '2022-03-01 00:00:00',
			),
		);

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithTheSameFromAndToLastActivityDateGiven_ReturnsActiveStudentsInThatDay() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			array(
				'last_activity_date_from' => '2022-03-01',
				'last_activity_date_to'   => '2022-03-01',
			)
		);

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $user_1,
				'last_activity_date' => '2022-03-01 00:00:00',
			),
		);

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithOnlyLastActivityDateFromGiven_ReturnsActiveStudentsFromThatDate() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			array(
				'last_activity_date_from' => '2022-03-02',
			)
		);

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $user_2,
				'last_activity_date' => '2022-03-02 00:00:00',
			),
		);

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithOnlyLastActivityDateToGiven_ReturnsActiveStudentsToThatDate() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			array(
				'last_activity_date_to' => '2022-03-01',
			)
		);

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $user_1,
				'last_activity_date' => '2022-03-01 00:00:00',
			),
		);

		$this->assertEquals( $expected, $this->exportStudents( $students ) );
	}

	public function testGetItems_FiltersWithLastActivityDateFromBiggerThanLastActivityDateToGiven_ReturnsNoStudents() {
		/* Arrange. */
		$user_1 = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithActivity( '2022-03-02 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			array(
				'last_activity_date_from' => '2022-03-02',
				'last_activity_date_to'   => '2022-03-01',
			)
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
		$students = $data_provider->get_items( array() );

		/* Assert. */
		$expected = array(
			array(
				'id'                 => $course_enrolled_user,
				'last_activity_date' => null,
			),
		);

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
		$students = $data_provider->get_items( array( 'number' => -1 ) );

		/* Assert. */
		$this->assertSame( 2, $data_provider->get_last_total_items() );
	}

	public function testGetItems_TablesModeLessonCompletedWithoutQuiz_ReturnsLastActivityDateFromProgressTable() {
		/* Arrange. */
		$this->enable_tables_mode();

		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
		$lesson_progress->complete( new DateTimeImmutable( '2022-03-01 00:00:00', new DateTimeZone( 'UTC' ) ) );
		Sensei()->lesson_progress_repository->save( $lesson_progress );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( array( 'number' => -1 ) );

		/* Assert. */
		$this->assertEquals( '2022-03-01 00:00:00', $students[0]->last_activity_date );

		$this->disable_tables_mode();
	}

	public function testGetItems_TablesModeQuizGraded_ReturnsLastActivityDateFromQuizCompletion() {
		/* Arrange. */
		$this->enable_tables_mode();

		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$quiz_id   = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		// Lesson stays in-progress; the granular status lives on the quiz progress row.
		Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );

		$submission = Sensei()->quiz_submission_repository->get_or_create( $quiz_id, $user_id, 80 );
		Sensei()->quiz_answer_repository->create( $submission, 0, 'answer' );

		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
		$quiz_progress->grade( new DateTimeImmutable( '2022-03-05 00:00:00', new DateTimeZone( 'UTC' ) ) );
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( array( 'number' => -1 ) );

		/* Assert. */
		$this->assertEquals( '2022-03-05 00:00:00', $students[0]->last_activity_date );

		$this->disable_tables_mode();
	}

	public function testGetItems_TablesModeSortByLastActivityDate_SortsUsersCorrectly() {
		/* Arrange. */
		$this->enable_tables_mode();

		$user_1 = $this->createUserWithCompletedLessonInTablesMode( '2022-03-01 00:00:00' );
		$user_2 = $this->createUserWithCompletedLessonInTablesMode( '2022-03-05 00:00:00' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items(
			array(
				'number'  => -1,
				'orderby' => 'last_activity_date',
				'order'   => 'DESC',
			)
		);

		/* Assert. */
		$this->assertEquals( $user_2, $students[0]->ID );
		$this->assertEquals( $user_1, $students[1]->ID );

		$this->disable_tables_mode();
	}

	public function testGetItems_NoUsersRelationship_ReturnsNoLastActivityDate() {
		/* Arrange. */
		tests_add_filter( 'sensei_can_use_users_relationship', '__return_false' );
		Sensei_No_Users_Table_Relationship::instance()->init();

		$user_with_last_activity = $this->createUserWithActivity( '2022-03-01 00:00:00' );
		$data_provider           = new Sensei_Reports_Overview_Data_Provider_Students();

		/* Act. */
		$students = $data_provider->get_items( array( 'number' => -1 ) );

		/* Assert. */
		$this->assertFalse( isset( $students[0]->last_activity_date ) );
	}

	/**
	 * Whether HPPS was enabled before switching to tables mode, so it can be restored.
	 *
	 * @var bool
	 */
	private $original_hpps_enabled = false;

	/**
	 * Switch storage to the HPPS tables repository, regardless of the ENABLE_HPPS
	 * environment variable, so the tables-mode query branch can be exercised directly.
	 */
	private function enable_tables_mode(): void {
		$this->original_hpps_enabled = Sensei()->settings->settings['experimental_progress_storage'] ?? false;

		Sensei()->settings->settings['experimental_progress_storage'] = true;

		$this->enable_hpps_tables_repository();
	}

	/**
	 * Restore the comments-based storage after a tables-mode test.
	 */
	private function disable_tables_mode(): void {
		$this->reset_hpps_repository();

		Sensei()->settings->settings['experimental_progress_storage'] = $this->original_hpps_enabled;
	}

	/**
	 * Create a user with a completed lesson, seeded via the tables-based progress
	 * repositories, and no quiz.
	 *
	 * @param string $completed_at The completion date, in 'Y-m-d H:i:s' format (UTC).
	 *
	 * @return int The user ID.
	 */
	private function createUserWithCompletedLessonInTablesMode( string $completed_at ): int {
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
		$lesson_progress->complete( new DateTimeImmutable( $completed_at, new DateTimeZone( 'UTC' ) ) );
		Sensei()->lesson_progress_repository->save( $lesson_progress );

		return $user_id;
	}

	/**
	 * Export the students to an array that is easier to assert against.
	 *
	 * @param array $students The students array containing the user objects.
	 *
	 * @return array
	 */
	private function exportStudents( array $students ): array {
		$result = array();

		foreach ( $students as $student ) {
			$result[] = array(
				'id'                 => $student->ID,
				'last_activity_date' => $student->last_activity_date,
			);
		}

		return $result;
	}

	/**
	 * Create a user that has an activity.
	 *
	 * @param string $activity_date The activity date.
	 * @param array  $user_args The user args.
	 *
	 * @return int The user ID.
	 */
	private function createUserWithActivity( string $activity_date, array $user_args = array() ): int {
		$user_id   = $this->factory->user->create( $user_args );
		$course_id = $this->factory->course->create();

		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array( '_lesson_course' => $course_id ),
			)
		);

		$this->manuallyEnrolStudentInCourse( $user_id, $course_id );

		if ( \Sensei\Internal\Services\Progress_Storage_Settings::is_tables_repository() ) {
			$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			$lesson_progress->complete( new DateTimeImmutable( $activity_date, new DateTimeZone( 'UTC' ) ) );
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		} else {
			$activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

			wp_update_comment(
				array(
					'comment_ID'   => $activity_comment_id,
					'comment_date' => $activity_date,
				)
			);
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
	private function createUserWithCourseEnrollment( array $user_args = array(), int $course_id = null ): int {
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
		$query_args['exclude'] = array( 1 );

		return $query_args;
	}
}

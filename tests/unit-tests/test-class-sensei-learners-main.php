<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

global $hook_suffix;

/**
 * Tests for Sensei_Learners_Main class.
 *
 * @group bulk-actions
 */
class Sensei_Learners_Main_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;
	use Sensei_HPPS_Helpers;

	private $original_get;
	private $original_request;

	protected $factory;

	private $course_id;
	private $course_students;

	public function setUp(): void {
		parent::setUp();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve request state for isolated test fixtures.
		$this->original_get = $_GET;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve request state for isolated test fixtures.
		$this->original_request = $_REQUEST;

		$this->factory = new Sensei_Factory();
		$this->maybe_enable_hpps_tables_repository();

		$this->login_as_teacher();
		$this->course_id = $this->factory->course->create( array( 'post_author' => get_current_user_id() ) );
	}

	public function tearDown(): void {
		$_GET     = $this->original_get;
		$_REQUEST = $this->original_request;
		$this->maybe_reset_hpps_repository();

		parent::tearDown();

		$this->factory->tearDown();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Create a course with learners of various enrollment status
	 */
	private function createCourseEnrolments() {
		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Provides_For_Dinosaurs::class );
		$this->resetCourseEnrolmentManager();

		$course_id        = $this->factory->course->create();
		$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );

		$users_manual        = $this->factory->user->create_many( 2 );
		$users_unenrolled    = $this->factory->user->create_many( 3 );
		$users_otherprovider = array_map( array( $this, 'createDinosaurStudent' ), range( 1, 4 ) );

		foreach ( $users_manual as $user_id ) {
			$this->manuallyEnrolStudentInCourse( $user_id, $course_id );
		}

		foreach ( $users_unenrolled as $user_id ) {
			Sensei_Utils::user_start_course( $user_id, $course_id );
		}

		foreach ( $users_otherprovider as $user_id ) {
			$course_enrolment->is_enrolled( $user_id );
		}

		$this->course_students = array_merge( $users_manual, $users_unenrolled, $users_otherprovider );

		return $course_id;
	}

	/**
	 * The Lessons view displays the number of students with lesson progress.
	 *
	 * @covers Sensei_Learners_Main::get_row_data
	 */
	public function testGetRowData_LessonWithStudentProgressGiven_RendersStudentCount() {
		$lesson_id = $this->createListingPost( 'lesson' );
		$this->createListingProgress( 'lesson', $lesson_id, $this->factory->user->create() );
		$_GET['course_id'] = $this->course_id;
		$_GET['view']      = 'lessons';
		$table             = new Sensei_Learners_Main();
		$table->prepare_items();

		ob_start();
		$table->single_row( get_post( $lesson_id ) );
		$html = ob_get_clean();

		self::assertStringContainsString( esc_html( get_the_title( $lesson_id ) ) . '</a>', $html, 'The row displays the lesson title.' );
		self::assertMatchesRegularExpression( '/column-num_learners[^>]*>1<\/td>/', $html, 'The row displays the lesson student count.' );
		self::assertSame( 1, $table->get_pagination_arg( 'total_items' ), 'The table contains the course lesson.' );
	}

	/**
	 * Course and lesson student rows display names, enrollment, status, and dates.
	 *
	 * @dataProvider listingTypes
	 * @covers Sensei_Learners_Main::get_row_data
	 */
	public function testGetRowData_CompletedStudentListed_RendersStudentDetails( $type ) {
		$post_id = $this->createListingPost( $type );
		$user_id = $this->factory->user->create(
			array(
				'first_name' => 'Alice',
				'last_name'  => 'Student',
			)
		);
		$this->createListingProgress( $type, $post_id, $user_id );
		$repository = 'course' === $type ? Sensei()->course_progress_repository : Sensei()->lesson_progress_repository;
		$progress   = $repository->get( $post_id, $user_id );
		$progress->complete( new DateTimeImmutable( '2026-01-03 04:05:06', new DateTimeZone( 'UTC' ) ) );
		$repository->save( $progress );
		$table = $this->prepareListing( $type, $post_id );

		ob_start();
		$table->single_row( $table->items[0] );
		$html = ob_get_clean();

		self::assertStringContainsString( 'Alice Student</a>', $html, 'The row displays the student name.' );
		self::assertMatchesRegularExpression( '/column-enrolment_status[^>]*>.*?>No<\/span>/s', $html, 'The row displays the enrollment status.' );
		self::assertMatchesRegularExpression( '/column-user_status[^>]*>Completed<\/td>/', $html, 'The row displays the progress status.' );
		self::assertStringContainsString( 'value="2026-01-02 03:04:05"', $html, 'The row displays the start date.' );
		self::assertMatchesRegularExpression( '/column-date_completed[^>]*>2026-01-03 04:05:06<\/td>/', $html, 'The row displays the completion date.' );
		self::assertSame( 1, $table->get_pagination_arg( 'total_items' ), 'The total includes the displayed student.' );
	}

	/**
	 * Students who passed a lesson quiz display as completed.
	 *
	 * @covers Sensei_Learners_Main::get_row_data
	 */
	public function testGetRowData_LessonWithPassedQuizGiven_RendersCompletedStatus() {
		$lesson_id = $this->createListingPost( 'lesson' );
		$quiz_id   = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		update_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		$user_id = $this->factory->user->create();
		$this->createListingProgress( 'lesson', $lesson_id, $user_id );
		$progress = Sensei()->lesson_progress_repository->get( $lesson_id, $user_id );
		$progress->complete();
		Sensei()->lesson_progress_repository->save( $progress );
		$quiz_progress = Sensei()->quiz_progress_repository->create( $quiz_id, $user_id );
		$quiz_progress->pass();
		Sensei()->quiz_progress_repository->save( $quiz_progress );

		$table = $this->prepareListing( 'lesson', $lesson_id );

		ob_start();
		$table->single_row( $table->items[0] );
		$html = ob_get_clean();

		self::assertMatchesRegularExpression( '/column-user_status[^>]*>Completed<\/td>/', $html );
	}

	/**
	 * Enrollment filters return the matching course or lesson students.
	 *
	 * @dataProvider enrolmentFilterTestCases
	 * @covers       Sensei_Learners_Main::get_learners
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGetLearners_EnrolmentStatusGiven_ReturnsMatchingStudents( $enrolment_status, $expected_result, $type ) {
		$this->course_id = $this->createCourseEnrolments();

		if ( 'lesson' === $type ) {
			$lesson_id = $this->createListingPost( 'lesson' );
			foreach ( $this->course_students as $user_id ) {
				$this->createListingProgress( 'lesson', $lesson_id, $user_id );
			}
			$_GET['lesson_id'] = $lesson_id;
		}

		$_GET['course_id']        = $this->course_id;
		$_GET['view']             = 'learners';
		$_GET['enrolment_status'] = $enrolment_status;

		$sensei_learners_main = new Sensei_Learners_Main();
		$sensei_learners_main->prepare_items();

		self::assertCount( $expected_result, $sensei_learners_main->items, 'The table contains the matching students.' );
		self::assertSame( $expected_result, $sensei_learners_main->get_pagination_arg( 'total_items' ), 'The total matches the enrollment filter.' );
	}

	/**
	 * Search filters the student list and its total.
	 *
	 * @dataProvider listingTypes
	 * @covers Sensei_Learners_Main::get_learners
	 */
	public function testGetLearners_SearchGiven_ReturnsMatchingStudent( $type ) {
		$post_id = $this->createListingPost( $type );
		$user_id = $this->factory->user->create( array( 'user_login' => 'matching_student' ) );
		$this->createListingProgress( $type, $post_id, $user_id );
		$this->createListingProgress( $type, $post_id, $this->factory->user->create() );
		$_GET['s'] = 'matching_student';

		$table = $this->prepareListing( $type, $post_id );

		self::assertSame( array( $user_id ), array_map( 'intval', wp_list_pluck( $table->items, 'user_id' ) ), 'Search returns the matching student.' );
		self::assertSame( 1, $table->get_pagination_arg( 'total_items' ), 'The total uses the same search restriction.' );
	}

	/**
	 * HPPS listings read progress without synchronized activity comments.
	 *
	 * @dataProvider listingTypes
	 * @covers Sensei_Learners_Main::get_learners
	 */
	public function testGetLearners_TableOnlyProgressGiven_ReturnsStudentAndTotal( $type ) {
		if ( ! self::is_hpps_tables_mode() ) {
			$this->markTestSkipped( 'Table-only reads require HPPS.' );
		}

		$post_id = $this->createListingPost( $type );
		$user_id = $this->factory->user->create();
		$this->createListingProgress( $type, $post_id, $user_id );
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Remove synchronized comments to exercise table-only progress.
		$wpdb->delete( $wpdb->comments, array( 'comment_post_ID' => $post_id ) );

		$table = $this->prepareListing( $type, $post_id );

		self::assertSame( array( $user_id ), array_map( 'intval', wp_list_pluck( $table->items, 'user_id' ) ), 'Table-only students remain visible.' );
		self::assertSame( 1, $table->get_pagination_arg( 'total_items' ), 'Table-only progress is included in the total.' );
	}

	/**
	 * Translated listings resolve the original stored progress.
	 *
	 * @dataProvider listingTypes
	 * @covers Sensei_Learners_Main::get_learners
	 */
	public function testGetLearners_TranslatedPostRequested_ReturnsOriginalProgress( $type ) {
		$original    = $this->createListingPost( $type );
		$translation = $this->createListingPost( $type );
		$user_id     = $this->factory->user->create();
		$this->createListingProgress( $type, $original, $user_id );
		$map = static function ( $post_id ) use ( $original, $translation ) {
			return $translation === $post_id ? $original : $post_id;
		};
		add_filter( 'sensei_course_progress_get_course_id', $map );
		add_filter( 'sensei_lesson_progress_get_lesson_id', $map );
		add_filter(
			'sensei_check_for_activity_args',
			static function ( $args ) use ( $map ) {
				$args['post_id'] = $map( $args['post_id'] );

				return $args;
			}
		);

		$table = $this->prepareListing( $type, $translation );

		self::assertSame( array( $user_id ), array_map( 'intval', wp_list_pluck( $table->items, 'user_id' ) ), 'Translated listings resolve the original progress ID.' );
	}

	public function enrolmentFilterTestCases() {
		return array(
			'All students'                      => array( 'all', 9, 'course' ),
			'Enrolled students'                 => array( 'enrolled', 6, 'course' ),
			'Unenrolled students'               => array( 'unenrolled', 3, 'course' ),
			'Manually enrolled students'        => array( 'manual', 2, 'course' ),
			'All lesson students'               => array( 'all', 9, 'lesson' ),
			'Enrolled lesson students'          => array( 'enrolled', 6, 'lesson' ),
			'Unenrolled lesson students'        => array( 'unenrolled', 3, 'lesson' ),
			'Manually enrolled lesson students' => array( 'manual', 2, 'lesson' ),
		);
	}

	public function listingTypes() {
		return array(
			'course' => array( 'course' ),
			'lesson' => array( 'lesson' ),
		);
	}

	private function createListingPost( $type ) {
		if ( 'course' === $type ) {
			return $this->factory->course->create( array( 'post_author' => get_current_user_id() ) );
		}

		return $this->factory->lesson->create(
			array(
				'post_author' => get_current_user_id(),
				'meta_input'  => array( '_lesson_course' => $this->course_id ),
			)
		);
	}

	private function createListingProgress( $type, $post_id, $user_id ) {
		$repository = 'course' === $type ? Sensei()->course_progress_repository : Sensei()->lesson_progress_repository;
		$progress   = $repository->create( $post_id, $user_id );
		$progress->start( new DateTimeImmutable( '2026-01-02 03:04:05', new DateTimeZone( 'UTC' ) ) );
		$repository->save( $progress );
	}

	private function prepareListing( $type, $post_id ) {
		$_GET['course_id'] = 'course' === $type ? $post_id : $this->course_id;
		$_GET['view']      = 'learners';
		if ( 'lesson' === $type ) {
			$_GET['lesson_id'] = $post_id;
		}

		$table = new Sensei_Learners_Main();
		$table->prepare_items();

		return $table;
	}
}

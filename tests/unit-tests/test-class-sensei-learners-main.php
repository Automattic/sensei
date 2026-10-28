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
		$this->maybe_enable_hpps_tables_repository();

		$this->factory = new Sensei_Factory();
		$this->login_as_teacher();
		$this->course_id = $this->createCourseEnrolments();
	}

	public function tearDown(): void {
		$_GET     = $this->original_get;
		$_REQUEST = $this->original_request;
		$this->maybe_reset_hpps_repository();

		parent::tearDown();
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
	 * Tests that only enrolled users are shown when filtered
	 *
	 * @dataProvider enrolmentFilterTestCases
	 * @covers       Sensei_Learners_Main::get_learners
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testUsersAreFilteredByEnrolmentStatus( $enrolment_status, $expected_result, $type ) {

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

		$this->assertCount( $expected_result, $sensei_learners_main->items );
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

	/**
	 * Lesson student rows expose the quiz status.
	 *
	 * @covers Sensei_Learners_Main::get_learners
	 */
	public function testGetLearners_LessonWithPassedQuizGiven_ReturnsQuizStatus() {
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

		self::assertSame( 'passed', $table->items[0]->status );
	}

	/**
	 * Student names determine the full result order before pagination.
	 *
	 * @dataProvider studentNameSortCases
	 * @covers Sensei_Learners_Main::get_learners
	 */
	public function testGetLearners_NameSortRequested_ReturnsAlphabeticalPages( $type, $order, $expected_indexes ) {
		$post_id = $this->createListingPost( $type );
		$names   = array(
			array(
				'first_name'   => 'Zoe',
				'last_name'    => 'Zeta',
				'display_name' => 'First display name',
			),
			array(
				'first_name'   => 'Alice',
				'last_name'    => 'Alpha',
				'display_name' => 'Last display name',
			),
			array(
				'first_name'   => 'Aaron',
				'last_name'    => '',
				'display_name' => 'Molly',
			),
		);
		$users   = array();
		foreach ( $names as $name ) {
			$user_id = $this->factory->user->create( $name );
			$this->createListingProgress( $type, $post_id, $user_id );
			$users[] = $user_id;
		}
		add_filter(
			'sensei_comments_per_page',
			static function () {
				return 2;
			}
		);
		$_GET['orderby'] = 'title';
		$_GET['order']   = $order;
		$expected        = array_map(
			static function ( $index ) use ( $users ) {
				return $users[ $index ];
			},
			$expected_indexes
		);

		$first_page        = $this->prepareListing( $type, $post_id );
		$_REQUEST['paged'] = 2;
		$second_page       = $this->prepareListing( $type, $post_id );

		self::assertSame( array_slice( $expected, 0, 2 ), array_map( 'intval', wp_list_pluck( $first_page->items, 'user_id' ) ), 'The first page sorts by the displayed student name.' );
		self::assertSame( array_slice( $expected, 2 ), array_map( 'intval', wp_list_pluck( $second_page->items, 'user_id' ) ), 'Name sorting applies before selecting the second page.' );
		self::assertSame( 3, $first_page->get_pagination_arg( 'total_items' ), 'Sorting preserves the student total.' );
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

		self::assertMatchesRegularExpression( '/column-num_learners[^>]*>1<\/td>/', $html );
	}

	/**
	 * Column filters and progress actions retain their student and post identifiers.
	 *
	 * @dataProvider listingTypes
	 * @covers Sensei_Learners_Main::get_row_data
	 */
	public function testGetRowData_StudentListed_RendersActionsAndColumnFilter( $type ) {
		$post_id = $this->createListingPost( $type );
		$user_id = $this->factory->user->create();
		$this->createListingProgress( $type, $post_id, $user_id );
		$table = $this->prepareListing( $type, $post_id );
		$row   = null;
		add_filter(
			'sensei_learners_main_column_data',
			static function ( $columns, $item ) use ( $user_id, &$row ) {
				$row = array( $item->post_id, $item->user_id, $item->status, $item->completed_at );
				if ( (int) $item->user_id === $user_id ) {
					$columns['title'] .= ' custom-column-marker';
				}
				return $columns;
			},
			10,
			2
		);

		ob_start();
		$table->single_row( $table->items[0] );
		$html = ob_get_clean();

		self::assertSame( array( $post_id, $user_id, 'in-progress', null ), $row, 'Column filters receive storage-neutral student progress fields.' );
		self::assertStringContainsString( 'custom-column-marker', $html, 'The existing column filter receives the student row.' );
		self::assertStringContainsString( 'data-action="remove_progress"', $html, 'Unenrolled students retain the remove-progress action.' );
		self::assertStringContainsString( 'data-post-id="' . $post_id . '"', $html, 'Actions target the requested post.' );
		self::assertStringContainsString( 'data-user-id="' . $user_id . '"', $html, 'Actions target the listed student.' );
	}

	public function listingTypes() {
		return array(
			'course' => array( 'course' ),
			'lesson' => array( 'lesson' ),
		);
	}

	public function studentNameSortCases() {
		return array(
			'course ascending'  => array( 'course', 'asc', array( 1, 2, 0 ) ),
			'course descending' => array( 'course', 'desc', array( 0, 2, 1 ) ),
			'lesson ascending'  => array( 'lesson', 'asc', array( 1, 2, 0 ) ),
			'lesson descending' => array( 'lesson', 'desc', array( 0, 2, 1 ) ),
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

<?php

/**
 * Sensei Reports Overview Data Provider Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Data_Provider_Courses
 */
class Sensei_Reports_Overview_Data_Provider_Courses_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Whether this test switched to HPPS table repositories.
	 *
	 * @var bool
	 */
	private $hpps_repository_enabled = false;

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
		if ( $this->hpps_repository_enabled ) {
			$this->maybe_reset_hpps_repository();
		}

		parent::tearDown();

		$this->factory->tearDown();
	}

	public function testGetItems_FiltersWithoutLastActivityGiven_ReturnsMatchingCourses() {
		/* Arrange. */
		$user_id = $this->factory->user->create();

		$course_id  = $this->factory->course->create();
		$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, 'complete' );
		update_comment_meta( $comment_id, 'start', '2022-01-01 00:00:01' );
		wp_update_comment(
			[
				'comment_ID'   => $comment_id,
				'comment_date' => '2022-01-02 00:00:01',
			]
		);

		$unfinished_course_id  = $this->factory->course->create();
		$unfinished_comment_id = Sensei_Utils::update_course_status( $user_id, $unfinished_course_id, 'in-progress' );

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$filters = array(
			'number'  => 2,
			'offset'  => 0,
			'orderby' => '',
			'order'   => 'ASC',
		);
		$courses = $data_provider->get_items( $filters );

		/* Assert. */
		$expected = [
			[
				'id'                   => $course_id,
				'last_activity_date'   => null,
				'days_to_completion'   => '2',
				'count_of_completions' => '1',
			],
			[
				'id'                   => $unfinished_course_id,
				'last_activity_date'   => null,
				'days_to_completion'   => null,
				'count_of_completions' => '0',
			],
		];

		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	public function testGetItems_FiltersWithLastActivityGiven_ReturnsMatchingCourses() {
		/* Arrange. */
		$user_id    = $this->factory->user->create();
		$course_id  = $this->factory->course->create();
		$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, 'complete' );
		update_comment_meta( $comment_id, 'start', '2022-01-01 00:00:01' );
		wp_update_comment(
			[
				'comment_ID'   => $comment_id,
				'comment_date' => '2022-01-02 00:00:01',
			]
		);

		$lesson1_id                  = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $course_id ] ] );
		$lesson1_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson1_id, $user_id );
		wp_update_comment(
			[
				'comment_ID'       => $lesson1_activity_comment_id,
				'comment_approved' => 'complete',
				'comment_date'     => '2022-01-02 00:00:01',
			]
		);

		$unfinished_course_id  = $this->factory->course->create();
		$unfinished_comment_id = Sensei_Utils::update_course_status( $user_id, $unfinished_course_id, 'in-progress' );
		wp_update_comment(
			[
				'comment_ID'   => $unfinished_comment_id,
				'comment_date' => '2022-01-01 00:00:01',
			]
		);

		$lesson2_id                  = $this->factory->lesson->create( [ 'meta_input' => [ '_lesson_course' => $unfinished_course_id ] ] );
		$lesson2_activity_comment_id = Sensei_Utils::sensei_start_lesson( $lesson2_id, $user_id );
		wp_update_comment(
			[
				'comment_ID'       => $lesson2_activity_comment_id,
				'comment_approved' => 'complete',
				'comment_date'     => '2022-01-01 00:00:01',
			]
		);

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$filters = array(
			'number'                  => 2,
			'offset'                  => 0,
			'orderby'                 => '',
			'order'                   => 'ASC',
			'last_activity_date_from' => '2022-01-02 00:00:00',
			'last_activity_date_to'   => '2022-01-03 23:59:59',
		);
		$courses = $data_provider->get_items( $filters );

		/* Assert. */
		$expected = [
			[
				'id'                   => $course_id,
				'last_activity_date'   => '2022-01-02 00:00:01',
				'days_to_completion'   => '2',
				'count_of_completions' => '1',
			],
		];

		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	public function testGetItems_TemporaryUserProgressCreated_ReturnsMatchingCourse() {
		/* Arrange. */
		$this->maybe_enable_hpps_tables_repository();
		$this->hpps_repository_enabled = self::is_hpps_tables_mode();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$started   = current_datetime();
		$completed = $started;

		foreach ( array( 'sensei_guest_student', 'sensei_preview_student' ) as $login ) {
			$user_id = $this->factory->user->create( array( 'user_login' => $login ) );

			$course_progress = Sensei()->course_progress_repository->create( $course_id, $user_id );
			$course_progress->start( $started );
			$course_progress->complete( $completed );
			Sensei()->course_progress_repository->save( $course_progress );

			$lesson_progress = Sensei()->lesson_progress_repository->create( $lesson_id, $user_id );
			$lesson_progress->start( $started );
			$lesson_progress->complete( $completed );
			Sensei()->lesson_progress_repository->save( $lesson_progress );
		}

		$data_provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$courses = $data_provider->get_items(
			array(
				'number'  => 1,
				'offset'  => 0,
				'orderby' => '',
				'order'   => 'ASC',
			)
		);

		/* Assert. */
		// Temporary-only progress contributes no completion days; the course remains in the report.
		$expected = array(
			array(
				'id'                   => $course_id,
				'last_activity_date'   => null,
				'days_to_completion'   => null,
				'count_of_completions' => '0',
			),
		);

		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	/**
	 * Published and private courses remain eligible for completion rows.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 */
	public function testGetItems_MixedCoursePostStatusesGiven_ReturnsOnlyPublishedAndPrivateCompletionRows(): void {
		/* Arrange. */
		$this->maybe_enable_hpps_tables_repository();
		$this->hpps_repository_enabled = self::is_hpps_tables_mode();
		$user                          = $this->factory->user->create();
		$course_ids                    = array();
		foreach ( array( 'publish', 'private', 'draft', 'trash' ) as $status ) {
			$course_ids[ $status ] = $this->factory->course->create( array( 'post_status' => $status ) );
			$progress              = Sensei()->course_progress_repository->create( $course_ids[ $status ], $user );
			$progress->complete();
			Sensei()->course_progress_repository->save( $progress );
		}
		$provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$actual = $provider->get_items(
			array(
				'number'  => 10,
				'offset'  => 0,
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		/* Assert. */
		self::assertEqualsCanonicalizing( array( $course_ids['publish'], $course_ids['private'] ), wp_list_pluck( $actual, 'ID' ), 'Only published and private courses should appear.' );
		self::assertSame( array( '1', '1' ), wp_list_pluck( $actual, 'count_of_completions' ), 'Each eligible course must retain its completion count.' );
	}

	/**
	 * Translated course records expose completions stored under the original course ID.
	 *
	 * @covers Sensei_Reports_Overview_Data_Provider_Courses::get_items
	 */
	public function testGetItems_TranslatedCourseWithoutStartDateGiven_ReturnsStoredCompletions(): void {
		/* Arrange. */
		$this->maybe_enable_hpps_tables_repository();
		$this->hpps_repository_enabled = self::is_hpps_tables_mode();
		$original                      = $this->factory->course->create();
		$translation                   = $this->factory->course->create();
		$user                          = $this->factory->user->create();
		$progress                      = Sensei()->course_progress_repository->create( $original, $user );
		$progress->complete();
		Sensei()->course_progress_repository->save( $progress );
		add_filter(
			'sensei_course_progress_get_course_id',
			static function ( $id ) use ( $original, $translation ) {
				return $translation === $id ? $original : $id;
			}
		);
		add_filter(
			'sensei_analysis_overview_filter_courses',
			static function ( $args ) use ( $translation ) {
				$args['post__in'] = array( $translation );
				return $args;
			}
		);
		$provider = new Sensei_Reports_Overview_Data_Provider_Courses();

		/* Act. */
		$actual = $provider->get_items(
			array(
				'number'  => 1,
				'offset'  => 0,
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		/* Assert. */
		self::assertSame( $translation, $actual[0]->ID, 'The translated course should be returned.' );
		self::assertSame( '1', $actual[0]->count_of_completions, 'The course should expose the completion stored under the original ID.' );
	}

	private function exportCourses( array $courses ): array {
		$ret = [];

		foreach ( $courses as $course ) {
			$ret[] = [
				'id'                   => $course->ID,
				'last_activity_date'   => $course->last_activity_date,
				'days_to_completion'   => $course->days_to_completion,
				'count_of_completions' => $course->count_of_completions,
			];
		}

		return $ret;
	}
}

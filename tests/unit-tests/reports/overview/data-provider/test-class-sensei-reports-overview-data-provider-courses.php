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
				'days_to_completion'   => '2',
				'count_of_completions' => '1',
			],
			[
				'id'                   => $unfinished_course_id,
				'days_to_completion'   => null,
				'count_of_completions' => '0',
			],
		];

		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	public function testGetAll_FiltersWithLastActivity_ReturnsMatchingCourses() {
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
				'days_to_completion'   => '2',
				'count_of_completions' => '1',
			],
		];

		self::assertSame( $expected, $this->exportCourses( $courses ) );
	}

	public function testGetItems_TemporaryUserProgressCreated_PreservesCompletionCountAndExcludesLastActivity() {
		/* Arrange. */
		$this->maybe_enable_hpps_tables_repository();
		$this->hpps_repository_enabled = self::is_hpps_tables_mode();

		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_course' => $course_id ) ) );
		$started   = new DateTimeImmutable( '2022-01-01 00:00:01', wp_timezone() );
		$completed = new DateTimeImmutable( '2022-01-02 00:00:01', wp_timezone() );

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
		self::assertSame(
			array(
				'last_activity_date'   => null,
				'count_of_completions' => '2',
			),
			array(
				'last_activity_date'   => $courses[0]->last_activity_date,
				'count_of_completions' => $courses[0]->count_of_completions,
			)
		);
	}

	private function exportCourses( array $courses ): array {
		$ret = [];

		foreach ( $courses as $course ) {
			$ret[] = [
				'id'                   => $course->ID,
				'days_to_completion'   => $course->days_to_completion,
				'count_of_completions' => $course->count_of_completions,
			];
		}

		return $ret;
	}
}

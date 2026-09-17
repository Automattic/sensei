<?php

/**
 * Sensei Reports Overview Service Courses Test Class
 *
 * @covers Sensei_Reports_Overview_Service_Courses
 */
class Sensei_Reports_Overview_Service_Courses_Test extends WP_UnitTestCase {

	private static $initial_hook_suffix;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

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

		$instance = new Sensei_Reports_Overview_Service_Courses();
		$actual   = $instance->get_average_days_to_completion( [ $course_id ] );

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

		$instance = new Sensei_Reports_Overview_Service_Courses();
		$actual   = $instance->get_average_days_to_completion( [ $course1_id, $course2_id ] );

		// Average for the first course: (1 + 1) / 2 = 1.
		// Average for the second course: 4 / 1 = 4.
		// Total: (1 + 4) / 2 = 2.5.
		self::assertSame( 2.5, $actual );
	}

	public function testGetAverageDaysToCompletionTotalWithoutCompletionsReturnsZero() {
		$instance = new Sensei_Reports_Overview_Service_Courses();
		$actual   = $instance->get_average_days_to_completion( [] );

		self::assertSame( 0.0, $actual );
	}

	/**
	 * Tests that average grade returns zero when courses have no graded quizzes.
	 *
	 * @covers Sensei_Reports_Overview_Service_Courses::get_courses_average_grade
	 */
	public function testGetCoursesAverageGrade_WhenNoGradedQuizzes_ReturnsZero() {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$instance  = new Sensei_Reports_Overview_Service_Courses();

		/* Act. */
		$actual = $instance->get_courses_average_grade( [ $course_id ] );

		/* Assert. */
		self::assertSame( 0.0, $actual, 'Average grade should be zero when there are no graded quizzes.' );
	}
}

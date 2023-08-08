<?php
/**
 * Tests for Sensei_Course_Progress_Block class.
 */
class Sensei_Course_Progress_Block_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	/**
	 * Course Progress block.
	 *
	 * @var Sensei_Course_Progress_Block
	 */
	private $block;

	/**
	 * Factory for setting up testing data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Block content.
	 */
	const CONTENT = '<!-- wp:sensei-lms/course-progress /-->';

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->prepareEnrolmentManager();

		$this->factory = new Sensei_Factory();
		$this->block   = new Sensei_Course_Progress_Block();
		$this->course  = $this->factory->course->create_and_get( [ 'post_name' => 'course-progress-block' ] );

		$GLOBALS['post'] = $this->course;
	}

	public function tearDown(): void {
		parent::tearDown();
		WP_Block_Type_Registry::get_instance()->unregister( 'sensei-lms/course-progress' );
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Doesn't render the block if it's not running in a course context.
	 *
	 * @covers Sensei_Course_Progress_Block::render_course_progress
	 */
	public function testRenderCourseProgress_Page_ReturnsEmptyString() {
		// Update the global post object ID to be the course ID, but change its post type to a page.
		$GLOBALS['post'] = (object) [
			'post_type' => 'page',
		];

		$result = $this->block->render_course_progress( [], self::CONTENT );

		$this->assertEmpty( $result );
	}

	public function testCourseProgressHeading_WhenRendered_ShowsProperPercentageRoundedToInteger() {
		/* Arrange */
		$course_lessons  = $this->factory->get_course_with_lessons(
			array(
				'lesson_count' => 3,
			)
		);
		$GLOBALS['post'] = $course_lessons['course_id'];
		$lesson_id       = array_pop( $course_lessons['lesson_ids'] );
		$user_id         = $this->factory->user->create();

		$this->login_as( $user_id );
		$this->manuallyEnrolStudentInCourse( $user_id, $course_lessons['course_id'] );
		Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id, true );

		/* Act */
		$result = $this->block->render_course_progress( [ 'postId' => $course_lessons['course_id'] ], self::CONTENT );

		/* Assert */
		$this->assertStringContainsString( '(33%)', $result );
	}
}

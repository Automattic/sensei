<?php
/**
 * AJAX tests for the grading lessons dropdown endpoint (`get_lessons_dropdown`).
 *
 * @group ajax-calls
 *
 * @covers Sensei_Grading::get_lessons_dropdown
 * @covers Sensei_Grading::lessons_drop_down_html
 */
class Sensei_Grading_Lessons_Dropdown_AJAX_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * HTTP status the handler died with, captured by the wrapped die handler.
	 *
	 * @var int|null
	 */
	private $die_status;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		add_filter( 'pre_http_request', '__return_empty_array' );

		// Sensei only hooks this handler when it loads in an admin context, which the
		// test bootstrap is not.
		add_action( 'wp_ajax_get_lessons_dropdown', array( Sensei()->grading, 'get_lessons_dropdown' ) );

		// The die handler of WP_Ajax_UnitTestCase only receives the message, so wrap it to
		// record the status the handler died with.
		add_filter(
			'wp_die_ajax_handler',
			function () {
				return function ( $message, $title = '', $args = array() ) {
					$this->die_status = $args['response'] ?? null;
					$this->dieHandler( $message );
				};
			},
			20
		);
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGetLessonsDropdown_CalledWithoutGradingCapability_RefusesTheRequest() {
		/* Arrange. */
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $this->create_course_with_private_lesson( $teacher, 'Secret Lesson Alpha' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		/* Act. */
		$response = $this->get_lessons_dropdown( $course_id );

		/* Assert. */
		$this->assertSame( '', $response, 'A user without the grading capability should get an empty response.' );
		$this->assertSame( 403, $this->die_status, 'A user without the grading capability should be refused with a 403.' );
	}

	public function testGetLessonsDropdown_CalledByTeacherForAnotherTeachersCourse_ExcludesTheirLessons() {
		/* Arrange. */
		$teacher_a = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $this->create_course_with_private_lesson( $teacher_a, 'Secret Lesson Beta' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );

		/* Act. */
		$response = $this->get_lessons_dropdown( $course_id );

		/* Assert. */
		$this->assertStringNotContainsString( 'Secret Lesson Beta', $response, "A teacher should not see another teacher's lesson." );
	}

	public function testGetLessonsDropdown_CalledByTeacherForTheirOwnCourse_IncludesOwnLessons() {
		/* Arrange. */
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $this->create_course_with_private_lesson( $teacher, 'Secret Lesson Gamma' );

		wp_set_current_user( $teacher );

		/* Act. */
		$response = $this->get_lessons_dropdown( $course_id );

		/* Assert. */
		$this->assertStringContainsString( 'Secret Lesson Gamma', $response, 'A teacher should still see their own lesson.' );
	}

	public function testGetLessonsDropdown_CalledByAdminForAnotherAuthorsCourse_IncludesTheirLessons() {
		/* Arrange. */
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $this->create_course_with_private_lesson( $teacher, 'Secret Lesson Delta' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		/* Act. */
		$response = $this->get_lessons_dropdown( $course_id );

		/* Assert. */
		$this->assertStringContainsString( 'Secret Lesson Delta', $response, "An admin should see other authors' lessons." );
	}

	/**
	 * Create a course owned by the given teacher with one private lesson attached to it.
	 *
	 * @param int    $teacher_id   Author of both the course and the lesson.
	 * @param string $lesson_title Title of the private lesson.
	 * @return int Course ID.
	 */
	private function create_course_with_private_lesson( int $teacher_id, string $lesson_title ): int {
		$course_id = $this->factory->course->create( array( 'post_author' => $teacher_id ) );
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_title'  => $lesson_title,
				'post_status' => 'private',
				'post_author' => $teacher_id,
			)
		);
		update_post_meta( $lesson_id, '_lesson_course', $course_id );

		return $course_id;
	}

	/**
	 * Dispatch the `get_lessons_dropdown` AJAX action and return the raw response.
	 *
	 * @param int $course_id Course to build the dropdown for.
	 * @return string Response body.
	 */
	private function get_lessons_dropdown( int $course_id ): string {
		$_GET['course_id'] = $course_id;

		try {
			// Stop is thrown when the handler dies without printing anything, as a denied
			// request does; Continue when it dies after printing the dropdown.
			$this->_handleAjax( 'get_lessons_dropdown' );
		} catch ( WPAjaxDieStopException | WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return $this->_last_response;
	}
}

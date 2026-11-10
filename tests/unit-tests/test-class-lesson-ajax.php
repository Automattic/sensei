<?php
/**
 * Authorization tests for the read-only AJAX handlers of Sensei_Lesson.
 *
 * @group ajax-calls
 *
 * @covers Sensei_Lesson::get_question_category_limit
 * @covers Sensei_Lesson::quiz_panel_filter_existing_questions
 * @covers Sensei_Lesson::handle_get_prerequisite_meta_box_content
 */
class Sensei_Lesson_AJAX_Test extends WP_Ajax_UnitTestCase {

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

		// Sensei only hooks these handlers when it loads in an admin context, which the
		// test bootstrap is not.
		add_action( 'wp_ajax_get_question_category_limit', array( Sensei()->lesson, 'get_question_category_limit' ) );
		add_action( 'wp_ajax_filter_existing_questions', array( Sensei()->lesson, 'quiz_panel_filter_existing_questions' ) );
		add_action( 'wp_ajax_get_prerequisite_meta_box_content', array( Sensei()->lesson, 'handle_get_prerequisite_meta_box_content' ) );

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

	public function testGetQuestionCategoryLimit_SubscriberGiven_RefusesTheRequest() {
		/* Arrange. */
		$_GET['cat'] = $this->create_question_category_with_one_question();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		/* Act. */
		$this->dispatch( 'get_question_category_limit' );

		/* Assert. */
		$this->assertSame( 403, $this->die_status );
	}

	public function testGetQuestionCategoryLimit_TeacherGiven_ReturnsTheCategoryCount() {
		/* Arrange. */
		$_GET['cat'] = $this->create_question_category_with_one_question();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );

		/* Act. */
		$this->dispatch( 'get_question_category_limit' );

		/* Assert. */
		$this->assertSame( '1', $this->_last_response );
	}

	public function testQuizPanelFilterExistingQuestions_SubscriberWithNonceGiven_RefusesTheRequest() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['filter_existing_questions_nonce'] = wp_create_nonce( 'filter_existing_questions_nonce' );
		$_POST['data']                            = 'question_status=all&question_page=1';

		/* Act. */
		$this->dispatch( 'filter_existing_questions' );

		/* Assert. */
		$this->assertSame( 403, $this->die_status );
	}

	public function testQuizPanelFilterExistingQuestions_TeacherWithNonceGiven_ReturnsTheQuestions() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );
		$_POST['filter_existing_questions_nonce'] = wp_create_nonce( 'filter_existing_questions_nonce' );
		$_POST['data']                            = 'question_status=all&question_page=1';

		/* Act. */
		$this->dispatch( 'filter_existing_questions' );

		/* Assert. */
		$this->assertArrayHasKey( 'html', json_decode( $this->_last_response, true ) );
	}

	public function testHandleGetPrerequisiteMetaBoxContent_CourseOfAnotherTeacherGiven_RefusesTheRequest() {
		/* Arrange. */
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $this->create_course_with_draft_lesson( $this->factory->user->create( array( 'role' => 'teacher' ) ), 'Secret draft lesson' );
		wp_set_current_user( $teacher );

		/* Act. */
		$this->dispatch_prerequisite_meta_box_content( $this->factory->lesson->create( array( 'post_author' => $teacher ) ), $course_id );

		/* Assert. */
		$this->assertSame( 403, $this->die_status );
	}

	public function testHandleGetPrerequisiteMetaBoxContent_LessonOfAnotherTeacherGiven_RefusesTheRequest() {
		/* Arrange. */
		$teacher         = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$other_lesson_id = $this->factory->lesson->create( array( 'post_author' => $this->factory->user->create( array( 'role' => 'teacher' ) ) ) );
		wp_set_current_user( $teacher );

		/* Act. */
		$this->dispatch_prerequisite_meta_box_content( $other_lesson_id, $this->factory->course->create( array( 'post_author' => $teacher ) ) );

		/* Assert. */
		$this->assertSame( 403, $this->die_status );
	}

	public function testHandleGetPrerequisiteMetaBoxContent_OwnCourseGiven_ReturnsItsDraftLessons() {
		/* Arrange. */
		$teacher   = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$course_id = $this->create_course_with_draft_lesson( $teacher, 'Own draft lesson' );
		wp_set_current_user( $teacher );

		/* Act. */
		$this->dispatch_prerequisite_meta_box_content( $this->factory->lesson->create( array( 'post_author' => $teacher ) ), $course_id );

		/* Assert. */
		$this->assertStringContainsString( 'Own draft lesson', $this->_last_response );
	}

	/**
	 * Create a question category holding one published question.
	 *
	 * @return int Term ID.
	 */
	private function create_question_category_with_one_question(): int {
		$term        = wp_insert_term( 'Category with one question', 'question-category' );
		$question_id = $this->factory->post->create( array( 'post_type' => 'question' ) );
		wp_set_object_terms( $question_id, array( $term['term_id'] ), 'question-category' );

		return $term['term_id'];
	}

	/**
	 * Create a course owned by the given user with one draft lesson attached to it.
	 *
	 * @param int    $author_id    Author of both the course and the lesson.
	 * @param string $lesson_title Title of the draft lesson.
	 * @return int Course ID.
	 */
	private function create_course_with_draft_lesson( int $author_id, string $lesson_title ): int {
		$course_id = $this->factory->course->create( array( 'post_author' => $author_id ) );
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_title'  => $lesson_title,
				'post_status' => 'draft',
				'post_author' => $author_id,
			)
		);
		update_post_meta( $lesson_id, '_lesson_course', $course_id );

		return $course_id;
	}

	/**
	 * Dispatch the prerequisite meta box request for the given lesson and course, as the lesson editor sends it.
	 *
	 * @param int $lesson_id Lesson being edited.
	 * @param int $course_id Course selected in the editor.
	 */
	private function dispatch_prerequisite_meta_box_content( int $lesson_id, int $course_id ): void {
		$_GET['lesson_id'] = $lesson_id;
		$_GET['course_id'] = $course_id;
		$_GET['security']  = wp_create_nonce( 'get_prerequisite_meta_box_content_nonce' );

		$this->dispatch( 'get_prerequisite_meta_box_content' );
	}

	/**
	 * Dispatch an AJAX action, swallowing the handler's wp_die().
	 *
	 * @param string $action Action name.
	 */
	private function dispatch( string $action ): void {
		try {
			// Stop is thrown when the handler dies without printing anything, as a denied
			// request does; Continue when it dies after printing its response.
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieStopException | WPAjaxDieContinueException $e ) {
			unset( $e );
		}
	}
}

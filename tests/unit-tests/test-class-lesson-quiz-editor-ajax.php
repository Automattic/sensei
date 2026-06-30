<?php
/**
 * Object-level authorization tests for the legacy quiz editor AJAX handlers.
 *
 * These ensure a teacher who does not own the lesson backing a quiz cannot
 * mutate that quiz through the handlers, while the owner still can. They drive
 * the real handlers end-to-end, so removing a `user_can_edit_quiz()` guard
 * makes the corresponding "not owner" test fail.
 *
 * @group ajax-calls
 */
class Sensei_Lesson_Quiz_Editor_AJAX_Test extends WP_Ajax_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		add_filter( 'pre_http_request', '__return_empty_array' );

		// The handlers are only hooked under is_admin(), which was false when the
		// Sensei_Lesson singleton was constructed at bootstrap. Register them so
		// _handleAjax() can dispatch to them.
		add_action( 'wp_ajax_lesson_update_grade_type', array( Sensei()->lesson, 'lesson_update_grade_type' ) );
		add_action( 'wp_ajax_lesson_update_question_order_random', array( Sensei()->lesson, 'lesson_update_question_order_random' ) );
		add_action( 'wp_ajax_lesson_add_multiple_questions', array( Sensei()->lesson, 'lesson_add_multiple_questions' ) );
		add_action( 'wp_ajax_lesson_remove_multiple_questions', array( Sensei()->lesson, 'lesson_remove_multiple_questions' ) );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', '__return_empty_array' );
		$this->factory->tearDown();
		parent::tearDown();
	}

	/**
	 * Create a quiz whose backing lesson is owned by the given teacher.
	 *
	 * @param int $owner_id Lesson/quiz owner.
	 * @return array{0:int,1:int} Lesson id and quiz id.
	 */
	private function create_quiz_for_owner( $owner_id ) {
		$lesson_id = $this->factory->lesson->create( array( 'post_author' => $owner_id ) );
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_author' => $owner_id,
				'meta_input'  => array( '_quiz_lesson' => $lesson_id ),
			)
		);
		return array( $lesson_id, $quiz_id );
	}

	/**
	 * Dispatch an AJAX action, swallowing the handler's wp_die().
	 *
	 * @param string $action Action name.
	 */
	private function dispatch( $action ) {
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}
	}

	public function testUpdateGradeType_WhenNotLessonOwner_DoesNotChangeQuiz() {
		$attacker          = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		update_post_meta( $quiz_id, '_quiz_grade_type', 'manual' );

		wp_set_current_user( $attacker );
		$_POST['lesson_update_grade_type_nonce'] = wp_create_nonce( 'lesson_update_grade_type_nonce' );
		$_POST['data']                           = "quiz_id={$quiz_id}&quiz_grade_type=auto";

		$this->dispatch( 'lesson_update_grade_type' );

		$this->assertSame( 'manual', get_post_meta( $quiz_id, '_quiz_grade_type', true ), 'A non-owner must not change the quiz grade type.' );
	}

	public function testUpdateGradeType_WhenLessonOwner_ChangesQuiz() {
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		update_post_meta( $quiz_id, '_quiz_grade_type', 'manual' );

		wp_set_current_user( $owner );
		$_POST['lesson_update_grade_type_nonce'] = wp_create_nonce( 'lesson_update_grade_type_nonce' );
		$_POST['data']                           = "quiz_id={$quiz_id}&quiz_grade_type=auto";

		$this->dispatch( 'lesson_update_grade_type' );

		$this->assertSame( 'auto', get_post_meta( $quiz_id, '_quiz_grade_type', true ), 'The lesson owner should change the quiz grade type.' );
	}

	public function testUpdateQuestionOrderRandom_WhenNotLessonOwner_DoesNotChangeQuiz() {
		$attacker          = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		update_post_meta( $quiz_id, '_random_question_order', 'no' );

		wp_set_current_user( $attacker );
		$_POST['lesson_update_question_order_random_nonce'] = wp_create_nonce( 'lesson_update_question_order_random_nonce' );
		$_POST['data']                                      = "quiz_id={$quiz_id}&random_question_order=yes";

		$this->dispatch( 'lesson_update_question_order_random' );

		$this->assertSame( 'no', get_post_meta( $quiz_id, '_random_question_order', true ), 'A non-owner must not change the random question order.' );
	}

	public function testUpdateQuestionOrderRandom_WhenLessonOwner_ChangesQuiz() {
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		update_post_meta( $quiz_id, '_random_question_order', 'no' );

		wp_set_current_user( $owner );
		$_POST['lesson_update_question_order_random_nonce'] = wp_create_nonce( 'lesson_update_question_order_random_nonce' );
		$_POST['data']                                      = "quiz_id={$quiz_id}&random_question_order=yes";

		$this->dispatch( 'lesson_update_question_order_random' );

		$this->assertSame( 'yes', get_post_meta( $quiz_id, '_random_question_order', true ), 'The lesson owner should change the random question order.' );
	}

	public function testAddMultipleQuestions_WhenNotLessonOwner_DoesNotAddQuestionGroup() {
		$attacker          = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		$category          = $this->factory->term->create( array( 'taxonomy' => 'question-category' ) );

		wp_set_current_user( $attacker );
		$_POST['lesson_add_multiple_questions_nonce'] = wp_create_nonce( 'lesson_add_multiple_questions_nonce' );
		$_POST['data']                                = "quiz_id={$quiz_id}&question_number=2&question_category={$category}&question_count=0";

		$this->dispatch( 'lesson_add_multiple_questions' );

		$this->assertCount( 0, $this->question_groups_for_quiz( $quiz_id ), 'A non-owner must not create a question group on the quiz.' );
	}

	public function testAddMultipleQuestions_WhenLessonOwner_AddsQuestionGroup() {
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		$category          = $this->factory->term->create( array( 'taxonomy' => 'question-category' ) );

		wp_set_current_user( $owner );
		$_POST['lesson_add_multiple_questions_nonce'] = wp_create_nonce( 'lesson_add_multiple_questions_nonce' );
		$_POST['data']                                = "quiz_id={$quiz_id}&question_number=2&question_category={$category}&question_count=0";

		$this->dispatch( 'lesson_add_multiple_questions' );

		$this->assertCount( 1, $this->question_groups_for_quiz( $quiz_id ), 'The lesson owner should create a question group on the quiz.' );
	}

	public function testRemoveMultipleQuestions_WhenNotLessonOwner_DoesNotRemoveGroup() {
		$attacker          = $this->factory->user->create( array( 'role' => 'teacher' ) );
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		$group             = $this->create_question_group( $quiz_id, $owner );

		wp_set_current_user( $attacker );
		$_POST['lesson_remove_multiple_questions_nonce'] = wp_create_nonce( 'lesson_remove_multiple_questions_nonce' );
		$_POST['data']                                   = "question_id={$group}&quiz_id={$quiz_id}";

		$this->dispatch( 'lesson_remove_multiple_questions' );

		$this->assertContains( (string) $quiz_id, array_map( 'strval', get_post_meta( $group, '_quiz_id', false ) ), 'A non-owner must not detach the question group from the quiz.' );
		$this->assertNotNull( get_post( $group ), 'A non-owner must not delete the question group.' );
	}

	public function testRemoveMultipleQuestions_WhenLessonOwner_RemovesGroup() {
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		$group             = $this->create_question_group( $quiz_id, $owner );

		wp_set_current_user( $owner );
		$_POST['lesson_remove_multiple_questions_nonce'] = wp_create_nonce( 'lesson_remove_multiple_questions_nonce' );
		$_POST['data']                                   = "question_id={$group}&quiz_id={$quiz_id}";

		$this->dispatch( 'lesson_remove_multiple_questions' );

		$this->assertNull( get_post( $group ), 'The lesson owner should remove the orphaned question group.' );
	}

	/**
	 * Create a multiple_question group attached to a quiz.
	 *
	 * @param int $quiz_id Quiz id.
	 * @param int $author  Group author.
	 * @return int Group post id.
	 */
	private function create_question_group( $quiz_id, $author ) {
		$group = wp_insert_post(
			array(
				'post_type'   => 'multiple_question',
				'post_status' => 'publish',
				'post_title'  => 'Group',
				'post_author' => $author,
			)
		);
		add_post_meta( $group, '_quiz_id', $quiz_id, false );
		return $group;
	}

	/**
	 * Question groups (multiple_question posts) attached to a quiz.
	 *
	 * @param int $quiz_id Quiz id.
	 * @return int[] Group post ids.
	 */
	private function question_groups_for_quiz( $quiz_id ) {
		return get_posts(
			array(
				'post_type'   => 'multiple_question',
				'post_status' => 'any',
				'fields'      => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Tiny dataset in a test.
				'meta_key'    => '_quiz_id',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Tiny dataset in a test.
				'meta_value'  => $quiz_id,
			)
		);
	}
}

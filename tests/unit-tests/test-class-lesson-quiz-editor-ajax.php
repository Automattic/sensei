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

		// The handlers are only hooked under is_admin(), which was false when the
		// Sensei_Lesson singleton was constructed at bootstrap. Register them so
		// _handleAjax() can dispatch to them.
		add_action( 'wp_ajax_lesson_update_grade_type', array( Sensei()->lesson, 'lesson_update_grade_type' ) );
		add_action( 'wp_ajax_lesson_update_question_order_random', array( Sensei()->lesson, 'lesson_update_question_order_random' ) );
		add_action( 'wp_ajax_lesson_add_multiple_questions', array( Sensei()->lesson, 'lesson_add_multiple_questions' ) );
		add_action( 'wp_ajax_lesson_remove_multiple_questions', array( Sensei()->lesson, 'lesson_remove_multiple_questions' ) );
	}

	public function tearDown(): void {
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

		$created = $this->dispatch_capturing_group_creation( 'lesson_add_multiple_questions' );

		$this->assertFalse( $created, 'A non-owner must not create a question group on the quiz.' );
	}

	public function testAddMultipleQuestions_WhenLessonOwner_AddsQuestionGroup() {
		$owner             = $this->factory->user->create( array( 'role' => 'teacher' ) );
		list( , $quiz_id ) = $this->create_quiz_for_owner( $owner );
		$category          = $this->factory->term->create( array( 'taxonomy' => 'question-category' ) );

		wp_set_current_user( $owner );
		$_POST['lesson_add_multiple_questions_nonce'] = wp_create_nonce( 'lesson_add_multiple_questions_nonce' );
		$_POST['data']                                = "quiz_id={$quiz_id}&question_number=2&question_category={$category}&question_count=0";

		$created = $this->dispatch_capturing_group_creation( 'lesson_add_multiple_questions' );

		$this->assertTrue( $created, 'The lesson owner should create a question group on the quiz.' );
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
	 * Dispatch lesson_add_multiple_questions and report whether it reached
	 * question-group creation. A save_post hook aborts the handler the moment a
	 * multiple_question is inserted, so the test never runs the handler's
	 * response rendering (which echoes markup and exits the process).
	 *
	 * @param string $action Action name.
	 * @return bool Whether a multiple_question was created.
	 */
	private function dispatch_capturing_group_creation( $action ) {
		$created = false;
		add_action(
			'save_post_multiple_question',
			function () use ( &$created ) {
				$created = true;
				throw new Sensei_Lesson_Quiz_Editor_Test_Abort();
			},
			1
		);

		// Aborting mid-insert skips _handleAjax's own ob_get_clean(), so close
		// any output buffer it left open to keep the buffer stack balanced.
		$ob_level = ob_get_level();
		try {
			$this->dispatch( $action );
		} catch ( Sensei_Lesson_Quiz_Editor_Test_Abort $e ) {
			unset( $e );
		} finally {
			while ( ob_get_level() > $ob_level ) {
				ob_end_clean();
			}
		}

		return $created;
	}
}

<?php

namespace SenseiTest\Admin\Content_Duplicators;

use Sensei\Admin\Content_Duplicators\Lesson_Quiz_Duplicator;
use Sensei_Factory;

/**
* Class Lesson_Quiz_Duplicator_Test
*
* @covers Sensei\Admin\Content_Duplicators\Lesson_Quiz_Duplicator
*/
class Lesson_Quiz_Duplicator_Test extends \WP_UnitTestCase {
	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function set_up(): void {
		parent::set_up();
		$this->factory = new Sensei_Factory();
	}

	public function tear_down(): void {
		parent::tear_down();
		$this->factory->tearDown();
	}

	public function testDuplicate_LessonIdsGiven_DuplicatesQuizForNewLesson(): void {
		/* Arrange */
		$old_lesson_id = $this->factory->get_lesson_with_quiz_and_questions();
		$new_lesson_id = $this->factory->lesson->create();

		$duplicator = new Lesson_Quiz_Duplicator();

		/* Act */
		$duplicator->duplicate( $old_lesson_id, $new_lesson_id );

		/* Assert */
		$new_quiz_id = Sensei()->lesson->lesson_quizzes( $new_lesson_id );
		$this->assertNotNull( $new_quiz_id );
	}

	public function testDuplicate_LessonWithQuizQuestions_CreatesSeparateQuestionPosts(): void {
		/* Arrange */
		$old_lesson_id = $this->factory->get_lesson_with_quiz_and_questions();
		$old_quiz_id   = Sensei()->lesson->lesson_quizzes( $old_lesson_id );
		$old_questions = Sensei()->quiz->get_questions( $old_quiz_id );

		$new_lesson_id = $this->factory->lesson->create();

		$duplicator = new Lesson_Quiz_Duplicator();

		/* Act */
		$duplicator->duplicate( $old_lesson_id, $new_lesson_id );

		/* Assert */
		$new_quiz_id   = Sensei()->lesson->lesson_quizzes( $new_lesson_id );
		$new_questions = Sensei()->quiz->get_questions( $new_quiz_id );

		$this->assertCount( count( $old_questions ), $new_questions, 'New quiz should have the same number of questions.' );

		// Verify question IDs are different (not shared).
		$old_question_ids = wp_list_pluck( $old_questions, 'ID' );
		$new_question_ids = wp_list_pluck( $new_questions, 'ID' );

		$this->assertEmpty(
			array_intersect( $old_question_ids, $new_question_ids ),
			'Duplicated quiz should have its own question posts, not shared with the original.'
		);
	}

	public function testDuplicate_QuestionsEditedInDuplicate_DoNotAffectOriginal(): void {
		/* Arrange */
		$old_lesson_id = $this->factory->get_lesson_with_quiz_and_questions();
		$old_quiz_id   = Sensei()->lesson->lesson_quizzes( $old_lesson_id );
		$old_questions = Sensei()->quiz->get_questions( $old_quiz_id );
		$first_question = reset( $old_questions );
		$original_title = $first_question->post_title;

		$new_lesson_id = $this->factory->lesson->create();

		$duplicator = new Lesson_Quiz_Duplicator();
		$duplicator->duplicate( $old_lesson_id, $new_lesson_id );

		/* Act — modify a question in the new quiz. */
		$new_quiz_id   = Sensei()->lesson->lesson_quizzes( $new_lesson_id );
		$new_questions = Sensei()->quiz->get_questions( $new_quiz_id );
		$new_question  = reset( $new_questions );

		wp_update_post(
			array(
				'ID'         => $new_question->ID,
				'post_title' => 'Modified Question Title',
			)
		);

		/* Assert — original question is unchanged. */
		$original_question = get_post( $first_question->ID );
		$this->assertSame( $original_title, $original_question->post_title, 'Original question should not be affected by edits to the duplicate.' );
	}

	public function testDuplicate_QuestionMetaCopiedToNewQuestions(): void {
		/* Arrange */
		$old_lesson_id = $this->factory->get_lesson_with_quiz_and_questions();
		$old_quiz_id   = Sensei()->lesson->lesson_quizzes( $old_lesson_id );
		$old_questions = Sensei()->quiz->get_questions( $old_quiz_id );
		$first_question = reset( $old_questions );
		$original_grade = get_post_meta( $first_question->ID, '_question_grade', true );

		$new_lesson_id = $this->factory->lesson->create();

		$duplicator = new Lesson_Quiz_Duplicator();

		/* Act */
		$duplicator->duplicate( $old_lesson_id, $new_lesson_id );

		/* Assert */
		$new_quiz_id   = Sensei()->lesson->lesson_quizzes( $new_lesson_id );
		$new_questions = Sensei()->quiz->get_questions( $new_quiz_id );
		$new_question  = reset( $new_questions );
		$new_grade     = get_post_meta( $new_question->ID, '_question_grade', true );

		$this->assertSame( $original_grade, $new_grade, 'Question grade meta should be copied to the new question.' );
	}

	public function testDuplicate_QuizBlockInPostContent_ReferencesNewQuizId(): void {
		/* Arrange */
		$old_lesson_id = $this->factory->get_lesson_with_quiz_and_questions();
		$old_quiz_id   = Sensei()->lesson->lesson_quizzes( $old_lesson_id );

		// Add quiz block with old quiz ID to lesson content.
		wp_update_post(
			array(
				'ID'           => $old_lesson_id,
				'post_content' => '<!-- wp:sensei-lms/quiz {"id":' . $old_quiz_id . '} -->
<!-- /wp:sensei-lms/quiz -->',
			)
		);

		$new_lesson_id = $this->factory->lesson->create(
			array(
				'post_content' => '<!-- wp:sensei-lms/quiz {"id":' . $old_quiz_id . '} -->
<!-- /wp:sensei-lms/quiz -->',
			)
		);

		$duplicator = new Lesson_Quiz_Duplicator();

		/* Act */
		$duplicator->duplicate( $old_lesson_id, $new_lesson_id );

		/* Assert */
		$new_quiz_id      = Sensei()->lesson->lesson_quizzes( $new_lesson_id );
		$new_lesson       = get_post( $new_lesson_id );
		$has_old_quiz_id  = strpos( $new_lesson->post_content, '"id":' . $old_quiz_id ) !== false;
		$has_new_quiz_id  = strpos( $new_lesson->post_content, '"id":' . $new_quiz_id ) !== false;

		$this->assertFalse( $has_old_quiz_id, 'Duplicated lesson content should not reference the old quiz ID.' );
		$this->assertTrue( $has_new_quiz_id, 'Duplicated lesson content should reference the new quiz ID.' );
	}
}

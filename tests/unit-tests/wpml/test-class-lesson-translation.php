<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Lesson_Translation;
use Sensei_Factory;

/**
 * Class Lesson_Translation_Test
 *
 * @covers \Sensei\WPML\Lesson_Translation
 */
class Lesson_Translation_Test extends \WP_UnitTestCase {
	/**
	 * Sensei Factory.
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

	public function testUpdateLessonTranslationsOnLessonTranslationCreated_WhenCalled_CreatesLessonTranslations() {
		/* Arrange. */
		$question_category   = $this->factory->question_category->create_and_get();
		$course_with_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'            => 1,
				'question_count'          => 3,
				'multiple_question_count' => 1,
				'multiple_question_args'  => array(
					'question_category_id' => $question_category->term_id,
				),
			)
		);
		$this->factory->question->create_many(
			3,
			array(
				'quiz_id'           => $course_with_lessons['quiz_ids'][0],
				'question_category' => $question_category->term_id,
			)
		);

		$new_course_id = $this->factory->course->create();
		$new_lesson_id = $this->factory->lesson->create();

		$lesson_translation = new Lesson_Translation();

		$element_language_details_filter = function () {
			return array(
				'language_code'        => 'a',
				'source_language_code' => 'c',
			);
		};
		add_filter( 'wpml_element_language_details', $element_language_details_filter, 10, 0 );

		$object_id_fitler = function ( $object_id, $element_type ) use ( $new_lesson_id, $new_course_id, $course_with_lessons ) {
			if ( $new_lesson_id === $object_id && 'lesson' === $element_type ) {
				return $course_with_lessons['lesson_ids'][0];
			}

			if ( $course_with_lessons['course_id'] === $object_id && 'course' === $element_type ) {
				return $new_course_id;
			}

			return 0;
		};
		add_filter( 'wpml_object_id', $object_id_fitler, 10, 2 );

		$created_duplicates           = array();
		$copy_post_to_language_filter = function ( $id ) use ( &$created_duplicates ) {
			$created_duplicates[] = $id;
			return 1;
		};
		add_filter( 'wpml_copy_post_to_language', $copy_post_to_language_filter );

		$post_duplicates_filter = function ( $post_id ) use ( $new_lesson_id, $course_with_lessons ) {
			if ( $post_id === $course_with_lessons['lesson_ids'][0] ) {
				return array(
					'a' => $new_lesson_id,
				);
			}

			return array();
		};

		add_filter( 'wpml_post_duplicates', $post_duplicates_filter, 10, 1 );

		/* Act. */
		$lesson_translation->update_lesson_translations_on_lesson_translation_created( $new_lesson_id );

		/* Clean up & Assert. */
		remove_filter( 'wpml_element_language_details', $element_language_details_filter );
		remove_filter( 'wpml_object_id', $object_id_fitler );
		remove_action( 'wpml_copy_post_to_language', $copy_post_to_language_filter );
		remove_filter( 'wpml_post_duplicates', $post_duplicates_filter );

		// 1 lesson, 3 questions, 1 multiple question, 3 questions inside the multiple question.
		$actual = count( array_unique( $created_duplicates ) );
		$this->assertSame( 8, $actual );
	}

	public function testUpdateLessonTranslationsOnLessonTranslationCreated_TranslatedContentWrittenAfterCreation_UpdatesTranslatedQuestion() {
		/* Arrange. */
		$course_with_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);

		$master_lesson_id   = $course_with_lessons['lesson_ids'][0];
		$master_quiz_id     = $course_with_lessons['quiz_ids'][0];
		$master_question_id = \Sensei()->quiz->get_questions( $master_quiz_id )[0]->ID;

		// WPML creates the translated posts empty at the start of a delivery; the
		// question begins as a copy that still holds the source-language strings.
		$translated_lesson_id   = $this->factory->lesson->create( array( 'post_content' => '' ) );
		$translated_quiz_id     = $this->factory->quiz->create();
		$translated_question_id = $this->factory->post->create(
			array(
				'post_type'  => 'question',
				'post_title' => 'Question',
			)
		);

		$this->simulate_wpml_translation(
			$master_lesson_id,
			$translated_lesson_id,
			$master_quiz_id,
			$translated_quiz_id,
			$master_question_id,
			$translated_question_id
		);

		$lesson_translation = new Lesson_Translation();
		$lesson_translation->init();

		/* Act. */
		// WPML fires the completion hook while the lesson is still empty, then
		// writes the translated block content later in the same request.
		$lesson_translation->update_lesson_translations_on_lesson_translation_created( $translated_lesson_id );
		wp_update_post(
			array(
				'ID'           => $translated_lesson_id,
				'post_content' => $this->translated_lesson_content( $master_question_id ),
			)
		);

		/* Assert. */
		$translated_question = get_post( $translated_question_id );
		$this->assertSame( 'Pregunta', $translated_question->post_title, 'The translated question title should be updated from the lesson content.' );
		$this->assertSame( array( 'sí' ), get_post_meta( $translated_question_id, '_question_right_answer', true ), 'The right answer should be updated from the lesson content.' );
		$this->assertSame( array( 'no' ), get_post_meta( $translated_question_id, '_question_wrong_answers', true ), 'The wrong answers should be updated from the lesson content.' );
	}

	/**
	 * Register the WPML hooks that stand in for a real lesson translation: the
	 * language details, the source/translation id map, and quiz duplication.
	 *
	 * @param int $source_lesson_id       Source (original language) lesson ID.
	 * @param int $translated_lesson_id   Translated lesson ID.
	 * @param int $source_quiz_id         Source quiz ID.
	 * @param int $translated_quiz_id     Translated quiz ID.
	 * @param int $source_question_id     Source question ID.
	 * @param int $translated_question_id Translated question ID.
	 */
	private function simulate_wpml_translation( $source_lesson_id, $translated_lesson_id, $source_quiz_id, $translated_quiz_id, $source_question_id, $translated_question_id ) {
		add_filter(
			'wpml_element_language_details',
			function () {
				return array(
					'language_code'        => 'es',
					'source_language_code' => 'en',
				);
			},
			10,
			0
		);

		// Map a post to its counterpart in the other language, per element type.
		add_filter(
			'wpml_object_id',
			function ( $object_id, $element_type ) use ( $source_lesson_id, $translated_lesson_id, $source_question_id, $translated_question_id ) {
				$map = array(
					'lesson'   => array(
						$translated_lesson_id => $source_lesson_id,
						$source_lesson_id     => $translated_lesson_id,
					),
					'question' => array(
						$source_question_id => $translated_question_id,
					),
				);
				return $map[ $element_type ][ $object_id ] ?? 0;
			},
			10,
			2
		);

		// Duplicating the quiz (or a question) into Spanish returns the
		// translated post created for the test instead of making a new one.
		add_filter(
			'wpml_copy_post_to_language',
			function ( $post_id ) use ( $source_quiz_id, $translated_quiz_id, $translated_question_id ) {
				return $source_quiz_id === $post_id ? $translated_quiz_id : $translated_question_id;
			}
		);
	}

	/**
	 * Translated lesson content: a quiz holding one translated question. The
	 * question block still references the source-language question id.
	 *
	 * @param int $source_question_id Source question ID referenced by the block.
	 * @return string Serialized block content.
	 */
	private function translated_lesson_content( $source_question_id ) {
		return '<!-- wp:sensei-lms/quiz -->' . "\n" .
			'<!-- wp:sensei-lms/quiz-question {"id":' . $source_question_id . ',"title":"Pregunta","answer":{"answers":[{"label":"sí","correct":true},{"label":"no","correct":false}]},"options":{"grade":1}} -->' . "\n" .
			'<!-- /wp:sensei-lms/quiz-question -->' . "\n" .
			'<!-- /wp:sensei-lms/quiz -->';
	}
}

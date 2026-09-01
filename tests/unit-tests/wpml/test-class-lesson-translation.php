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

	public function testUpdateLessonTranslationsOnLessonTranslationCreated_QuizNotYetTranslated_SyncsQuizTitleFromTranslatedLesson() {
		/* Arrange. */
		$course_with_lessons = $this->factory->get_course_with_lessons(
			array(
				'lesson_count'   => 1,
				'question_count' => 1,
			)
		);
		$master_lesson_id    = $course_with_lessons['lesson_ids'][0];
		$master_quiz_id      = $course_with_lessons['quiz_ids'][0];

		$translated_lesson_id = $this->factory->lesson->create(
			array(
				'post_title' => 'Lección 1',
				'post_name'  => 'leccion-1',
			)
		);
		// The quiz translation is a verbatim copy of the master, so it carries the master's title.
		$translated_quiz_id     = $this->factory->quiz->create( array( 'post_title' => 'Lesson 1' ) );
		$translated_question_id = $this->factory->post->create( array( 'post_type' => 'question' ) );

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

		add_filter(
			'wpml_object_id',
			function ( $object_id, $element_type ) use ( $translated_lesson_id, $master_lesson_id ) {
				if ( 'lesson' === $element_type && $translated_lesson_id === $object_id ) {
					return $master_lesson_id;
				}
				if ( 'lesson' === $element_type && $master_lesson_id === $object_id ) {
					return $translated_lesson_id;
				}
				return 0;
			},
			10,
			2
		);

		add_filter(
			'wpml_copy_post_to_language',
			function ( $post_id ) use ( $master_quiz_id, $translated_quiz_id, $translated_question_id ) {
				return $master_quiz_id === $post_id ? $translated_quiz_id : $translated_question_id;
			}
		);

		add_filter(
			'wpml_post_duplicates',
			function () {
				return array();
			},
			10,
			0
		);

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_lesson_translations_on_lesson_translation_created( $translated_lesson_id );

		/* Assert. */
		$translated_quiz = get_post( $translated_quiz_id );
		$this->assertSame( 'Lección 1', $translated_quiz->post_title, 'The translated quiz title should be synced from the translated lesson.' );
		$this->assertSame( 'leccion-1', $translated_quiz->post_name, 'The translated quiz slug should be synced from the translated lesson.' );
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
		// question begins as a copy that still holds the source-language strings
		// and answer order.
		$translated_lesson_id   = $this->factory->lesson->create( array( 'post_content' => '' ) );
		$translated_quiz_id     = $this->factory->quiz->create();
		$translated_question_id = $this->factory->post->create(
			array(
				'post_type'  => 'question',
				'post_title' => 'Question',
			)
		);
		update_post_meta(
			$translated_question_id,
			'_answer_order',
			\Sensei()->lesson->get_answer_id( 'yes' ) . ',' . \Sensei()->lesson->get_answer_id( 'no' )
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

		/* Clean up. */
		remove_all_filters( 'wpml_element_language_details' );
		remove_all_filters( 'wpml_object_id' );
		remove_all_filters( 'wpml_copy_post_to_language' );
		remove_action( 'wpml_pro_translation_completed', array( $lesson_translation, 'update_lesson_translations_on_lesson_translation_created' ) );
		remove_action( 'wp_after_insert_post', array( $lesson_translation, 'update_question_translations_on_lesson_content_written' ) );

		/* Assert. */
		$translated_question = get_post( $translated_question_id );
		$this->assertSame( 'Pregunta', $translated_question->post_title, 'The translated question title should be updated from the lesson content.' );
		$this->assertSame( array( 'sí' ), get_post_meta( $translated_question_id, '_question_right_answer', true ), 'The right answer should be updated from the lesson content.' );
		$this->assertSame( array( 'no' ), get_post_meta( $translated_question_id, '_question_wrong_answers', true ), 'The wrong answers should be updated from the lesson content.' );
		$this->assertSame( \Sensei()->lesson->get_answer_id( 'sí' ) . ',' . \Sensei()->lesson->get_answer_id( 'no' ), get_post_meta( $translated_question_id, '_answer_order', true ), 'The answer order should be recomputed from the translated labels in block order.' );
		$this->assertStringContainsString( 'Descripción ES', $translated_question->post_content, 'The question description should be updated from the lesson content.' );
		$this->assertStringContainsString( 'Feedback correcto ES', \Sensei_Quiz::get_correct_answer_feedback( $translated_question_id ), 'The question feedback blocks should be updated from the lesson content.' );
	}

	public function testUpdateLessonTranslationsOnLessonTranslationCreated_TranslatedLessonsWithoutOrderMetaGiven_PlacesNewTranslationLast() {
		/* Arrange. */
		$master_course_id     = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		$master_lesson_ids = $this->create_course_lessons(
			$master_course_id,
			array( '2024-01-11 10:00:00', '2024-01-12 10:00:00', '2024-01-13 10:00:00', '2024-01-14 10:00:00' ),
			true
		);
		// Lessons translated before the order sync existed carry no order meta.
		$translated_lesson_ids = $this->create_course_lessons(
			$translated_course_id,
			array( '2024-02-11 10:00:00', '2024-02-12 10:00:00', '2024-02-13 10:00:00', '2024-02-14 10:00:00' ),
			false
		);
		$new_lesson_id         = $translated_lesson_ids[3];

		$this->simulate_wpml_language_pair(
			array_combine( $master_lesson_ids, $translated_lesson_ids ) + array( $master_course_id => $translated_course_id )
		);

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_lesson_translations_on_lesson_translation_created( $new_lesson_id );

		/* Clean up & Assert. */
		$this->remove_wpml_language_pair_filters();
		$this->assertSame( $translated_lesson_ids, Sensei()->course->course_lessons( $translated_course_id, 'any', 'ids' ) );
	}

	public function testUpdateLessonTranslationsOnLessonTranslationCreated_LessonsWithoutMasterCounterpartGiven_KeepsThemLastInRelativeOrder() {
		/* Arrange. */
		$master_course_id     = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		$master_lesson_ids = $this->create_course_lessons(
			$master_course_id,
			array( '2024-01-11 10:00:00', '2024-01-12 10:00:00' ),
			true
		);
		// Lessons that only exist in the translated course. Their dates are
		// earlier than the translated counterparts', so without the sync the
		// date fallback would list them first.
		$orphan_lesson_ids     = $this->create_course_lessons(
			$translated_course_id,
			array( '2024-02-11 10:00:00', '2024-02-12 10:00:00' ),
			false
		);
		$translated_lesson_ids = $this->create_course_lessons(
			$translated_course_id,
			array( '2024-02-13 10:00:00', '2024-02-14 10:00:00' ),
			false
		);
		$new_lesson_id         = $translated_lesson_ids[1];

		$this->simulate_wpml_language_pair(
			array_combine( $master_lesson_ids, $translated_lesson_ids ) + array( $master_course_id => $translated_course_id )
		);

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_lesson_translations_on_lesson_translation_created( $new_lesson_id );

		/* Clean up & Assert. */
		$this->remove_wpml_language_pair_filters();
		$this->assertSame(
			array_merge( $translated_lesson_ids, $orphan_lesson_ids ),
			Sensei()->course->course_lessons( $translated_course_id, 'any', 'ids' )
		);
	}

	public function testUpdateLessonPropertiesOnLessonDuplicated_DuplicatedLessonGiven_AttachesItToTranslatedCourse() {
		/* Arrange. */
		$master_course_id     = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		$master_lesson_ids    = $this->create_course_lessons( $master_course_id, array( '2024-01-11 10:00:00' ), true );
		$duplicated_lesson_id = $this->create_lesson_duplicate( $master_lesson_ids[0], $master_course_id );

		$this->simulate_wpml_language_pair(
			array(
				$master_lesson_ids[0] => $duplicated_lesson_id,
				$master_course_id     => $translated_course_id,
			)
		);

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_lesson_properties_on_lesson_duplicated( $master_lesson_ids[0], 'es', array(), $duplicated_lesson_id );

		/* Clean up & Assert. */
		$this->remove_wpml_language_pair_filters();
		$this->assertSame( $translated_course_id, (int) get_post_meta( $duplicated_lesson_id, '_lesson_course', true ) );
	}

	public function testUpdateQuizOnLessonDuplicated_DuplicatedLessonGiven_GivesItItsOwnQuiz() {
		/* Arrange. */
		$master_lesson_id     = $this->factory->get_lesson_with_quiz_and_questions();
		$master_quiz_id       = Sensei()->lesson->lesson_quizzes( $master_lesson_id );
		$duplicated_lesson_id = $this->factory->lesson->create( array( 'meta_input' => array( '_lesson_quiz' => $master_quiz_id ) ) );

		$this->simulate_wpml_language_pair( array( $master_lesson_id => $duplicated_lesson_id ) );
		$copied_quiz_id   = $this->factory->quiz->create();
		$copy_to_language = function () use ( $copied_quiz_id ) {
			return $copied_quiz_id;
		};
		add_filter( 'wpml_copy_post_to_language', $copy_to_language );

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_quiz_on_lesson_duplicated( $master_lesson_id, 'es', array(), $duplicated_lesson_id );

		/* Clean up & Assert. */
		remove_filter( 'wpml_copy_post_to_language', $copy_to_language );
		$this->remove_wpml_language_pair_filters();
		$this->assertSame( $copied_quiz_id, (int) get_post_meta( $duplicated_lesson_id, '_lesson_quiz', true ), 'The duplicated lesson should own the quiz copied for its language, not the master one.' );
	}

	public function testUpdateQuizOnLessonDuplicated_DuplicatedCourseGiven_DoesNothing() {
		/* Arrange. */
		$master_lesson_id     = $this->factory->lesson->create();
		$duplicated_course_id = $this->factory->course->create();

		$this->simulate_wpml_language_pair( array( $master_lesson_id => $duplicated_course_id ) );
		$copies           = 0;
		$copy_to_language = function ( $post_id ) use ( &$copies ) {
			++$copies;
			return $post_id;
		};
		add_filter( 'wpml_copy_post_to_language', $copy_to_language );

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_quiz_on_lesson_duplicated( $master_lesson_id, 'es', array(), $duplicated_course_id );

		/* Clean up & Assert. */
		remove_filter( 'wpml_copy_post_to_language', $copy_to_language );
		$this->remove_wpml_language_pair_filters();
		$this->assertSame( 0, $copies, 'Duplicating something that is not a lesson should not copy quizzes.' );
	}

	public function testUpdateLessonPropertiesOnLessonDuplicated_TranslatedLessonsWithoutOrderMetaGiven_PlacesDuplicateLast() {
		/* Arrange. */
		$master_course_id     = $this->factory->course->create();
		$translated_course_id = $this->factory->course->create();

		$master_lesson_ids = $this->create_course_lessons(
			$master_course_id,
			array( '2024-01-11 10:00:00', '2024-01-12 10:00:00', '2024-01-13 10:00:00', '2024-01-14 10:00:00' ),
			true
		);
		// Lessons translated before the order sync existed carry no order meta.
		$translated_lesson_ids = $this->create_course_lessons(
			$translated_course_id,
			array( '2024-02-11 10:00:00', '2024-02-12 10:00:00', '2024-02-13 10:00:00' ),
			false
		);
		$duplicated_lesson_id  = $this->create_lesson_duplicate( $master_lesson_ids[3], $master_course_id );

		$this->simulate_wpml_language_pair(
			array_combine( $master_lesson_ids, array_merge( $translated_lesson_ids, array( $duplicated_lesson_id ) ) )
			+ array( $master_course_id => $translated_course_id )
		);

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_lesson_properties_on_lesson_duplicated( $master_lesson_ids[3], 'es', array(), $duplicated_lesson_id );

		/* Clean up & Assert. */
		$this->remove_wpml_language_pair_filters();
		$this->assertSame(
			array_merge( $translated_lesson_ids, array( $duplicated_lesson_id ) ),
			Sensei()->course->course_lessons( $translated_course_id, 'any', 'ids' )
		);
	}

	public function testUpdateLessonPropertiesOnLessonDuplicated_CourseWithoutTranslationGiven_KeepsMasterCourse() {
		/* Arrange. */
		$master_course_id     = $this->factory->course->create();
		$master_lesson_ids    = $this->create_course_lessons( $master_course_id, array( '2024-01-11 10:00:00' ), true );
		$duplicated_lesson_id = $this->create_lesson_duplicate( $master_lesson_ids[0], $master_course_id );

		$this->simulate_wpml_language_pair(
			array( $master_lesson_ids[0] => $duplicated_lesson_id )
		);

		$lesson_translation = new Lesson_Translation();

		/* Act. */
		$lesson_translation->update_lesson_properties_on_lesson_duplicated( $master_lesson_ids[0], 'es', array(), $duplicated_lesson_id );

		/* Clean up & Assert. */
		$this->remove_wpml_language_pair_filters();
		$this->assertSame( $master_course_id, (int) get_post_meta( $duplicated_lesson_id, '_lesson_course', true ) );
	}

	/**
	 * Create lessons attached to a course, one per post date, in the given order.
	 *
	 * @param int      $course_id  Course the lessons belong to.
	 * @param string[] $post_dates One lesson is created per date.
	 * @param bool     $set_order  Whether to write the 1-based `_order_{course_id}` meta.
	 *
	 * @return int[] Lesson IDs, in the order given.
	 */
	private function create_course_lessons( $course_id, $post_dates, $set_order ) {
		$lesson_ids = array();
		foreach ( $post_dates as $index => $post_date ) {
			$lesson_id = $this->factory->lesson->create(
				array(
					'post_date'  => $post_date,
					'meta_input' => array( '_lesson_course' => $course_id ),
				)
			);

			if ( $set_order ) {
				update_post_meta( $lesson_id, '_order_' . $course_id, $index + 1 );
			}

			$lesson_ids[] = $lesson_id;
		}

		return $lesson_ids;
	}

	/**
	 * Create a lesson the way WPML's duplicate flow leaves it: custom fields
	 * copied verbatim from the master, so it points at the master course and
	 * carries the master course's order meta key.
	 *
	 * @param int $master_lesson_id Master lesson ID.
	 * @param int $master_course_id Master course ID.
	 *
	 * @return int Duplicated lesson ID.
	 */
	private function create_lesson_duplicate( $master_lesson_id, $master_course_id ) {
		$duplicated_lesson_id = $this->factory->lesson->create(
			array(
				'post_date'  => '2024-02-14 10:00:00',
				'meta_input' => array( '_lesson_course' => $master_course_id ),
			)
		);
		update_post_meta(
			$duplicated_lesson_id,
			'_order_' . $master_course_id,
			(int) get_post_meta( $master_lesson_id, '_order_' . $master_course_id, true )
		);

		return $duplicated_lesson_id;
	}

	/**
	 * Stand in for WPML: fixed es/en language details, and a wpml_object_id
	 * map resolving each post to its counterpart in the other language.
	 *
	 * @param array $counterparts Map of post ID to counterpart post ID (registered in both directions).
	 */
	private function simulate_wpml_language_pair( $counterparts ) {
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

		$map = array();
		foreach ( $counterparts as $original_id => $counterpart_id ) {
			$map[ $original_id ]    = $counterpart_id;
			$map[ $counterpart_id ] = $original_id;
		}

		add_filter(
			'wpml_object_id',
			function ( $object_id, $element_type ) use ( $map ) {
				$counterpart_id = $map[ $object_id ] ?? 0;
				return $counterpart_id && get_post_type( $counterpart_id ) === $element_type ? $counterpart_id : 0;
			},
			10,
			2
		);
	}

	/**
	 * Remove the filters registered by simulate_wpml_language_pair().
	 */
	private function remove_wpml_language_pair_filters() {
		remove_all_filters( 'wpml_element_language_details' );
		remove_all_filters( 'wpml_object_id' );
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
			'<!-- wp:sensei-lms/question-description -->' . "\n" .
			'<p>Descripción ES</p>' . "\n" .
			'<!-- /wp:sensei-lms/question-description -->' . "\n" .
			'<!-- wp:sensei-lms/quiz-question-feedback-correct -->' . "\n" .
			'<p>Feedback correcto ES</p>' . "\n" .
			'<!-- /wp:sensei-lms/quiz-question-feedback-correct -->' . "\n" .
			'<!-- /wp:sensei-lms/quiz-question -->' . "\n" .
			'<!-- /wp:sensei-lms/quiz -->';
	}
}

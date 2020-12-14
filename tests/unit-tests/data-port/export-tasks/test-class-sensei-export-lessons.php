<?php
/**
 * This file contains the Sensei_Export_Lessons_Tests class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Export_Lessons class.
 *
 * @group data-port
 */
class Sensei_Export_Lessons_Tests extends WP_UnitTestCase {

	use Sensei_Export_Task_Tests;

	/**
	 * Factory helper.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}


	/**
	 * Test that lesson details are exported correctly.
	 */
	public function testLessonContentExported() {
		$lesson = $this->factory->lesson->create_and_get();

		$result = $this->export();

		$this->assertArraySubset(
			[
				'id'          => $lesson->ID,
				'lesson'      => $lesson->post_title,
				'slug'        => $lesson->post_name,
				'description' => $lesson->post_content,
				'excerpt'     => $lesson->post_excerpt,
				'status'      => $lesson->post_status,
			],
			$result[0]
		);
	}

	/**
	 * Test that lesson image is exported correctly.
	 */
	public function testLessonImageExported() {
		$lesson = $this->factory->lesson->create_and_get();

		$thumbnail_id = $this->factory->attachment->create(
			[
				'file'           => 'lesson-img.png',
				'post_mime_type' => 'image/png',
			]
		);
		set_post_thumbnail( $lesson, $thumbnail_id );

		$result = $this->export();

		$this->assertArraySubset(
			[
				'image' => 'http://example.org/wp-content/uploads/lesson-img.png',
			],
			$result[0]
		);
	}

	/**
	 * Test that lesson module is exported correctly.
	 */
	public function testModulesExported() {
		$lesson = $this->factory->lesson->create_and_get();

		$terms = [
			$this->factory->term->create(
				[
					'taxonomy' => Sensei()->modules->taxonomy,
					'name'     => 'Module Title',
				]
			),
		];
		$this->factory->term->add_post_terms( $lesson->ID, $terms, Sensei()->modules->taxonomy, false );

		$result = $this->export();

		$this->assertEquals(
			'Module Title',
			$result[0]['module']
		);
	}

	/**
	 * Test that lesson associated prerequisite are exported correctly.
	 */
	public function testLessonAssociatedPostsExported() {
		$lesson              = $this->factory->lesson->create_and_get();
		$prerequisite_lesson = $this->factory->lesson->create_and_get();

		update_post_meta( $lesson->ID, '_lesson_prerequisite', $prerequisite_lesson->ID );

		$result = $this->export();

		$this->assertArraySubset(
			[
				'prerequisite' => 'id:' . $prerequisite_lesson->ID,
			],
			$result[0]
		);
	}

	/**
	 * Test that lesson questions are exported correctly.
	 */
	public function testLessonQuestionsExported() {
		$lesson_id    = $this->factory->get_lesson_graded_quiz();
		$quiz_id      = Sensei()->lesson->lesson_quizzes( $lesson_id );
		$question_ids = Sensei_Utils::lesson_quiz_questions(
			$quiz_id,
			[
				'fields'   => 'ids',
				'meta_key' => '_quiz_question_order' . $quiz_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Using the lessons sorter.
				'orderby'  => 'meta_value_num title',
			]
		);

		$result = $this->export();

		$this->assertArraySubset(
			[
				'questions' => 'id:' . implode( ',id:', $question_ids ),
			],
			$result[0]
		);
	}

	/**
	 * Test that lesson tags are exported correctly.
	 */
	public function testLessonTagsExported() {
		$lesson = $this->factory->lesson->create_and_get();

		$terms = [
			$this->factory->term->create(
				[
					'taxonomy' => 'lesson-tag',
					'name'     => 'Lesson Tag \'Single\'',
				]
			),
			$this->factory->term->create(
				[
					'taxonomy' => 'lesson-tag',
					'name'     => 'Lesson Tag "Double"',
				]
			),
		];
		$this->factory->term->add_post_terms( $lesson->ID, $terms, 'lesson-tag', false );

		$result = $this->export();

		$this->assertEquals(
			'Lesson Tag \'Single\',Lesson Tag "Double"',
			$result[0]['tags']
		);
	}

	/**
	 * Test that lesson metas are exported correctly.
	 *
	 * @param array $expected  Expected data.
	 * @param array $data      Data to process.
	 * @param int   $lesson_id Lesson ID.
	 *
	 * @dataProvider lessonMetaData
	 */
	public function testLessonMetaExported( $expected, $data ) {
		$lesson = $this->factory->lesson->create_and_get();

		foreach ( $data as $key => $value ) {
			update_post_meta( $lesson->ID, $key, $value );
		}

		$result = $this->export();

		$this->assertArraySubset( $expected, $result[0] );
	}

	/**
	 * Data source for lesson meta tests.
	 *
	 * @return array[]
	 */
	public function lessonMetaData() {
		return [
			[
				[
					'preview'    => 1,
					'length'     => 10,
					'complexity' => 'easy',
					'video'      => '<iframe>',
				],
				[
					'_lesson_preview'     => 'preview',
					'_lesson_length'      => 10,
					'_lesson_complexity'  => 'easy',
					'_lesson_video_embed' => '<iframe>',
				],
			],
			[
				[
					'preview' => 0,
				],
				[
					'_lesson_preview' => '',
				],
			],
		];
	}

	/**
	 * Test that quiz metas are exported correctly.
	 *
	 * @param array $expected Expected data.
	 * @param array $data     Data to process.
	 *
	 * @dataProvider quizMetaData
	 */
	public function testQuizMetaExported( $expected, $data ) {
		$lesson  = $this->factory->lesson->create_and_get();
		$quiz_id = $this->factory->maybe_create_quiz_for_lesson(
			$lesson->ID,
			[
				'meta_input' => $data,
			]
		);

		$result = $this->export();

		$this->assertArraySubset( $expected, $result[0] );
	}

	/**
	 * Data source for quiz meta tests.
	 *
	 * @return array[]
	 */
	public function quizMetaData() {
		return [
			[
				[
					'pass required'         => 1,
					'passmark'              => 88,
					'number of questions'   => 3,
					'random question order' => 1,
					'auto-grade'            => 1,
					'quiz reset'            => 1,
				],
				[
					'_pass_required'         => 'on',
					'_quiz_passmark'         => 88,
					'_show_questions'        => 3,
					'_random_question_order' => 'yes',
					'_quiz_grade_type'       => 'auto',
					'_enable_quiz_reset'     => 'on',
				],
			],
			[
				[
					'pass required'         => 0,
					'passmark'              => 0,
					'number of questions'   => '',
					'random question order' => 0,
					'auto-grade'            => 0,
					'quiz reset'            => 0,
				],
				[
					'_pass_required'         => '',
					'_quiz_passmark'         => 0,
					'_show_questions'        => '',
					'_random_question_order' => 'no',
					'_quiz_grade_type'       => 'manual',
					'_enable_quiz_reset'     => '',
				],
			],
		];
	}

	protected function get_task_class() {
		return Sensei_Export_Lessons::class;
	}
}

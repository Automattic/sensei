<?php
/**
 * This file contains the Sensei_Export_Job_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tests for Sensei_Export_Job class.
 *
 * @group data-port
 */
class Sensei_Export_Job_Test extends WP_UnitTestCase {

	/**
	 * Factory helper.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Setup function.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tear down after tests.
	 */
	public function tearDown(): void {
		$this->factory->tearDown();
		parent::tearDown();
	}

	/**
	 * Selections payload sanitisation: missing types default to empty arrays.
	 */
	public function testSetSelectionsNormalizesMissingTypesToEmptyArrays() {
		$job = Sensei_Export_Job::create( 'sel-1', 0 );

		$job->set_selections( array( 'course' => array( 1, 2, 3 ) ) );

		$this->assertSame(
			array(
				'course'   => array( 1, 2, 3 ),
				'lesson'   => array(),
				'question' => array(),
			),
			$job->get_selections(),
			'Missing types should default to empty arrays.'
		);
	}

	/**
	 * Selections payload sanitisation: dedupes and casts to int.
	 */
	public function testSetSelectionsDedupesAndCastsIds() {
		$job = Sensei_Export_Job::create( 'sel-2', 0 );

		$job->set_selections(
			array(
				'course'   => array( '12', 12, '34' ),
				'lesson'   => array(),
				'question' => array( 5, '5', 7 ),
			)
		);

		$selections = $job->get_selections();

		$this->assertSame( array( 12, 34 ), $selections['course'], 'Course IDs should be deduped and cast to int.' );
		$this->assertSame( array( 5, 7 ), $selections['question'], 'Question IDs should be deduped and cast to int.' );
	}

	/**
	 * No selection set: resolved IDs are empty for all types ("export all").
	 */
	public function testResolveExportIdsWithoutSelectionsKeepsResolvedEmpty() {
		$job = Sensei_Export_Job::create( 'res-empty', 0 );

		$job->resolve_export_ids();

		$this->assertSame( array(), $job->get_resolved_ids( 'course' ), 'Empty selection should produce empty course resolved list (export all).' );
		$this->assertSame( array(), $job->get_resolved_ids( 'lesson' ), 'Empty selection should produce empty lesson resolved list (export all).' );
		$this->assertSame( array(), $job->get_resolved_ids( 'question' ), 'Empty selection should produce empty question resolved list (export all).' );
	}

	/**
	 * Selecting specific courses cascades to their lessons and the questions in those lessons' quizzes.
	 */
	public function testResolveExportIdsCascadesCoursesToLessonsAndQuestions() {
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$question_ids = $this->factory->question->create_many( 2 );
		$quiz_id      = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		add_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		foreach ( $question_ids as $i => $qid ) {
			add_post_meta( $qid, '_quiz_id', $quiz_id );
			add_post_meta( $qid, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $i );
		}

		// Unrelated content that should NOT be in the resolved closure.
		$unrelated_course   = $this->factory->course->create();
		$unrelated_lesson   = $this->factory->lesson->create();
		$unrelated_question = $this->factory->question->create();

		$job = Sensei_Export_Job::create( 'res-cascade-course', 0 );
		$job->set_selections( array( 'course' => array( $course_id ) ) );
		$job->set_mode( Sensei_Export_Job::MODE_BY_COURSE );
		$job->resolve_export_ids();

		$this->assertSame( array( $course_id ), $job->get_resolved_ids( 'course' ), 'Selected course should be in resolved list.' );
		$this->assertSame( array( $lesson_id ), $job->get_resolved_ids( 'lesson' ), 'Lessons of selected course should cascade.' );

		$resolved_questions = $job->get_resolved_ids( 'question' );
		sort( $resolved_questions );
		sort( $question_ids );
		$this->assertSame( $question_ids, $resolved_questions, 'Questions in the selected course\'s lessons should cascade.' );

		$this->assertNotContains( $unrelated_course, $job->get_resolved_ids( 'course' ), 'Unrelated course should not be resolved.' );
		$this->assertNotContains( $unrelated_lesson, $job->get_resolved_ids( 'lesson' ), 'Unrelated lesson should not be resolved.' );
		$this->assertNotContains( $unrelated_question, $job->get_resolved_ids( 'question' ), 'Unrelated question should not be resolved.' );
	}

	/**
	 * Selecting specific lessons cascades to questions but does not touch courses.
	 */
	public function testResolveExportIdsCascadesLessonsToQuestionsOnly() {
		$lesson_id    = $this->factory->lesson->create();
		$question_ids = $this->factory->question->create_many( 2 );
		$quiz_id      = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		add_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		foreach ( $question_ids as $i => $qid ) {
			add_post_meta( $qid, '_quiz_id', $quiz_id );
			add_post_meta( $qid, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $i );
		}

		$job = Sensei_Export_Job::create( 'res-cascade-lesson', 0 );
		$job->set_selections( array( 'lesson' => array( $lesson_id ) ) );
		$job->set_mode( Sensei_Export_Job::MODE_BY_COURSE );
		$job->resolve_export_ids();

		$this->assertSame( array(), $job->get_resolved_ids( 'course' ), 'Course resolved list should remain empty when only lessons are selected.' );
		$this->assertSame( array( $lesson_id ), $job->get_resolved_ids( 'lesson' ), 'Selected lesson should be in resolved list.' );

		$resolved_questions = $job->get_resolved_ids( 'question' );
		sort( $resolved_questions );
		sort( $question_ids );
		$this->assertSame( $question_ids, $resolved_questions, 'Questions of selected lesson should cascade.' );
	}

	/**
	 * Selecting only questions does not affect courses or lessons.
	 */
	public function testResolveExportIdsQuestionsAreLeafNodes() {
		$question_ids = $this->factory->question->create_many( 2 );

		$job = Sensei_Export_Job::create( 'res-questions-only', 0 );
		$job->set_selections( array( 'question' => $question_ids ) );
		$job->set_mode( Sensei_Export_Job::MODE_BY_COURSE );
		$job->resolve_export_ids();

		$this->assertSame( array(), $job->get_resolved_ids( 'course' ), 'Course resolved list should be empty.' );
		$this->assertSame( array(), $job->get_resolved_ids( 'lesson' ), 'Lesson resolved list should be empty.' );

		$resolved_questions = $job->get_resolved_ids( 'question' );
		sort( $resolved_questions );
		sort( $question_ids );
		$this->assertSame( $question_ids, $resolved_questions, 'Selected questions should be in resolved list.' );
	}

	/**
	 * In MODE_BY_FILE_TYPE, picking specific courses does NOT cascade to lessons or questions.
	 */
	public function testResolveExportIdsByFileTypeDoesNotCascade() {
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$question_ids = $this->factory->question->create_many( 2 );
		$quiz_id      = $this->factory->quiz->create( array( 'post_parent' => $lesson_id ) );
		add_post_meta( $lesson_id, '_lesson_quiz', $quiz_id );
		foreach ( $question_ids as $i => $qid ) {
			add_post_meta( $qid, '_quiz_id', $quiz_id );
			add_post_meta( $qid, '_quiz_question_order' . $quiz_id, $quiz_id . '000' . $i );
		}

		$job = Sensei_Export_Job::create( 'res-by-file-type', 0 );
		$job->set_selections( array( 'course' => array( $course_id ) ) );
		$job->set_mode( Sensei_Export_Job::MODE_BY_FILE_TYPE );
		$job->resolve_export_ids();

		$this->assertSame( array( $course_id ), $job->get_resolved_ids( 'course' ), 'Course resolved list should contain the picked course.' );
		$this->assertSame( array(), $job->get_resolved_ids( 'lesson' ), 'Lesson resolved list should be empty (no cascade in by_file_type mode).' );
		$this->assertSame( array(), $job->get_resolved_ids( 'question' ), 'Question resolved list should be empty (no cascade in by_file_type mode).' );
	}

	/**
	 * Mode defaults to MODE_BY_FILE_TYPE when unset, preserving literal interpretation.
	 */
	public function testGetModeDefaultsToByFileType() {
		$job = Sensei_Export_Job::create( 'mode-default', 0 );

		$this->assertSame(
			Sensei_Export_Job::MODE_BY_FILE_TYPE,
			$job->get_mode(),
			'Unset mode should default to MODE_BY_FILE_TYPE.'
		);
	}

	/**
	 * Invalid mode values are coerced to MODE_BY_FILE_TYPE.
	 */
	public function testSetModeRejectsInvalidValues() {
		$job = Sensei_Export_Job::create( 'mode-invalid', 0 );
		$job->set_mode( 'not_a_real_mode' );

		$this->assertSame(
			Sensei_Export_Job::MODE_BY_FILE_TYPE,
			$job->get_mode(),
			'Invalid mode values should fall back to MODE_BY_FILE_TYPE.'
		);
	}

	/**
	 * Explicit lesson + cascaded lesson are deduped in the resolved list.
	 */
	public function testResolveExportIdsDedupesUnionOfExplicitAndCascadedSelections() {
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create();
		add_post_meta( $lesson_id, '_lesson_course', $course_id );

		$job = Sensei_Export_Job::create( 'res-dedupe', 0 );
		$job->set_selections(
			array(
				'course' => array( $course_id ),
				'lesson' => array( $lesson_id ),
			)
		);
		$job->set_mode( Sensei_Export_Job::MODE_BY_COURSE );
		$job->resolve_export_ids();

		$this->assertSame(
			array( $lesson_id ),
			$job->get_resolved_ids( 'lesson' ),
			'Lesson selected explicitly and via course cascade should appear once.'
		);
	}
}

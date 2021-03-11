<?php
/**
 * This file contains the Sensei_Updates_Test class.
 *
 * @package sensei
 */

/**
 * Tests for the class `Sensei_Updates`.
 *
 * @group update-tasks
 */
class Sensei_Updates_Test extends WP_UnitTestCase {
	use Sensei_Scheduler_Test_Helpers;

	/**
	 * Sensei factory.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	/**
	 * Setup function.
	 */
	public function setUp() {
		parent::setUp();

		Sensei_Scheduler_Shim::reset();
		self::restoreShimScheduler();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		self::resetScheduler();

		return parent::tearDownAfterClass();
	}

	/**
	 * Test to make sure multiple question flag is calculated when updating from 3.8.0.
	 */
	public function testMultipleQuestionsCheckedWhenComingFrom38() {
		$this->setupQuizWithMultipleQuestion();

		$updates = new Sensei_Updates( '3.8.0', false, true );
		$updates->run_updates();

		$this->assertTrue( Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_MULTIPLE_QUESTIONS_EXIST ) );
	}

	/**
	 * Test to make sure multiple question flag is not recalculated when updating from 3.9.0.
	 */
	public function testMultipleQuestionsNotCheckedWhenComingFrom39() {
		$this->setupQuizWithMultipleQuestion();

		$updates = new Sensei_Updates( '3.9.0', false, true );
		$updates->run_updates();

		$this->assertFalse( Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_MULTIPLE_QUESTIONS_EXIST ) );
	}

	/**
	 * Test to make sure orphaned `multiple_questions` aren't used when calculating legacy flag.
	 */
	public function testOrphanedMultipleQuestionsIgnoredWhenSettingFlag() {
		$this->factory->quiz->create();
		$this->factory->multiple_question->create();

		$updates = new Sensei_Updates( '3.8.0', false, true );
		$updates->run_updates();

		$this->assertFalse( Sensei()->get_legacy_flag( Sensei_Main::LEGACY_FLAG_MULTIPLE_QUESTIONS_EXIST ) );
	}

	/**
	 * Test to make sure question update fix is enqueued when coming from 3.8.0.
	 */
	public function testFixQuestionsEnqueuedWhenComingFrom38() {
		$updates = new Sensei_Updates( '3.8.0', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertNotFalse( $next_scheduled );
	}

	/**
	 * Test to make sure question update fix is not enqueued in future.
	 */
	public function testFixQuestionsNotEnqueuedWhenComingFrom39() {
		$updates = new Sensei_Updates( '3.9.0', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );

		$updates = new Sensei_Updates( '3.9.1', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );
	}

	/**
	 * Test to make sure question update fix is not enqueued on fresh installs.
	 */
	public function testFixQuestionsNotEnqueuedOnNewInstalls() {
		$updates = new Sensei_Updates( null, true, false );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );

		$updates = new Sensei_Updates( '3.9.1', false, true );
		$updates->run_updates();

		$job            = new Sensei_Update_Fix_Question_Author();
		$next_scheduled = Sensei_Scheduler_Shim::get_next_scheduled( $job );
		$this->assertFalse( $next_scheduled );
	}

	/**
	 * Create a quiz with a multiple-question added.
	 */
	public function setupQuizWithMultipleQuestion() {
		$quiz_id = $this->factory->quiz->create();
		$this->factory->multiple_question->create(
			[
				'quiz_id'              => $quiz_id,
				'question_number'      => 3,
				'question_category_id' => 0,
			]
		);
	}
}

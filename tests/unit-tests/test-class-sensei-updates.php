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
	use Sensei_WP_Cron_Helpers;

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

		Sensei_Test_Events::reset();
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
	 * Test to make sure changelog parser generally works.
	 */
	public function testChangelogParser() {
		$updates = new Sensei_Updates( '3.9.0', false, true );

		$method = new ReflectionMethod( $updates, 'get_changelog_release_dates' );
		$method->setAccessible( true );

		$releases = $method->invoke( $updates );

		// Spot check a few releases.
		$this->assertArrayHasKey( '3.8.0', $releases );
		$this->assertEquals( '2021-02-09', $releases['3.8.0']->format( 'Y-m-d' ) );

		$this->assertArrayHasKey( '2.0.0', $releases );
		$this->assertEquals( '2019-04-02', $releases['2.0.0']->format( 'Y-m-d' ) );
	}

	/**
	 * Test that it fires an update event with `days_since_release` set to 0 when today.
	 */
	public function testDaysSinceReleaseToday() {
		$today = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		Sensei()->version = '3.9.0';
		$updates          = $this->getUpdateMockWithChangelog( [ '3.7.0', false, true ], $this->getChangelog( '3.9.0', $today ) );
		$updates->run_updates();
		$this->runAllScheduledEvents( 'sensei_log_update' );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_update' );

		$this->assertTrue( isset( $events[0]['url_args']['days_since_release'] ) );
		$this->assertEquals( '0', $events[0]['url_args']['days_since_release'] );
		$this->assertEquals( '3.7.0', $events[0]['url_args']['from_version'] );
		$this->assertEquals( Sensei()->version, $events[0]['url_args']['to_version'] );
	}

	/**
	 * Test that it fires an update event with `days_since_release` set to 1 when yesterday.
	 */
	public function testDaysSinceReleaseYesterday() {
		$yesterday = new DateTimeImmutable( 'yesterday', new DateTimeZone( 'UTC' ) );

		Sensei()->version = '3.9.0';
		$updates          = $this->getUpdateMockWithChangelog( [ '3.7.0', false, true ], $this->getChangelog( '3.9.0', $yesterday ) );
		$updates->run_updates();
		$this->runAllScheduledEvents( 'sensei_log_update' );

		$events = Sensei_Test_Events::get_logged_events( 'sensei_update' );

		$this->assertTrue( isset( $events[0]['url_args']['days_since_release'] ) );
		$this->assertEquals( '1', $events[0]['url_args']['days_since_release'] );
		$this->assertEquals( '3.7.0', $events[0]['url_args']['from_version'] );
		$this->assertEquals( Sensei()->version, $events[0]['url_args']['to_version'] );
	}

	/**
	 * Get mock to override changelog.
	 *
	 * @param array  $construtor_args Arguments for constructor.
	 * @param string $changelog      Changelog content.
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|Sensei_Updates
	 */
	private function getUpdateMockWithChangelog( $construtor_args, $changelog ) {
		$mock = $this->getMockBuilder( Sensei_Updates::class )
					->setMethods( [ 'get_changelog' ] )
					->setConstructorArgs( $construtor_args )
					->getMock();

		$mock->expects( $this->any() )
			->method( 'get_changelog' )
			->willReturn( $changelog );

		return $mock;
	}

	/**
	 * Get mocked changelog.
	 *
	 * @param string            $latest_version      Latest version string.
	 * @param DateTimeImmutable $latest_version_date Latest version date.
	 *
	 * @return string
	 */
	private function getChangelog( string $latest_version, DateTimeImmutable $latest_version_date ) {
		$date_str = $latest_version_date->format( 'Y-m-d' );

		return <<<END
{$date_str} - version {$latest_version}
* Fix: Fix something broken.
* Change: Changed something to be better.

2021.03.07 - version 3.8.1
* Fix: Fix something [#1](https://github.com/Automattic/sensei/pull/1)
* Fix: Fix something [#2](https://github.com/Automattic/sensei/pull/2)
* Fix: Fix something [#3](https://github.com/Automattic/sensei/pull/3)
* Fix: Fix something [#4](https://github.com/Automattic/sensei/pull/4) 👏 helper

2021.03.01 - version 3.8.0
* Fix: Fix something [#5](https://github.com/Automattic/sensei/pull/5)
* Fix: Fix something [#6](https://github.com/Automattic/sensei/pull/6)
END;
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

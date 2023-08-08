<?php
/**
 * This file contains the Sensei_Home_Task_Configure_Learning_Mode_Test class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Tests for Sensei_Home_Task_Configure_Learning_Mode class.
 *
 * @covers Sensei_Home_Task_Configure_Learning_Mode
 */
class Sensei_Home_Task_Configure_Learning_Mode_Test extends WP_UnitTestCase {

	/**
	 * The task under test.
	 *
	 * @var Sensei_Home_Task_Configure_Learning_Mode
	 */
	private $task;

	public function setUp(): void {
		parent::setUp();
		$this->task = new Sensei_Home_Task_Configure_Learning_Mode();
	}

	public function tearDown(): void {
		delete_option( Sensei_Settings::VISITED_SECTIONS_OPTION_KEY );
		parent::tearDown();
	}


	/**
	 * Test task is not completed by default.
	 */
	public function testTaskIsNotCompletedByDefault() {
		$this->assertFalse( $this->task->is_completed() );
	}

	/**
	 * Test adding the proper option marks task as complete.
	 */
	public function testTaskIsCompletedWhenAddingTheSettingsVisitedOption() {
		add_option( Sensei_Settings::VISITED_SECTIONS_OPTION_KEY, [ 'appearance-settings' ] );

		$this->assertTrue( $this->task->is_completed() );
	}
}

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
	 * Selections payload normalisation: missing types default to empty arrays.
	 */
	public function testSetSelections_MissingType_DefaultsToEmpty() {
		$job = Sensei_Export_Job::create( 'sel-1', 0 );

		$job->set_selections( array( 'course' => array( 1, 2, 3 ) ) );

		self::assertSame( array( 1, 2, 3 ), $job->get_selection( 'course' ), 'Course selection should match what was set.' );
		self::assertSame( array(), $job->get_selection( 'lesson' ), 'Missing types should default to an empty selection.' );
		self::assertSame( array(), $job->get_selection( 'question' ), 'Missing types should default to an empty selection.' );
	}

	/**
	 * Selections payload normalisation: dedupes and casts to int.
	 */
	public function testSetSelections_DuplicateAndStringIds_DedupedAndCastToInt() {
		$job = Sensei_Export_Job::create( 'sel-2', 0 );

		$job->set_selections(
			array(
				'course'   => array( '12', 12, '34' ),
				'lesson'   => array(),
				'question' => array( 5, '5', 7 ),
			)
		);

		self::assertSame( array( 12, 34 ), $job->get_selection( 'course' ), 'Course IDs should be deduped and cast to int.' );
		self::assertSame( array( 5, 7 ), $job->get_selection( 'question' ), 'Question IDs should be deduped and cast to int.' );
	}

	/**
	 * Default state: every type returns an empty selection (export all).
	 */
	public function testGetSelection_NoSelectionsSet_ReturnsEmptyForAllTypes() {
		$job = Sensei_Export_Job::create( 'sel-default', 0 );

		self::assertSame( array(), $job->get_selection( 'course' ), 'Default course selection should be empty.' );
		self::assertSame( array(), $job->get_selection( 'lesson' ), 'Default lesson selection should be empty.' );
		self::assertSame( array(), $job->get_selection( 'question' ), 'Default question selection should be empty.' );
	}
}

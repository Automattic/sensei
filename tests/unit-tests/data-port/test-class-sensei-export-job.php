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
	 * Types absent from the payload are skipped — they don't appear in the
	 * derived content-types list.
	 */
	public function testSetSelections_MissingType_NotIncludedInContentTypes() {
		$job = Sensei_Export_Job::create( 'sel-1', 0 );

		$job->set_selections( array( 'course' => array( 1, 2, 3 ) ) );

		self::assertSame( array( 1, 2, 3 ), $job->get_selection( 'course' ), 'Course selection should match what was set.' );
		self::assertSame( array( 'course' ), $job->get_content_types(), 'Only types present in the payload should be included.' );
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
		self::assertSame( array( 'course', 'lesson', 'question' ), $job->get_content_types(), 'All three types should be in content types.' );
	}

	/**
	 * Content types are derived from the keys of the persisted selections.
	 */
	public function testSetSelections_PopulatesContentTypesFromKeys() {
		$job = Sensei_Export_Job::create( 'sel-keys', 0 );

		$job->set_selections(
			array(
				'course'   => array(),
				'question' => array( 1 ),
			)
		);

		self::assertSame( array( 'course', 'question' ), $job->get_content_types(), 'Content types should reflect the keys of selections.' );
	}

	/**
	 * Default state: nothing has been set, so no types are included.
	 */
	public function testGetSelection_NoSelectionsSet_ReturnsEmptyForAllTypes() {
		$job = Sensei_Export_Job::create( 'sel-default', 0 );

		self::assertSame( array(), $job->get_selection( 'course' ), 'Default course selection should be empty.' );
		self::assertSame( array(), $job->get_selection( 'lesson' ), 'Default lesson selection should be empty.' );
		self::assertSame( array(), $job->get_selection( 'question' ), 'Default question selection should be empty.' );
		self::assertSame( array(), $job->get_content_types(), 'No content types should be set by default.' );
	}

	/**
	 * Legacy storage shape: pre-partial-export jobs persisted a flat list of
	 * type names under 'content_types'. The model should translate this to the
	 * current per-type-filter shape with empty filters.
	 */
	public function testGetSelectionsState_LegacyContentTypesShape_TranslatesToEmptyFilters() {
		$job = Sensei_Export_Job::create( 'sel-legacy', 0 );

		$job->set_state( 'content_types', array( 'course', 'lesson' ) );

		self::assertSame( array(), $job->get_selection( 'course' ), 'Legacy course should map to empty filter.' );
		self::assertSame( array(), $job->get_selection( 'lesson' ), 'Legacy lesson should map to empty filter.' );
		self::assertSame( array( 'course', 'lesson' ), $job->get_content_types(), 'Content types should be derived from the legacy list.' );
	}
}

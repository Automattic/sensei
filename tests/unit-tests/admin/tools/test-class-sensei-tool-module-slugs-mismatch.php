<?php
/**
 * This file contains the Sensei_Tool_Module_Slugs_Mismatch_Tests class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Tool_Module_Slugs_Mismatch class.
 *
 * @group tools
 */
class Sensei_Tool_Module_Slugs_Mismatch_Tests extends WP_UnitTestCase {
	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	private $factory;

	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests to make the slugs are fixed.
	 */
	public function testRunFixSlugs() {
		$args = [
			'slug' => 'wrong-slug',
		];
		wp_insert_term( 'My module', 'module', $args );

		self::assertNotFalse( get_term_by( 'slug', 'wrong-slug', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', 'my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		$this->assertFalse( get_term_by( 'slug', 'wrong-slug', 'module' ) );
		self::assertNotFalse( get_term_by( 'slug', 'my-module', 'module' ) );
	}

	/**
	 * Tests to make the slugs are fixed keeping the teacher ID prefix.
	 */
	public function testRunFixSlugsKeepingTeacherIDPrefix() {
		$args = [
			'slug' => '1-wrong-slug',
		];
		wp_insert_term( 'My module', 'module', $args );

		self::assertNotFalse( get_term_by( 'slug', '1-wrong-slug', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', '1-my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		$this->assertFalse( get_term_by( 'slug', '1-wrong-slug', 'module' ) );
		self::assertNotFalse( get_term_by( 'slug', '1-my-module', 'module' ) );
	}

	/**
	 * Tests that modules that have a name which start with a number are not modified by the tool.
	 */
	public function testRunModulesWithNumbersInNameNotModified() {
		wp_insert_term( '01 My module', 'module' );
		wp_insert_term( '100-200-My module', 'module' );

		self::assertNotFalse( get_term_by( 'slug', '01-my-module', 'module' ) );
		self::assertNotFalse( get_term_by( 'slug', '100-200-my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		self::assertNotFalse( get_term_by( 'slug', '01-my-module', 'module' ) );
		self::assertNotFalse( get_term_by( 'slug', '100-200-my-module', 'module' ) );
	}

	/**
	 * Tests that modules which start with a number and have a teacher assigned to them are fixed correctly.
	 */
	public function testRunModulesWithNumbersInNameFixedCorrectly() {
		// The wrong slug begins with a teacher id.
		$args = [
			'slug' => '1-wrong-slug',
		];
		wp_insert_term( '15 My module', 'module', $args );

		self::assertNotFalse( get_term_by( 'slug', '1-wrong-slug', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', '1-15-my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		self::assertNotFalse( get_term_by( 'slug', '1-15-my-module', 'module' ), 'Teacher id was not prepended to the module name.' );
		$this->assertFalse( get_term_by( 'slug', '1-wrong-slug', 'module' ) );

		// The wrong slug begins with the same numbers as the module name.
		$args = [
			'slug' => '15-wrong-slug',
		];
		wp_insert_term( '15 My module', 'module', $args );

		self::assertNotFalse( get_term_by( 'slug', '15-wrong-slug', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', '15-my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		self::assertNotFalse( get_term_by( 'slug', '15-my-module', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', '15-wrong-slug', 'module' ) );

		// Test that modules with a name which begins with the teacher's id, are not modified.
		$args = [
			'slug' => '15-15-my-module',
		];
		wp_insert_term( '15 My module', 'module', $args );

		self::assertNotFalse( get_term_by( 'slug', '15-15-my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		self::assertNotFalse( get_term_by( 'slug', '15-15-my-module', 'module' ), 'Module with a name which begins with the teacher id has been modified.' );
	}
}

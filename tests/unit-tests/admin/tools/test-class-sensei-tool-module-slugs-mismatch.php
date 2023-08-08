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
	protected $factory;

	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests to make the slugs are fixed.
	 */
	public function testRunFixSlugs() {
		$args        = [
			'slug' => 'wrong-slug',
		];
		$term_result = wp_insert_term( 'My module', 'module', $args );

		$this->assertNotFalse( get_term_by( 'slug', 'wrong-slug', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', 'my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		$this->assertFalse( get_term_by( 'slug', 'wrong-slug', 'module' ) );
		$this->assertNotFalse( get_term_by( 'slug', 'my-module', 'module' ) );
	}

	/**
	 * Tests to make the slugs are fixed keeping the teacher ID prefix.
	 */
	public function testRunFixSlugsKeepingTeacherIDPrefix() {
		$args        = [
			'slug' => '1-wrong-slug',
		];
		$term_result = wp_insert_term( 'My module', 'module', $args );

		$this->assertNotFalse( get_term_by( 'slug', '1-wrong-slug', 'module' ) );
		$this->assertFalse( get_term_by( 'slug', '1-my-module', 'module' ) );

		$tool = new Sensei_Tool_Module_Slugs_Mismatch();
		$tool->process();

		$this->assertFalse( get_term_by( 'slug', '1-wrong-slug', 'module' ) );
		$this->assertNotFalse( get_term_by( 'slug', '1-my-module', 'module' ) );
	}
}

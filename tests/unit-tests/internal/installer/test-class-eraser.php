<?php
namespace SenseiTest\Internal\Installer;

use Sensei\Internal\Installer\Eraser;

/**
 * Class Eraser_Test
 *
 * @covers \Sensei\Internal\Installer\Eraser
 */
class Eraser_Test extends \WP_UnitTestCase {
	public function testErase_Always_DeletesTables() {
		/* Arrange. */
		$eraser = new Eraser();

		/* Act. */
		$actual = $eraser->drop_tables();

		/* Assert. */
		global $wpdb;
		$expected = array(
			"{$wpdb->prefix}sensei_lms_progress",
			"{$wpdb->prefix}sensei_lms_quiz_submissions",
			"{$wpdb->prefix}sensei_lms_quiz_answers",
			"{$wpdb->prefix}sensei_lms_quiz_grades",
		);
		$this->assertEquals( $expected, $actual );
	}

	public function testGetTables_Always_ReturnsMatchingTables(): void {
		/* Arrange. */
		$eraser = new Eraser();

		/* Act. */
		$actual = $eraser->get_tables();

		/* Assert. */
		global $wpdb;
		$expected = array(
			"{$wpdb->prefix}sensei_lms_progress",
			"{$wpdb->prefix}sensei_lms_quiz_submissions",
			"{$wpdb->prefix}sensei_lms_quiz_answers",
			"{$wpdb->prefix}sensei_lms_quiz_grades",
		);
		$this->assertEquals( $expected, $actual );
	}
}

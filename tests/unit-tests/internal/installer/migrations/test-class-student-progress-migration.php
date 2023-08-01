<?php

namespace SenseiTest\Internal\Installer\Migrations;

use Sensei\Internal\Installer\Migrations\Student_Progress_Migration;
use Sensei_Factory;

/**
 * Class Student_Progress_Migration_Test
 *
 * @covers \Sensei\Internal\Installer\Migrations\Student_Progress_Migration
 */
class Student_Progress_Migration_Test extends \WP_UnitTestCase {

	/**
	 * @var \Sensei\Internal\Installer\Migrations\Student_Progress_Migration
	 */
	private $migration;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->migration = new Student_Progress_Migration();
		$this->factory   = new Sensei_Factory();

		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'sensei_lms_progress' );
	}

	public function testTargetVersion_Always_ReturnsMathcingValue(): void {
		/* Act. */
		$actual = $this->migration->target_version();

		/* Assert. */
		$this->assertEquals( '1.0.0', $actual );
	}

	public function testGetErrors_MigrationDidntRun_ReturnsEmptyArray(): void {
		/* Act. */
		$actual = $this->migration->get_errors();

		/* Assert. */
		$this->assertEmpty( $actual );
	}

	public function testRun_NoCommentsExist_ReturnsZero(): void {
		/* Arrange. */
		$expected = 0;

		/* Act. */
		$actual = $this->migration->run( $dry_run = false );

		/* Assert. */
		$this->assertEquals( $expected, $actual );
	}

	public function testRun_CommentsExist_ReturnsMatchingNumberOfInserts(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$lesson_id = $this->factory->lesson->create( array( 'post_title' => 'Lesson 1', 'post_parent' => $course_id ) );

		\Sensei_Utils::start_user_on_course( 1, $course_id );
		\Sensei_Utils::user_start_lesson( 1, $lesson_id, true );

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		/* Act. */
		$this->migration->run( $dry_run = false );

		/* Assert. */
		global $wpdb;
		$actual_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_progress" );
		$this->assertEquals( 2, $actual_rows );
	}
}

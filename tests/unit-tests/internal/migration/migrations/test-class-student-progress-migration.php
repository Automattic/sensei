<?php

namespace SenseiTest\Internal\Migration\Migrations;

use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;
use Sensei_Factory;
use Sensei_Utils;

/**
 * Class Student_Progress_Migration_Test
 *
 * @covers \Sensei\Internal\Migration\Migrations\Student_Progress_Migration
 */
class Student_Progress_Migration_Test extends \WP_UnitTestCase {

	/**
	 * Migration instance.
	 *
	 * @var \Sensei\Internal\Migration\Migrations\Student_Progress_Migration
	 */
	private $migration;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->migration = new Student_Progress_Migration();
		$this->factory   = new Sensei_Factory();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'sensei_lms_progress' );
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
		$actual = $this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$this->assertEquals( $expected, $actual );
	}

	public function testRun_CommentsExist_ReturnsMatchingNumberOfInserts(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_title'  => 'Lesson 1',
				'post_parent' => $course_id,
			)
		);

		Sensei_Utils::start_user_on_course( 1, $course_id );
		Sensei_Utils::user_start_lesson( 1, $lesson_id, true );

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$actual_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sensei_lms_progress" );
		$this->assertEquals( 2, $actual_rows );
	}


	public function testRun_CommentsExist_CreatesProgressMatchingEntriesInCustomTables(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create( array( 'post_title' => 'Course 1' ) );
		$quiz_id   = $this->factory->quiz->create(
			array(
				'post_title' => 'Quiz 1',
			)
		);
		$lesson_id = $this->factory->lesson->create(
			array(
				'post_title'  => 'Lesson 1',
				'post_parent' => $course_id,
				'meta_input'  => array(
					'_lesson_quiz' => $quiz_id,
				),
			)
		);
		$user_1    = $this->factory->user->create();
		$user_2    = $this->factory->user->create();

		update_post_meta( $quiz_id, '_quiz_lesson', $lesson_id );

		Sensei_Utils::start_user_on_course( $user_1, $course_id );
		Sensei_Utils::user_start_lesson( $user_1, $lesson_id, true );
		Sensei_Utils::user_passed_quiz( $quiz_id, $user_1 );

		Sensei_Utils::start_user_on_course( $user_2, $course_id );
		Sensei_Utils::user_start_lesson( $user_2, $lesson_id, true );
		Sensei_Utils::user_passed_quiz( $quiz_id, $user_2 );

		update_option( 'sensei_migrated_progress_last_comment_id', 0 );

		/* Act. */
		$this->migration->run( $dry_run = false ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		/* Assert. */
		$actual_rows = $this->get_table_based_progress();
		$expected    = array(
			array(
				'user_id' => $user_1,
				'post_id' => $course_id,
				'status'  => 'in-progress',
				'type'    => 'course',
			),
			array(
				'user_id' => $user_1,
				'post_id' => $lesson_id,
				'status'  => 'complete',
				'type'    => 'lesson',
			),
			array(
				'user_id' => $user_1,
				'post_id' => $quiz_id,
				'status'  => 'passed',
				'type'    => 'quiz',
			),
			array(
				'user_id' => $user_2,
				'post_id' => $course_id,
				'status'  => 'in-progress',
				'type'    => 'course',
			),
			array(
				'user_id' => $user_2,
				'post_id' => $lesson_id,
				'status'  => 'complete',
				'type'    => 'lesson',
			),
			array(
				'user_id' => $user_2,
				'post_id' => $quiz_id,
				'status'  => 'passed',
				'type'    => 'quiz',
			),
		);
		$this->assertSame( $expected, $actual_rows );
	}

	private function get_table_based_progress(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sensei_lms_progress ORDER BY user_id" );

		$result = array();
		foreach ( $rows as $row ) {
			$result[] = array(
				'user_id' => (int) $row->user_id,
				'post_id' => (int) $row->post_id,
				'status'  => $row->status,
				'type'    => $row->type,
			);
		}

		return $result;
	}
}

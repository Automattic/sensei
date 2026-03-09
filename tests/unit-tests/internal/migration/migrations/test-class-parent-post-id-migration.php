<?php

namespace SenseiTest\Internal\Migration\Migrations;

use Sensei\Internal\Migration\Migrations\Parent_Post_Id_Migration;
use Sensei_Factory;

/**
 * Class Parent_Post_Id_Migration_Test
 *
 * @covers \Sensei\Internal\Migration\Migrations\Parent_Post_Id_Migration
 */
class Parent_Post_Id_Migration_Test extends \WP_UnitTestCase {

	/**
	 * Migration instance.
	 *
	 * @var Parent_Post_Id_Migration
	 */
	private $migration;

	protected $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->migration = new Parent_Post_Id_Migration();
		$this->factory   = new Sensei_Factory();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'sensei_lms_progress' );

		delete_option( Parent_Post_Id_Migration::LAST_ID_OPTION_NAME );
	}

	public function testRun_NoRows_ReturnsZero(): void {
		$actual = $this->migration->run( false );

		$this->assertSame( 0, $actual );
	}

	public function testRun_LessonProgress_BackfillsCourseId(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course' => $course_id,
				),
			)
		);
		$user_id   = $this->factory->user->create();

		$this->insert_progress_row( $lesson_id, $user_id, 'lesson' );

		/* Act. */
		$this->migration->run( false );

		/* Assert. */
		$parent = $this->get_parent_post_id( $lesson_id, $user_id, 'lesson' );
		$this->assertEquals( $course_id, (int) $parent );
	}

	public function testRun_QuizProgress_BackfillsLessonId(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		$quiz_id   = $this->factory->quiz->create(
			array(
				'meta_input' => array(
					'_quiz_lesson' => $lesson_id,
				),
			)
		);
		$user_id   = $this->factory->user->create();

		// First run processes lessons (none), second processes quizzes.
		$this->insert_progress_row( $quiz_id, $user_id, 'quiz' );

		/* Act. */
		// First call completes lesson phase (0 rows), then processes quiz.
		$this->migration->run( false );

		/* Assert. */
		$parent = $this->get_parent_post_id( $quiz_id, $user_id, 'quiz' );
		$this->assertEquals( $lesson_id, (int) $parent );
	}

	public function testRun_CourseProgress_RemainsNull(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$user_id   = $this->factory->user->create();

		$this->insert_progress_row( $course_id, $user_id, 'course' );

		/* Act. */
		$this->migration->run( false );
		$this->migration->run( false );

		/* Assert. */
		$parent = $this->get_parent_post_id( $course_id, $user_id, 'course' );
		$this->assertNull( $parent );
	}

	public function testRun_AlreadyPopulated_DoesNotModify(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course' => $course_id,
				),
			)
		);
		$user_id   = $this->factory->user->create();

		// Insert with parent_post_id already set to a different value.
		$this->insert_progress_row( $lesson_id, $user_id, 'lesson', 99999 );

		/* Act. */
		$this->migration->run( false );

		/* Assert. */
		$parent = $this->get_parent_post_id( $lesson_id, $user_id, 'lesson' );
		$this->assertEquals( 99999, (int) $parent, 'Already-populated rows should not be modified.' );
	}

	public function testRun_OrphanedLesson_RemainsNull(): void {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create(); // No _lesson_course meta.
		$user_id   = $this->factory->user->create();

		$this->insert_progress_row( $lesson_id, $user_id, 'lesson' );

		/* Act. */
		$this->migration->run( false );

		/* Assert. */
		$parent = $this->get_parent_post_id( $lesson_id, $user_id, 'lesson' );
		$this->assertNull( $parent, 'Orphaned lesson progress should remain NULL.' );
	}

	public function testRun_ReturnsZeroWhenComplete(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$lesson_id = $this->factory->lesson->create(
			array(
				'meta_input' => array(
					'_lesson_course' => $course_id,
				),
			)
		);
		$user_id   = $this->factory->user->create();

		$this->insert_progress_row( $lesson_id, $user_id, 'lesson' );

		/* Act. */
		$this->migration->run( false ); // Processes lesson rows.
		$result = $this->migration->run( false ); // Should return 0 (complete).

		/* Assert. */
		$this->assertSame( 0, $result );
	}

	public function testRun_BatchesCorrectly(): void {
		/* Arrange. */
		$course_id = $this->factory->course->create();
		$migration = new Parent_Post_Id_Migration( 2 ); // Batch size of 2.

		for ( $i = 0; $i < 5; $i++ ) {
			$lesson_id = $this->factory->lesson->create(
				array(
					'meta_input' => array(
						'_lesson_course' => $course_id,
					),
				)
			);
			$user_id = $this->factory->user->create();
			$this->insert_progress_row( $lesson_id, $user_id, 'lesson' );
		}

		/* Act. */
		$first_batch  = $migration->run( false );
		$second_batch = $migration->run( false );
		$third_batch  = $migration->run( false );
		$final        = $migration->run( false );

		/* Assert. */
		$this->assertSame( 2, $first_batch );
		$this->assertSame( 2, $second_batch );
		$this->assertSame( 1, $third_batch );
		$this->assertSame( 0, $final );
	}

	/**
	 * Insert a progress row directly into the database.
	 *
	 * @param int         $post_id The post ID.
	 * @param int         $user_id The user ID.
	 * @param string      $type The progress type.
	 * @param int|null    $parent_post_id The parent post ID.
	 */
	private function insert_progress_row( int $post_id, int $user_id, string $type, ?int $parent_post_id = null ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'sensei_lms_progress';

		$data   = array(
			'post_id'        => $post_id,
			'user_id'        => $user_id,
			'parent_post_id' => $parent_post_id,
			'type'           => $type,
			'status'         => 'in-progress',
			'started_at'     => current_time( 'mysql', true ),
			'created_at'     => current_time( 'mysql', true ),
			'updated_at'     => current_time( 'mysql', true ),
		);
		$format = array(
			'%d',
			'%d',
			$parent_post_id ? '%d' : null,
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $data, $format );
	}

	/**
	 * Get the parent_post_id for a progress row.
	 *
	 * @param int    $post_id The post ID.
	 * @param int    $user_id The user ID.
	 * @param string $type The progress type.
	 * @return string|null The parent_post_id value.
	 */
	private function get_parent_post_id( int $post_id, int $user_id, string $type ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sensei_lms_progress';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT parent_post_id FROM {$table} WHERE post_id = %d AND user_id = %d AND type = %s",
				$post_id,
				$user_id,
				$type
			)
		);
	}
}

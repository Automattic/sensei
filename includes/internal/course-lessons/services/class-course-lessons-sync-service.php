<?php
/**
 * File containing the Course_Lessons_Sync_Service class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Services;

use Sensei\Internal\Course_Lessons\Models\Course_Lesson;
use Sensei\Internal\Course_Lessons\Repositories\Course_Lessons_Repository_Interface;
use Sensei\Internal\Course_Lessons\Repositories\Tables_Based_Course_Lessons_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Lessons_Sync_Service.
 *
 * Mirrors the structure metas into the course lessons table: on every change, the lesson's row is
 * written again from the metas. It listens to the hooks rather than to Sensei's own writers because
 * REST, WPML and other plugins write the metas directly.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Course_Lessons_Sync_Service {
	/**
	 * Repository the structure is read from.
	 *
	 * @var Course_Lessons_Repository_Interface
	 */
	private $metas;

	/**
	 * Repository the table is written with.
	 *
	 * @var Tables_Based_Course_Lessons_Repository
	 */
	private $table;

	/**
	 * Course_Lessons_Sync_Service constructor.
	 *
	 * @internal
	 *
	 * @param Course_Lessons_Repository_Interface    $metas Repository the structure is read from.
	 * @param Tables_Based_Course_Lessons_Repository $table Repository the table is written with.
	 */
	public function __construct( Course_Lessons_Repository_Interface $metas, Tables_Based_Course_Lessons_Repository $table ) {
		$this->metas = $metas;
		$this->table = $table;
	}

	/**
	 * Add the hooks that keep the table in sync.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 */
	public function init(): void {
		add_action( 'added_post_meta', array( $this, 'handle_meta_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'handle_meta_change' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'handle_meta_change' ), 10, 3 );
	}

	/**
	 * Write the row of a lesson again when one of its structure metas changes.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int|int[] $meta_ids  Meta ID, or IDs when deleting.
	 * @param int       $object_id Post ID.
	 * @param string    $meta_key  Meta key.
	 */
	public function handle_meta_change( $meta_ids, $object_id, $meta_key ): void {
		if ( '_lesson_course' !== $meta_key || 'lesson' !== get_post_type( (int) $object_id ) ) {
			return;
		}

		$this->sync_lesson( (int) $object_id );
	}

	/**
	 * Write the row of a lesson from its metas, or delete it when the lesson has no course.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int $lesson_id Lesson ID.
	 */
	public function sync_lesson( int $lesson_id ): void {
		$course_lesson = $this->get_course_lesson( $lesson_id );
		if ( null === $course_lesson ) {
			$this->table->delete_for_lesson( $lesson_id );
			return;
		}

		// Rows are keyed by (course, lesson): when the lesson changed course, the row of the old course
		// is still there and has to go. Saving first keeps the current row's id.
		$this->table->save( $course_lesson );
		$this->table->delete_for_lesson( $lesson_id, $course_lesson->get_course_id() );
	}

	/**
	 * Compute the row of a lesson from its metas.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return Course_Lesson|null Null when the lesson has no course.
	 */
	private function get_course_lesson( int $lesson_id ): ?Course_Lesson {
		$course_id = $this->metas->get_course_id( $lesson_id );
		if ( null === $course_id ) {
			return null;
		}

		return new Course_Lesson( $course_id, $lesson_id );
	}
}

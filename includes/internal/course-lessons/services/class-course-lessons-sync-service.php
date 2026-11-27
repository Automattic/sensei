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
	private $meta_repo;

	/**
	 * Repository the table is written with.
	 *
	 * @var Tables_Based_Course_Lessons_Repository
	 */
	private $table_repo;

	/**
	 * Course_Lessons_Sync_Service constructor.
	 *
	 * @internal
	 *
	 * @param Course_Lessons_Repository_Interface    $meta_repo  Repository the structure is read from.
	 * @param Tables_Based_Course_Lessons_Repository $table_repo Repository the table is written with.
	 */
	public function __construct( Course_Lessons_Repository_Interface $meta_repo, Tables_Based_Course_Lessons_Repository $table_repo ) {
		$this->meta_repo  = $meta_repo;
		$this->table_repo = $table_repo;
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
		add_action( 'set_object_terms', array( $this, 'handle_set_object_terms' ), 10, 6 );
		add_action( 'deleted_term_relationships', array( $this, 'handle_deleted_term_relationships' ), 10, 3 );
	}

	/**
	 * Write the row of a lesson again when its course or one of its order metas changes.
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
		if ( ! $this->is_structure_meta_key( (string) $meta_key ) || 'lesson' !== get_post_type( (int) $object_id ) ) {
			return;
		}

		$this->sync_lesson( (int) $object_id );
	}

	/**
	 * Write the rows again when the module terms of a lesson or a course are set.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int    $object_id  Post ID.
	 * @param array  $terms      Terms set.
	 * @param array  $tt_ids     Term taxonomy IDs set.
	 * @param string $taxonomy   Taxonomy.
	 * @param bool   $append     Whether the terms were appended.
	 * @param array  $old_tt_ids Term taxonomy IDs the post had before.
	 */
	public function handle_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append = false, $old_tt_ids = array() ): void {
		// The action fires even when the terms did not change, on every outline save for example.
		if ( $this->same_terms( (array) $tt_ids, (array) $old_tt_ids ) ) {
			return;
		}

		$this->handle_module_change( (int) $object_id, (string) $taxonomy );
	}

	/**
	 * Write the rows again when module terms are removed from a lesson or a course.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int    $object_id Post ID.
	 * @param array  $tt_ids    Term taxonomy IDs removed.
	 * @param string $taxonomy  Taxonomy.
	 */
	public function handle_deleted_term_relationships( $object_id, $tt_ids, $taxonomy ): void {
		$this->handle_module_change( (int) $object_id, (string) $taxonomy );
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
			$this->table_repo->delete_for_lesson( $lesson_id );
			return;
		}

		// Rows are keyed by (course, lesson): when the lesson changed course, the row of the old course
		// is still there and has to go. Saving first keeps the current row's id.
		$this->table_repo->save( $course_lesson );
		$this->table_repo->delete_for_lesson( $lesson_id, $course_lesson->get_course_id() );
	}

	/**
	 * Compute the row of a lesson from its metas.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @return Course_Lesson|null Null when the lesson has no course.
	 */
	private function get_course_lesson( int $lesson_id ): ?Course_Lesson {
		$course_id = $this->meta_repo->get_course_id( $lesson_id );
		if ( null === $course_id ) {
			return null;
		}

		// Only a module of the lesson's course counts, as on the course page and in the navigation.
		$module = $this->meta_repo->get_lesson_module( $lesson_id );
		if ( $module && ! has_term( $module->term_id, 'module', $course_id ) ) {
			$module = null;
		}

		// The order is the position in the lesson's container: its module, or the course root without one.
		$module_term_id = $module ? $module->term_id : 0;
		$lesson_order   = $module
			? $this->meta_repo->get_lesson_module_order( $lesson_id, $module_term_id )
			: $this->meta_repo->get_lesson_order( $lesson_id, $course_id );

		return new Course_Lesson( $course_id, $lesson_id, $module_term_id, $lesson_order );
	}

	/**
	 * Write the rows again when the modules of a lesson or a course changed.
	 *
	 * @param int    $object_id Post ID.
	 * @param string $taxonomy  Taxonomy of the terms that changed.
	 */
	private function handle_module_change( int $object_id, string $taxonomy ): void {
		if ( 'module' !== $taxonomy ) {
			return;
		}

		$post_type = get_post_type( $object_id );
		if ( 'lesson' === $post_type ) {
			$this->sync_lesson( $object_id );
			return;
		}

		// The modules of a course decide which of its lessons count as being in a module.
		if ( 'course' === $post_type ) {
			foreach ( $this->meta_repo->get_lesson_ids( $object_id, array_keys( get_post_stati() ) ) as $lesson_id ) {
				$this->sync_lesson( $lesson_id );
			}
		}
	}

	/**
	 * Whether two lists of term taxonomy IDs hold the same terms.
	 *
	 * @param array $tt_ids     Term taxonomy IDs.
	 * @param array $old_tt_ids Term taxonomy IDs to compare with.
	 * @return bool
	 */
	private function same_terms( array $tt_ids, array $old_tt_ids ): bool {
		$tt_ids     = array_map( 'intval', $tt_ids );
		$old_tt_ids = array_map( 'intval', $old_tt_ids );
		sort( $tt_ids );
		sort( $old_tt_ids );

		return $tt_ids === $old_tt_ids;
	}

	/**
	 * Whether a meta key holds part of a lesson's place in its course.
	 *
	 * @param string $meta_key Meta key.
	 * @return bool
	 */
	private function is_structure_meta_key( string $meta_key ): bool {
		return '_lesson_course' === $meta_key || 1 === preg_match( '/^_order_(module_)?\d+$/', $meta_key );
	}
}

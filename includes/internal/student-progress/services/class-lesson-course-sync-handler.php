<?php
/**
 * File containing the Lesson_Course_Sync_Handler class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

use Sensei\Internal\Services\Utils;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Course_Sync_Handler.
 *
 * Keeps the parent_post_id column on lesson progress rows in the
 * wp_sensei_lms_progress table in sync with the lesson's _lesson_course
 * postmeta. Without this, reassigning a lesson to a different course leaves
 * the table-based storage pointing at the old course, causing HPPS code
 * paths that group or filter by parent_post_id to return stale results.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Lesson_Course_Sync_Handler {
	/**
	 * The lesson course meta key.
	 */
	private const LESSON_COURSE_META_KEY = '_lesson_course';

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Adds hooks.
	 */
	public function init(): void {
		add_action( 'added_post_meta', [ $this, 'handle_meta_change' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'handle_meta_change' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'handle_meta_delete' ], 10, 3 );
	}

	/**
	 * Handle a postmeta add/update event.
	 *
	 * @param int    $meta_id    Meta ID (unused).
	 * @param int    $object_id  Post ID the meta belongs to.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New meta value.
	 */
	public function handle_meta_change( $meta_id, int $object_id, $meta_key, $meta_value ): void {
		if ( ! $this->is_relevant_meta( $meta_key, $object_id ) ) {
			return;
		}

		$course_id = is_scalar( $meta_value ) ? (int) $meta_value : 0;
		$this->update_parent_post_id( $object_id, $course_id > 0 ? $course_id : null );
	}

	/**
	 * Handle a postmeta delete event.
	 *
	 * @param array  $meta_ids  Meta IDs (unused).
	 * @param int    $object_id Post ID the meta belongs to.
	 * @param string $meta_key  Meta key.
	 */
	public function handle_meta_delete( $meta_ids, int $object_id, $meta_key ): void {
		if ( ! $this->is_relevant_meta( $meta_key, $object_id ) ) {
			return;
		}

		$this->update_parent_post_id( $object_id, null );
	}

	/**
	 * Whether the meta event applies to a lesson's _lesson_course postmeta.
	 *
	 * @param string $meta_key  Meta key.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	private function is_relevant_meta( $meta_key, int $object_id ): bool {
		if ( self::LESSON_COURSE_META_KEY !== $meta_key ) {
			return false;
		}

		return 'lesson' === get_post_type( $object_id );
	}

	/**
	 * Update parent_post_id on all lesson progress rows for the given lesson.
	 *
	 * @param int      $lesson_id     Lesson ID.
	 * @param int|null $parent_post_id New course ID, or null to clear.
	 */
	private function update_parent_post_id( int $lesson_id, ?int $parent_post_id ): void {
		$result = $this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			array( 'parent_post_id' => $parent_post_id ),
			array(
				'post_id' => $lesson_id,
				'type'    => 'lesson',
			),
			array( '%d' ),
			array( '%d', '%s' )
		);

		if ( false === $result ) {
			Utils::log_query_error(
				$this->wpdb,
				sprintf( 'Lesson_Course_Sync_Handler::update_parent_post_id lesson_id=%d parent_post_id=%s', $lesson_id, null === $parent_post_id ? 'NULL' : (string) $parent_post_id )
			);
		}
	}
}

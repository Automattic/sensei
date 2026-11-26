<?php
/**
 * File containing the Tables_Based_Course_Lessons_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use Sensei\Internal\Course_Lessons\Models\Course_Lesson;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Course_Lessons_Repository.
 *
 * Writes the course lessons table.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Tables_Based_Course_Lessons_Repository {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Whether the table exists, keyed by table name because the prefix changes with the blog.
	 *
	 * @var array<string, bool>
	 */
	private $table_installed = array();

	/**
	 * Tables_Based_Course_Lessons_Repository constructor.
	 *
	 * @internal
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Save the row of a lesson in a course. An existing row is updated in place, so its id never changes.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param Course_Lesson $course_lesson The row to save.
	 */
	public function save( Course_Lesson $course_lesson ): void {
		if ( ! $this->is_table_installed() ) {
			return;
		}

		$now = ( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d H:i:s' );
		$row = array(
			'course_id'              => $course_lesson->get_course_id(),
			'lesson_id'              => $course_lesson->get_lesson_id(),
			'module_term_id'         => $course_lesson->get_module_term_id(),
			'lesson_order'           => $course_lesson->get_lesson_order(),
			'prerequisite_lesson_id' => $course_lesson->get_prerequisite_lesson_id(),
			'created_at'             => $now,
			'updated_at'             => $now,
		);

		// prepare() has no placeholder for NULL, so a null column gets the literal and no value.
		$placeholders = array();
		$values       = array();
		foreach ( $row as $value ) {
			if ( null === $value ) {
				$placeholders[] = 'NULL';
				continue;
			}
			$placeholders[] = is_int( $value ) ? '%d' : '%s';
			$values[]       = $value;
		}

		$columns      = implode( ', ', array_keys( $row ) );
		$placeholders = implode( ', ', $placeholders );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name, column names and placeholders are built above, not from input.
		$query = $this->wpdb->prepare(
			"INSERT INTO {$this->get_table_name()} ( {$columns} ) VALUES ( {$placeholders} )
			ON DUPLICATE KEY UPDATE module_term_id = VALUES( module_term_id ), lesson_order = VALUES( lesson_order ), prerequisite_lesson_id = VALUES( prerequisite_lesson_id ), updated_at = VALUES( updated_at )",
			$values
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->wpdb->query( $query );
	}

	/**
	 * Delete the rows of a lesson.
	 *
	 * @internal
	 *
	 * @since $$next-version$$
	 *
	 * @param int      $lesson_id        Lesson ID.
	 * @param int|null $except_course_id Course whose row is kept.
	 */
	public function delete_for_lesson( int $lesson_id, ?int $except_course_id = null ): void {
		if ( ! $this->is_table_installed() ) {
			return;
		}

		$values     = array( $lesson_id );
		$except_sql = '';
		if ( null !== $except_course_id ) {
			$except_sql = ' AND course_id != %d';
			$values[]   = $except_course_id;
		}

		$query = $this->wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and the optional course condition are safe.
			"DELETE FROM {$this->get_table_name()} WHERE lesson_id = %d{$except_sql}",
			$values
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->wpdb->query( $query );
	}

	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		return $this->wpdb->prefix . 'sensei_lms_course_lessons';
	}

	/**
	 * Whether the table exists. The installer creates it on a version bump only, so writes can run before.
	 *
	 * @return bool
	 */
	private function is_table_installed(): bool {
		$table = $this->get_table_name();
		if ( ! isset( $this->table_installed[ $table ] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$this->table_installed[ $table ] = $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		}

		return $this->table_installed[ $table ];
	}
}

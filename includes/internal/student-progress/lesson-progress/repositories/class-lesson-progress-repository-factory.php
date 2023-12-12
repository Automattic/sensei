<?php
/**
 * File containing the Lesson_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Progress_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Lesson_Progress_Repository_Factory {
	/**
	 * Is tables based progress feature flag enabled.
	 *
	 * @var bool
	 */
	private $tables_enabled;

	/**
	 * Read from tables flag.
	 *
	 * @var bool
	 */
	private $read_tables;

	/**
	 * Lesson_Progress_Repository_Factory constructor.
	 *
	 * @param bool $tables_enabled Is tables based progress feature flag enabled.
	 * @param bool $read_tables Read from tables flag.
	 */
	public function __construct( bool $tables_enabled, bool $read_tables ) {
		$this->tables_enabled = $tables_enabled;
		$this->read_tables    = $read_tables;
	}

	/**
	 * Creates a new lesson progress repository.
	 *
	 * @internal
	 *
	 * @return Lesson_Progress_Repository_Interface The repository.
	 */
	public function create(): Lesson_Progress_Repository_Interface {
		global $wpdb;

		if ( ! $this->tables_enabled ) {
			return new Comments_Based_Lesson_Progress_Repository();
		}

		if ( ! $this->read_tables ) {
			return new Comment_Reading_Aggregate_Lesson_Progress_Repository(
				new Comments_Based_Lesson_Progress_Repository(),
				new Tables_Based_Lesson_Progress_Repository( $wpdb )
			);
		}

		return new Table_Reading_Aggregate_Lesson_Progress_Repository(
			new Comments_Based_Lesson_Progress_Repository(),
			new Tables_Based_Lesson_Progress_Repository( $wpdb )
		);
	}

	/**
	 * Creates a comments-based lesson progress repository.
	 *
	 * @internal
	 *
	 * @return Comments_Based_Lesson_Progress_Repository The repository.
	 */
	public function create_comments_based_repository(): Comments_Based_Lesson_Progress_Repository {
		return new Comments_Based_Lesson_Progress_Repository();
	}
}

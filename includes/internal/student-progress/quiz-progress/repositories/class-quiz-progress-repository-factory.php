<?php
/**
 * File containing the Quiz_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;

/**
 * Class Quiz_Progress_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Quiz_Progress_Repository_Factory {

	/**
	 * The flag if the tables based implementation is available for use.
	 *
	 * @var bool
	 */
	private $tables_enabled;

	/**
	 * The flag if we read progress from tables.
	 *
	 * @var bool
	 */
	private $read_tables;

	/**
	 * Quiz_Progress_Repository_Factory constructor.
	 *
	 * @param bool $tables_enabled Is tables based progress enabled.
	 * @param bool $read_tables Is reading from tables enabled.
	 */
	public function __construct( bool $tables_enabled, bool $read_tables ) {
		$this->tables_enabled = $tables_enabled;
		$this->read_tables    = $read_tables;
	}

	/**
	 * Create a new quiz progress repository.
	 *
	 * @internal
	 *
	 * @return Quiz_Progress_Repository_Interface
	 */
	public function create(): Quiz_Progress_Repository_Interface {
		global $wpdb;

		if ( ! $this->tables_enabled ) {
			return new Comments_Based_Quiz_Progress_Repository();
		}

		if ( ! $this->read_tables ) {
			return new Comment_Reading_Aggregate_Quiz_Progress_Repository(
				new Comments_Based_Quiz_Progress_Repository(),
				new Tables_Based_Quiz_Progress_Repository( $wpdb )
			);
		}

		return new Table_Reading_Aggregate_Quiz_Progress_Repository(
			new Comments_Based_Quiz_Progress_Repository(),
			new Tables_Based_Quiz_Progress_Repository( $wpdb ),
			new Comments_Based_Lesson_Progress_Repository()
		);
	}

	/**
	 * Create a new tables based quiz progress repository.
	 *
	 * @internal
	 *
	 * @since 4.17.0
	 *
	 * @return Tables_Based_Quiz_Progress_Repository
	 */
	public function create_tables_based_repository(): Tables_Based_Quiz_Progress_Repository {
		global $wpdb;

		return new Tables_Based_Quiz_Progress_Repository( $wpdb );
	}
}

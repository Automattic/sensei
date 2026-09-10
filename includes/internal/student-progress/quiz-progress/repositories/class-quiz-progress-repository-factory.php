<?php
/**
 * File containing the Quiz_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;
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
	 * Resolved progress storage configuration.
	 *
	 * @var Progress_Storage_Configuration
	 */
	private $storage_configuration;

	/**
	 * Quiz_Progress_Repository_Factory constructor.
	 *
	 * @param Progress_Storage_Configuration $storage_configuration Resolved progress storage configuration.
	 */
	public function __construct( Progress_Storage_Configuration $storage_configuration ) {
		$this->storage_configuration = $storage_configuration;
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

		if ( ! $this->storage_configuration->is_dual_write_enabled() ) {
			return new Comments_Based_Quiz_Progress_Repository();
		}

		if ( ! $this->storage_configuration->is_reading_from_tables() ) {
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

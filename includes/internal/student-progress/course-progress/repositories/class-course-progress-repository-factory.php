<?php
/**
 * File containing the Course_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Course_Progress_Repository_Factory {

	/**
	 * Resolved progress storage configuration.
	 *
	 * @var Progress_Storage_Configuration
	 */
	private $storage_configuration;

	/**
	 * Course_Progress_Repository_Factory constructor.
	 *
	 * @param Progress_Storage_Configuration $storage_configuration Resolved progress storage configuration.
	 */
	public function __construct( Progress_Storage_Configuration $storage_configuration ) {
		$this->storage_configuration = $storage_configuration;
	}

	/**
	 * Create a repository for a course progress.
	 *
	 * @internal
	 *
	 * @return Course_Progress_Repository_Interface
	 */
	public function create(): Course_Progress_Repository_Interface {
		global $wpdb;

		$comments_based = $this->create_comments_based_repository();
		$tables_based   = new Tables_Based_Course_Progress_Repository( $wpdb );

		if ( ! $this->storage_configuration->is_dual_write_enabled() ) {
			return $comments_based;
		}

		if ( ! $this->storage_configuration->is_reading_from_tables() ) {
			return new Comment_Reading_Aggregate_Course_Progress_Repository( $comments_based, $tables_based );
		}

		return new Table_Reading_Aggregate_Course_Progress_Repository(
			$comments_based,
			$tables_based
		);
	}

	/**
	 * Create a comments based course progress repository.
	 *
	 * @internal
	 *
	 * @return Comments_Based_Course_Progress_Repository
	 */
	public function create_comments_based_repository(): Comments_Based_Course_Progress_Repository {
		return new Comments_Based_Course_Progress_Repository();
	}
}

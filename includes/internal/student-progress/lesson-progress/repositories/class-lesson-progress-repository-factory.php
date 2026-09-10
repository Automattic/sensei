<?php
/**
 * File containing the Lesson_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;

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
	 * Resolved progress storage configuration.
	 *
	 * @var Progress_Storage_Configuration
	 */
	private $storage_configuration;

	/**
	 * Lesson_Progress_Repository_Factory constructor.
	 *
	 * @param Progress_Storage_Configuration $storage_configuration Resolved progress storage configuration.
	 */
	public function __construct( Progress_Storage_Configuration $storage_configuration ) {
		$this->storage_configuration = $storage_configuration;
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

		if ( ! $this->storage_configuration->is_dual_write_enabled() ) {
			return new Comments_Based_Lesson_Progress_Repository();
		}

		if ( ! $this->storage_configuration->is_reading_from_tables() ) {
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

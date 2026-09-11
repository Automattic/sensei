<?php
/**
 * File containing the Submission_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Services\Progress_Storage_Configuration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Submission_Repository_Factory {

	/**
	 * Resolved progress storage configuration.
	 *
	 * @var Progress_Storage_Configuration
	 */
	private $storage_configuration;

	/**
	 * Submission_Repository_Factory constructor.
	 *
	 * @param Progress_Storage_Configuration $storage_configuration Resolved progress storage configuration.
	 */
	public function __construct( Progress_Storage_Configuration $storage_configuration ) {
		$this->storage_configuration = $storage_configuration;
	}

	/**
	 * Create a repository for the quiz submissions.
	 *
	 * @internal
	 *
	 * @return Submission_Repository_Interface
	 */
	public function create(): Submission_Repository_Interface {
		global $wpdb;

		if ( ! $this->storage_configuration->is_dual_write_enabled() ) {
			return new Comments_Based_Submission_Repository();
		}

		if ( ! $this->storage_configuration->is_reading_from_tables() ) {
			return new Comment_Reading_Aggregate_Submission_Repository(
				new Comments_Based_Submission_Repository(),
				new Tables_Based_Submission_Repository( $wpdb )
			);
		}

		return new Table_Reading_Aggregate_Submission_Repository(
			new Comments_Based_Submission_Repository(),
			new Tables_Based_Submission_Repository( $wpdb )
		);
	}
}

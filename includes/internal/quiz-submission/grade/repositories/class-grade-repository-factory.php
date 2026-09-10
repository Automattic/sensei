<?php
/**
 * File containing the Grade_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;
use Sensei\Internal\Services\Progress_Storage_Configuration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Grade_Repository_Factory {

	/**
	 * Resolved progress storage configuration.
	 *
	 * @var Progress_Storage_Configuration
	 */
	private $storage_configuration;

	/**
	 * Grade_Repository_Factory constructor.
	 *
	 * @internal
	 *
	 * @param Progress_Storage_Configuration $storage_configuration Resolved progress storage configuration.
	 */
	public function __construct( Progress_Storage_Configuration $storage_configuration ) {
		$this->storage_configuration = $storage_configuration;
	}

	/**
	 * Create a repository for the grades.
	 *
	 * @internal
	 *
	 * @return Grade_Repository_Interface
	 */
	public function create(): Grade_Repository_Interface {
		global $wpdb;

		if ( ! $this->storage_configuration->is_dual_write_enabled() ) {
			return new Comments_Based_Grade_Repository();
		}

		if ( ! $this->storage_configuration->is_reading_from_tables() ) {
			return new Comment_Reading_Aggregate_Grade_Repository(
				new Comments_Based_Grade_Repository(),
				new Tables_Based_Grade_Repository( $wpdb ),
				new Tables_Based_Submission_Repository( $wpdb ),
				new Tables_Based_Answer_Repository( $wpdb ),
				new Comments_Based_Answer_Repository()
			);
		}

		return new Table_Reading_Aggregate_Grade_Repository(
			new Comments_Based_Grade_Repository(),
			new Tables_Based_Grade_Repository( $wpdb ),
			new Comments_Based_Submission_Repository(),
			new Tables_Based_Answer_Repository( $wpdb ),
			new Comments_Based_Answer_Repository()
		);
	}
}

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
	 * Is tables based progress feature flag enabled.
	 *
	 * @var bool
	 */
	private $tables_enabled;

	/**
	 * Read from tables.
	 *
	 * @var bool
	 */
	private $read_tables;

	/**
	 * Grade_Repository_Factory constructor.
	 *
	 * @internal
	 *
	 * @param bool $tables_enabled Is tables based progress feature flag enabled.
	 * @param bool $read_tables    Read from tables.
	 */
	public function __construct( bool $tables_enabled, bool $read_tables ) {
		$this->tables_enabled = $tables_enabled;
		$this->read_tables    = $read_tables;
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

		if ( ! $this->tables_enabled ) {
			return new Comments_Based_Grade_Repository();
		}

		if ( ! $this->read_tables ) {
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

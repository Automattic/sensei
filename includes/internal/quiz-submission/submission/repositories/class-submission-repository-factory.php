<?php
/**
 * File containing the Submission_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Submission\Repositories;

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
	 * Submission_Repository_Factory constructor.
	 *
	 * @param bool $tables_enabled Is tables based progress feature flag enabled.
	 * @param bool $read_tables    Read from tables.
	 */
	public function __construct( bool $tables_enabled, bool $read_tables ) {
		$this->tables_enabled = $tables_enabled;
		$this->read_tables    = $read_tables;
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

		if ( ! $this->tables_enabled ) {
			return new Comments_Based_Submission_Repository();
		}

		if ( ! $this->read_tables ) {
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

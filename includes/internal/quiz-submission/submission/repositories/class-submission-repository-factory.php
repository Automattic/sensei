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
	 * Use tables-based repository.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Submission_Repository_Factory constructor.
	 *
	 * @param bool $use_tables Use tables-based repository.
	 */
	public function __construct( $use_tables = false ) {
		$this->use_tables = $use_tables;
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

		return new Aggregate_Submission_Repository(
			new Comments_Based_Submission_Repository(),
			new Tables_Based_Submission_Repository( $wpdb ),
			$this->use_tables
		);
	}
}

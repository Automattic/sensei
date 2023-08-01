<?php
/**
 * File containing the Answer_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Answer_Repository_Factory {
	/**
	 * Use tables-based repository.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Class constructor.
	 *
	 * @internal
	 *
	 * @param bool $use_tables Use tables-based repository.
	 */
	public function __construct( bool $use_tables = false ) {
		$this->use_tables = $use_tables;
	}

	/**
	 * Create a repository for the answers.
	 *
	 * @internal
	 *
	 * @return Answer_Repository_Interface
	 */
	public function create(): Answer_Repository_Interface {
		global $wpdb;

		return new Aggregate_Answer_Repository(
			new Comments_Based_Answer_Repository(),
			new Tables_Based_Answer_Repository( $wpdb ),
			new Tables_Based_Submission_Repository( $wpdb ),
			$this->use_tables
		);
	}
}

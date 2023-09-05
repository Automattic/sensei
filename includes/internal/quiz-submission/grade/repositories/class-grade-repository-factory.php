<?php
/**
 * File containing the Grade_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
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
	 * Use tables based implementation.
	 *
	 * @var bool $use_tables
	 */
	private $use_tables;

	/**
	 * Grade_Repository_Factory constructor.
	 *
	 * @internal
	 *
	 * @param bool $use_tables The flag if the tables based implementation is available for use.
	 */
	public function __construct( bool $use_tables ) {
		$this->use_tables = $use_tables;
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

		return new Aggregate_Grade_Repository(
			new Comments_Based_Grade_Repository(),
			new Tables_Based_Grade_Repository( $wpdb ),
			new Tables_Based_Submission_Repository( $wpdb ),
			new Tables_Based_Answer_Repository( $wpdb ),
			new Comments_Based_Answer_Repository(),
			$this->use_tables
		);
	}
}

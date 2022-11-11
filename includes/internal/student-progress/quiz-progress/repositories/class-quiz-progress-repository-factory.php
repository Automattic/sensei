<?php
/**
 * File containing the Quiz_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Quiz_Progress\Repositories;

/**
 * Class Quiz_Progress_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Quiz_Progress_Repository_Factory {

	/**
	 * Use tables based progress flag.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Quiz_Progress_Repository_Factory constructor.
	 *
	 * @param bool $use_tables Use tables based progress flag.
	 */
	public function __construct( bool $use_tables ) {
		$this->use_tables = $use_tables;
	}

	/**
	 * Creates a new quiz progress repository.
	 *
	 * @internal
	 *
	 * @return Quiz_Progress_Repository_Interface
	 */
	public function create(): Quiz_Progress_Repository_Interface {
		global $wpdb;

		return new Aggregate_Quiz_Progress_Repository(
			new Comments_Based_Quiz_Progress_Repository(),
			new Tables_Based_Quiz_Progress_Repository( $wpdb ),
			$this->use_tables
		);
	}
}

<?php
/**
 * File containing the Course_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

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
	 * Use tables based progress flag.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Course_Progress_Repository_Factory constructor.
	 *
	 * @param bool $use_tables Use tables based progress flag.
	 */
	public function __construct( bool $use_tables ) {
		$this->use_tables = $use_tables;
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

		return new Aggregate_Course_Progress_Repository(
			new Comments_Based_Course_Progress_Repository(),
			new Tables_Based_Course_Progress_Repository( $wpdb ),
			$this->use_tables
		);
	}
}

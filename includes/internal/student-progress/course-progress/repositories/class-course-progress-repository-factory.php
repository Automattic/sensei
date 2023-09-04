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
	 * Course_Progress_Repository_Factory constructor.
	 *
	 * @param bool $tables_enabled Is tables based progress feature flag enabled.
	 * @param bool $read_tables    Read from tables.
	 */
	public function __construct( bool $tables_enabled, bool $read_tables ) {
		$this->tables_enabled = $tables_enabled;
		$this->read_tables    = $read_tables;
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

		$comments_based = new Comments_Based_Course_Progress_Repository();
		$tables_based   = new Tables_Based_Course_Progress_Repository( $wpdb );

		if ( ! $this->tables_enabled ) {
			return $comments_based;
		}

		if ( ! $this->read_tables ) {
			return new Comment_Reading_Aggregate_Course_Progress_Repository( $comments_based, $tables_based );
		}

		return new Table_Reading_Aggregate_Course_Progress_Repository(
			$comments_based,
			$tables_based
		);
	}
}

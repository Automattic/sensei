<?php
/**
 * File containing the Lesson_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Lesson_Progress_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Lesson_Progress_Repository_Factory {
	/**
	 * Use tables based progress flag.
	 *
	 * @var bool
	 */
	private $use_tables;

	/**
	 * Lesson_Progress_Repository_Factory constructor.
	 *
	 * @param bool $use_tables Use tables based progress flag.
	 */
	public function __construct( bool $use_tables ) {
		$this->use_tables = $use_tables;
	}

	/**
	 * Creates a new lesson progress repository.
	 *
	 * @internal
	 *
	 * @return Lesson_Progress_Repository_Interface The repository.
	 */
	public function create(): Lesson_Progress_Repository_Interface {
		global $wpdb;

		return new Aggregate_Lesson_Progress_Repository(
			new Comments_Based_Lesson_Progress_Repository(),
			new Tables_Based_Lesson_Progress_Repository( $wpdb ),
			$this->use_tables
		);
	}
}

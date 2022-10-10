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
 * @since $$next-version$$
 */
class Quiz_Progress_Repository_Factory {
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
			true
		);
	}
}

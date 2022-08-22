<?php
/**
 * File containing the Grade_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Grade_Repository_Factory {
	/**
	 * Create a repository for the grades.
	 *
	 * @return Grade_Repository_Interface
	 */
	public function create(): Grade_Repository_Interface {
		return new Grade_Repository_Aggregate(
			new Grade_Tables_Repository(),
			new Grade_Comments_Repository(),
			false
		);
	}
}

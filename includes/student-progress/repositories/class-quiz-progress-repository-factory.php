<?php
/**
 * File containing the Quiz_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Repositories;

/**
 * Class Quiz_Progress_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Quiz_Progress_Repository_Factory {
	/**
	 * Creates a new quiz progress repository.
	 *
	 * @return Quiz_Progress_Repository_Interface
	 */
	public function create(): Quiz_Progress_Repository_Interface {
		return new Quiz_Progress_Comments_Repository();
	}
}

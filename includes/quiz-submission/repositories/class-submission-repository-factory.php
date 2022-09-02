<?php
/**
 * File containing the Submission_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Submission_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Submission_Repository_Factory {
	/**
	 * Create a repository for the quiz submissions.
	 *
	 * @return Submission_Repository_Interface
	 */
	public function create(): Submission_Repository_Interface {
		return new Submission_Comments_Repository();
	}
}

<?php
/**
 * File containing the Answer_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Quiz_Submission\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Answer_Repository_Factory {
	/**
	 * Create a repository for the answers.
	 *
	 * @return Answer_Repository_Interface
	 */
	public function create(): Answer_Repository_Interface {
		return new Answer_Comments_Repository( new Grade_Comments_Repository() );
	}
}

<?php
/**
 * File containing the Answer_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Answer\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Answer_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Answer_Repository_Factory {
	/**
	 * Create a repository for the answers.
	 *
	 * @internal
	 *
	 * @return Answer_Repository_Interface
	 */
	public function create(): Answer_Repository_Interface {
		return new Comments_Based_Answer_Repository();
	}
}

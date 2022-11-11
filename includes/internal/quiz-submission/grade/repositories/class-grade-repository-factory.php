<?php
/**
 * File containing the Grade_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Quiz_Submission\Grade\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grade_Repository_Factory.
 *
 * @internal
 *
 * @since 4.7.2
 */
class Grade_Repository_Factory {
	/**
	 * Create a repository for the grades.
	 *
	 * @internal
	 *
	 * @return Grade_Repository_Interface
	 */
	public function create(): Grade_Repository_Interface {
		return new Comments_Based_Grade_Repository();
	}
}

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
	 * Create a repository for a course progress.
	 *
	 * @internal
	 *
	 * @return Course_Progress_Repository_Interface
	 */
	public function create(): Course_Progress_Repository_Interface {
		return new Comments_Based_Course_Progress_Repository();
	}
}

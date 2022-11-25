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
	 * Creates a new lesson progress repository.
	 *
	 * @internal
	 *
	 * @return Lesson_Progress_Repository_Interface The repository.
	 */
	public function create(): Lesson_Progress_Repository_Interface {
		return new Comments_Based_Lesson_Progress_Repository();
	}
}

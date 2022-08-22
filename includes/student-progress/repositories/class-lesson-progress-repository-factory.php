<?php
/**
 * File containing the Sensei_Lesson_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\StudentProgress\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Progress_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Lesson_Progress_Repository_Factory {
	/**
	 * Creates a new lesson progress repository.
	 * @return Lesson_Progress_Repository_Interface The repository.
	 */
	public function create(): Lesson_Progress_Repository_Interface {
		return new Lesson_Progress_Comments_Repository();
	}
}

<?php
/**
 * File containing the Sensei_Lesson_Progress_Repository_Factory class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Lesson_Progress_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Sensei_Lesson_Progress_Repository_Factory {
	/**
	 * Creates a new lesson progress repository.
	 *
	 * @return Sensei_Lesson_Progress_Repository_Interface The repository.
	 */
	public function create(): Sensei_Lesson_Progress_Repository_Interface {
		return new Sensei_Lesson_Progress_Comments_Repository();
	}
}

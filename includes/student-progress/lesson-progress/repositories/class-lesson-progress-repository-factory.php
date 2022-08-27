<?php
/**
 * File containing the Sensei_Lesson_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Lesson_Progress\Repositories;

use Sensei\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository;
use Sensei\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface;

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
	 *
	 * @return Lesson_Progress_Repository_Interface The repository.
	 */
	public function create(): Lesson_Progress_Repository_Interface {
		return new Comments_Based_Lesson_Progress_Repository();
	}
}

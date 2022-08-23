<?php
/**
 * File containing the Sensei_Course_Progress_Tables_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Repositories;

use Sensei\Student_Progress\Models\Course_Progress_Interface;
use Sensei\Student_Progress\Models\Course_Progress_Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Progress_Tables_Repository.
 *
 * @since $$next-version$$
 */
class Course_Progress_Tables_Repository implements Course_Progress_Repository_Interface {
	public function create( int $course_id, int $user_id ): Course_Progress_Interface {
		return new Course_Progress_Tables( $course_id, $user_id );
	}
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface {
		// find and return
		return null;
	}
	public function has( int $course_id, int $user_id ): bool {
		// check
		return false;
	}
	public function save( Course_Progress_Interface $course_progress ): void {
		// save
	}
}

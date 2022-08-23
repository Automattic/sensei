<?php
/**
 * File containing the Sensei_Course_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Repositories;

use InvalidArgumentException;
use Sensei\Student_Progress\Models\Course_Progress_Comments;
use Sensei\Student_Progress\Models\Course_Progress_Interface;
use Sensei\Student_Progress\Models\Course_Progress_Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Progress_Repository_Factory.
 *
 * @since $$next-version$$
 */
class Course_Progress_Repository_Factory {
	/**
	 * Create a repository for a course progress.
	 *
	 * @return Course_Progress_Repository_Interface
	 */
	public function create(): Course_Progress_Repository_Interface {
		return new Course_Progress_Repository_Aggregate(
			$this->create_tables(),
			$this->create_comments(),
			false
		);
	}
	/**
	 * Creates a new course progress repository with tables-based storage.
	 *
	 * @return Course_Progress_Tables_Repository The course progress repository.
	 */
	public function create_tables(): Course_Progress_Tables_Repository {
		return new Course_Progress_Tables_Repository();
	}

	/**
	 * Creates a new course progress repository with comments-based storage.
	 *
	 * @return Course_Progress_Comments_Repository The course progress repository.
	 */
	public function create_comments(): Course_Progress_Comments_Repository {
		return new Course_Progress_Comments_Repository();
	}

	/**
	 * Creates a new course progress repository for the given course progress.
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 * @return Course_Progress_Repository_Interface
	 */
	public function create_for( Course_Progress_Interface $course_progress ): Course_Progress_Repository_Interface {
		if ( $course_progress instanceof Course_Progress_Comments ) {
			return $this->create_comments();
		}
		if ( $course_progress instanceof Course_Progress_Tables ) {
			return $this->create_tables();
		}
		throw new InvalidArgumentException( sprintf( 'Unknown course progress type "%s".', get_class( $course_progress ) ) );
	}
}

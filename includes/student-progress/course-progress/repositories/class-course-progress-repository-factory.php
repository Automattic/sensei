<?php
/**
 * File containing the Course_Progress_Repository_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Course_Progress\Repositories;

use InvalidArgumentException;
use Sensei\Student_Progress\Course_Progress\Models\Comments_Based_Course_Progress;
use Sensei\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei\Student_Progress\Course_Progress\Models\Tables_Based_Course_Progress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Progress_Repository_Factory.
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
		return new Aggregate_Course_Progress_Repository(
			$this->create_tables(),
			$this->create_comments(),
			false
		);
	}
	/**
	 * Creates a new course progress repository with tables-based storage.
	 *
	 * @return Tables_Based_Course_Progress_Repository The course progress repository.
	 */
	public function create_tables(): Tables_Based_Course_Progress_Repository {
		return new Tables_Based_Course_Progress_Repository();
	}

	/**
	 * Creates a new course progress repository with comments-based storage.
	 *
	 * @return Comments_Based_Course_Progress_Repository The course progress repository.
	 */
	public function create_comments(): Comments_Based_Course_Progress_Repository {
		return new Comments_Based_Course_Progress_Repository();
	}

	/**
	 * Creates a new course progress repository for the given course progress.
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 * @return Course_Progress_Repository_Interface
	 * @throws InvalidArgumentException When the course progress is not supported.
	 */
	public function create_for( Course_Progress_Interface $course_progress ): Course_Progress_Repository_Interface {
		if ( $course_progress instanceof Comments_Based_Course_Progress ) {
			return $this->create_comments();
		}
		if ( $course_progress instanceof Tables_Based_Course_Progress ) {
			return $this->create_tables();
		}
		throw new InvalidArgumentException( sprintf( 'Unknown course progress type "%s".', get_class( $course_progress ) ) );
	}
}

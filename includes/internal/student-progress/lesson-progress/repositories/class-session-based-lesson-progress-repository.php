<?php
/**
 * File containing the Session_Based_Lesson_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Lesson_Progress\Repositories;

use DateTime;
use SebastianBergmann\Timer\RuntimeException;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress;
use Sensei_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Session_Based_Lesson_Progress_Repository.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Session_Based_Lesson_Progress_Repository implements Lesson_Progress_Repository_Interface {


	private $session = [];

	public function __construct() {
		$this->session = $_SESSION['lessons'] ?? [];
	}

	private function persist() {
		$_SESSION['lessons'] = $this->session;
	}


	/**
	 * Creates a new lesson progress.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 *
	 * @return Lesson_Progress The lesson progress.
	 */
	public function create( int $lesson_id, int $user_id ): Lesson_Progress {

		$now = new DateTime( current_time( 'mysql' ), wp_timezone() );

		$this->session[ $lesson_id ] = new Lesson_Progress(
			0,
			$lesson_id,
			-1,
			Lesson_Progress::STATUS_IN_PROGRESS,
			$now,
			null,
			$now,
			$now
		);

		$this->persist();

		return $this->get( $lesson_id, $user_id );
	}

	/**
	 * Finds a lesson progress by lesson and user.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 *
	 * @return Lesson_Progress|null The lesson progress or null if not found.
	 */
	public function get( int $lesson_id, int $user_id ): ?Lesson_Progress {
		return $this->session[ $lesson_id ] ?? null;
	}

	/**
	 * Check if a lesson progress exists.
	 *
	 * @internal
	 *
	 * @param int $lesson_id The lesson ID.
	 * @param int $user_id   The user ID.
	 *
	 * @return bool
	 */
	public function has( int $lesson_id, int $user_id ): bool {
		return isset( $this->session[ $lesson_id ] );
	}

	/**
	 * Save the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function save( Lesson_Progress $lesson_progress ): void {
		$this->session[ $lesson_progress->get_lesson_id() ] = $lesson_progress;
		$this->persist();
	}

	/**
	 * Delete the lesson progress.
	 *
	 * @internal
	 *
	 * @param Lesson_Progress $lesson_progress The lesson progress.
	 */
	public function delete( Lesson_Progress $lesson_progress ): void {
		unset( $this->session[ $lesson_progress->get_lesson_id() ] );
		$this->persist();
	}

	/**
	 * Returns the number of started lessons for a user in a course.
	 * The number of started lessons is the same as the number of lessons that have a progress record.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id   The user ID.
	 *
	 * @return int
	 */
	public function count( int $course_id, int $user_id ): int {
		$lessons = Sensei()->course->course_lessons( $course_id, 'publish', 'ids' );

		if ( empty( $lessons ) ) {
			return 0;
		}

		return array_reduce( $lessons, function( int $count, int $lesson_id ) {
			return isset( $this->session[ $lesson_id ] ) ? ( $count + 1 ) : $count;
		}, 0 );
	}
}

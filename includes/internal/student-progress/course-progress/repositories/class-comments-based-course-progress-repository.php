<?php
/**
 * File containing the Comments_Based_Course_Progress_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Course_Progress\Repositories;

use DateTime;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Comments_Based_Course_Progress;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;
use Sensei_Utils;
use WP_Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comments_Based_Course_Progress_Repository
 *
 * @internal
 *
 * @since 4.7.2
 */
class Comments_Based_Course_Progress_Repository implements Course_Progress_Repository_Interface {

	/**
	 * Creates a new course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return Course_Progress_Interface The course progress.
	 * @throws \RuntimeException If the course progress could not be created.
	 */
	public function create( int $course_id, int $user_id ): Course_Progress_Interface {
		$metadata   = [
			'start'    => current_time( 'mysql' ),
			'percent'  => 0,
			'complete' => 0,
		];
		$comment_id = Sensei_Utils::update_course_status( $user_id, $course_id, Course_Progress_Interface::STATUS_IN_PROGRESS, $metadata );
		if ( ! $comment_id ) {
			throw new \RuntimeException( "Can't create a course progress" );
		}

		$progress = $this->get( $course_id, $user_id );
		if ( ! $progress ) {
			throw new \RuntimeException( 'Created course progress not found' );
		}

		return $progress;
	}

	/**
	 * Gets a course progress.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 *
	 * @return Comments_Based_Course_Progress|null The course progress.
	 */
	public function get( int $course_id, int $user_id ): ?Course_Progress_Interface {
		if ( ! $user_id ) {
			return null;
		}

		$activity_args = [
			'post_id' => $course_id,
			'user_id' => $user_id,
			'type'    => 'sensei_course_status',
		];
		$comment       = Sensei_Utils::sensei_check_for_activity( $activity_args, true );
		if ( ! $comment ) {
			return null;
		}

		if ( is_array( $comment ) ) {
			usort(
				$comment,
				[ $this, 'sort_comments' ]
			);
			$comment = reset( $comment );
		}

		if ( ! $comment instanceof WP_Comment ) {
			return null;
		}

		return $this->create_progress_from_comment( $comment );
	}

	/**
	 * Create a course progress from a comment.
	 *
	 * @param WP_Comment $comment The comment.
	 * @return Comments_Based_Course_Progress The course progress.
	 */
	private function create_progress_from_comment( WP_Comment $comment ): Comments_Based_Course_Progress {
		$meta_start = get_comment_meta( (int) $comment->comment_ID, 'start', true );
		$started_at = $meta_start ? new DateTime( $meta_start, wp_timezone() ) : current_datetime();

		$comment_date = new DateTime( $comment->comment_date, wp_timezone() );
		if ( Course_Progress_Interface::STATUS_COMPLETE === $comment->comment_approved ) {
			$completed_at = $comment_date;
		} else {
			$completed_at = null;
		}

		return new Comments_Based_Course_Progress(
			(int) $comment->comment_ID,
			(int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$comment->comment_approved,
			$started_at,
			$completed_at,
			$comment_date,
			$comment_date
		);
	}

	/**
	 * Sort comments by comment ID in descending order.
	 *
	 * @param \stdClass $a First comment to compare.
	 * @param \stdClass $b Second comment to compare.
	 *
	 * @return int
	 */
	private function sort_comments( $a, $b ) {
		$a_id = (int) $a->comment_ID;
		$b_id = (int) $b->comment_ID;
		if ( $a_id === $b_id ) {
			return 0;
		}
		return ( $a_id > $b_id ) ? -1 : 1;
	}

	/**
	 * Checks if a course progress exists.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 * @param int $user_id The user ID.
	 * @return bool Whether the course progress exists.
	 */
	public function has( int $course_id, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}

		$activity_args = [
			'post_id' => $course_id,
			'user_id' => $user_id,
			'type'    => 'sensei_course_status',
		];
		$count         = Sensei_Utils::sensei_check_for_activity( $activity_args );
		return $count > 0;
	}

	/**
	 * Save course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function save( Course_Progress_Interface $course_progress ): void {
		$this->assert_comments_based_course_progress( $course_progress );
		$metadata = [];
		if ( $course_progress->get_started_at() ) {
			$metadata['start'] = $course_progress->get_started_at()->format( 'Y-m-d H:i:s' );
		}
		Sensei_Utils::update_course_status( $course_progress->get_user_id(), $course_progress->get_course_id(), $course_progress->get_status(), $metadata );
	}

	/**
	 * Delete course progress.
	 *
	 * @internal
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 */
	public function delete( Course_Progress_Interface $course_progress ): void {
		$args = array(
			'post_id' => $course_progress->get_course_id(),
			'type'    => 'sensei_course_status',
			'user_id' => $course_progress->get_user_id(),
		);

		Sensei_Utils::sensei_delete_activities( $args );
	}

	/**
	 * Delete course progress for a given course.
	 *
	 * @internal
	 *
	 * @param int $course_id The course ID.
	 */
	public function delete_for_course( int $course_id ): void {
		$args = array(
			'post_id' => $course_id,
			'type'    => 'sensei_course_status',
		);

		$this->delete_activities( $args );
	}

	/**
	 * Delete course progress for a given user.
	 *
	 * @internal
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_for_user( int $user_id ): void {
		$args = array(
			'user_id' => $user_id,
			'type'    => 'sensei_course_status',
		);

		$this->delete_activities( $args );
	}

	/**
	 * Delete activity comments for a given set of arguments.
	 *
	 * @param array $args The arguments.
	 */
	private function delete_activities( array $args ): void {
		$comments = Sensei_Utils::sensei_check_for_activity( $args, true );
		if ( ! $comments ) {
			return;
		}

		$comments = is_array( $comments ) ? $comments : [ $comments ];
		$post_ids = [];
		foreach ( $comments as $comment ) {
			if ( isset( $comment->comment_post_ID ) ) {
				$post_ids[] = $comment->comment_post_ID;
			}

			if ( isset( $comment->comment_ID ) && 0 < $comment->comment_ID ) {
				wp_delete_comment( intval( $comment->comment_ID ), true );
			}
		}

		foreach ( $post_ids as $post_id ) {
			Sensei()->flush_comment_counts_cache( $post_id );
		}
	}

	/**
	 * Assert that the course progress is a Comments_Based_Course_Progress.
	 *
	 * @param Course_Progress_Interface $course_progress The course progress.
	 * @throws \InvalidArgumentException If the course progress is not a Comments_Based_Course_Progress.
	 */
	private function assert_comments_based_course_progress( Course_Progress_Interface $course_progress ): void {
		if ( ! $course_progress instanceof Comments_Based_Course_Progress ) {
			$actual_type = get_class( $course_progress );
			throw new \InvalidArgumentException( esc_html( "Expected Comments_Based_Course_Progress, got {$actual_type}." ) );
		}
	}

	/**
	 * Find course progress.
	 *
	 * @internal
	 *
	 * @param array $args The arguments.
	 * @return Course_Progress_Interface[] The course progress.
	 * @throws \InvalidArgumentException If the order by argument is not supported.
	 */
	public function find( array $args ): array {
		$comments_args = array(
			'type'    => 'sensei_course_status',
			'order'   => 'ASC',
			'orderby' => 'comment_ID',
		);

		if ( isset( $args['course_id'] ) ) {
			if ( is_array( $args['course_id'] ) ) {
				$comments_args['post__in'] = $args['course_id'];
			} else {
				$comments_args['post_id'] = $args['course_id'];
			}
		}

		if ( isset( $args['user_id'] ) ) {
			$comments_args['user_id'] = $args['user_id'];
		}

		if ( isset( $args['status'] ) ) {
			$comments_args['status'] = $args['status'];
		}

		if ( isset( $args['order'] ) ) {
			$comments_args['order'] = $args['order'];
		}

		if ( isset( $args['orderby'] ) ) {
			switch ( $args['orderby'] ) {
				case 'started_at':
					throw new \InvalidArgumentException( 'Ordering by started_at is not supported in comments-based version.' );
				case 'completed_at':
				case 'created_at':
				case 'updated_at':
					$comments_args['orderby'] = 'comment_date';
					break;
				case 'course_id':
					$comments_args['orderby'] = 'comment_post_ID';
					break;
				case 'id':
					$comments_args['orderby'] = 'comment_ID';
					break;
				case 'status':
					$comments_args['orderby'] = 'comment_approved';
					break;
				default:
					$comments_args['orderby'] = $args['orderby'];
					break;
			}
		}

		if ( isset( $args['order'] ) ) {
			$comments_args['order'] = $args['order'];
		}

		if ( isset( $args['offset'] ) ) {
			$comments_args['offset'] = $args['offset'];
		}

		if ( isset( $args['number'] ) ) {
			$comments_args['number'] = $args['number'];
		}

		$comments = \Sensei_Utils::sensei_check_for_activity( $comments_args, true );
		if ( empty( $comments ) ) {
			return array();
		}

		$comments = is_array( $comments ) ? $comments : array( $comments );

		$course_progresses = [];
		foreach ( $comments as $comment ) {
			$course_progresses[] = $this->create_progress_from_comment( $comment );
		}

		return $course_progresses;
	}
}

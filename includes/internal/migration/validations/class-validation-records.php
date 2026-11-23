<?php
/**
 * File containing the Validation_Records class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Migration\Validations;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use RuntimeException;
use Sensei\Internal\Quiz_Submission\Answer\Models\Answer_Interface;
use Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer;
use Sensei\Internal\Quiz_Submission\Answer\Models\Tables_Based_Answer;
use Sensei\Internal\Quiz_Submission\Grade\Models\Comments_Based_Grade;
use Sensei\Internal\Quiz_Submission\Grade\Models\Grade_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Models\Tables_Based_Grade;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei\Internal\Quiz_Submission\Submission\Models\Tables_Based_Submission;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Comments_Based_Course_Progress;
use Sensei\Internal\Student_Progress\Course_Progress\Models\Tables_Based_Course_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Comments_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Lesson_Progress\Models\Tables_Based_Lesson_Progress;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Comments_Based_Quiz_Progress;
use Sensei\Internal\Student_Progress\Quiz_Progress\Models\Tables_Based_Quiz_Progress;
use Sensei\Internal\Student_Progress\Progress_Start_Date;

/**
 * Reads persisted validation data directly, enumerating records in bounded batches.
 *
 * Stored IDs must stay unchanged: repository lookup filters can redirect reads to other records.
 *
 * @internal
 * @since 4.28.0
 */
class Validation_Records {
	private const BATCH_SIZE = 200;

	/**
	 * Get the comment records of a given activity type.
	 *
	 * @since 4.28.0
	 *
	 * @param string $comment_type The Sensei activity comment type.
	 * @return Generator<int, object>
	 */
	public static function get_comments( string $comment_type ): Generator {
		global $wpdb;

		return self::read(
			'SELECT comment_ID AS id, comment_post_ID AS post_id, user_id
			FROM %i
			WHERE comment_type = %s AND comment_ID > %d
			ORDER BY comment_ID
			LIMIT %d',
			array( $wpdb->comments, $comment_type ),
			$comment_type . ' comments'
		);
	}

	/**
	 * Get the HPPS progress records of a given type.
	 *
	 * @since 4.28.0
	 *
	 * @param string $type The progress type.
	 * @return Generator<int, object>
	 */
	public static function get_progress_rows( string $type ): Generator {
		global $wpdb;

		return self::read(
			'SELECT *
			FROM %i
			WHERE type = %s AND id > %d
			ORDER BY id
			LIMIT %d',
			array( $wpdb->prefix . 'sensei_lms_progress', $type ),
			$type . ' progress table'
		);
	}

	/**
	 * Get the HPPS quiz submission records.
	 *
	 * @since 4.28.0
	 *
	 * @return Generator<int, object>
	 */
	public static function get_submission_rows(): Generator {
		global $wpdb;

		return self::read(
			'SELECT id, quiz_id, user_id, final_grade, created_at, updated_at
			FROM %i
			WHERE id > %d
			ORDER BY id
			LIMIT %d',
			array( $wpdb->prefix . 'sensei_lms_quiz_submissions' ),
			'quiz submission table'
		);
	}

	/**
	 * Get the HPPS answer rows whose submission no longer exists.
	 *
	 * @since 4.28.0
	 *
	 * @return Generator<int, object>
	 */
	public static function get_answers_without_submissions(): Generator {
		global $wpdb;

		return self::read(
			'SELECT answers.id, answers.submission_id, answers.question_id
			FROM %i AS answers
			LEFT JOIN %i AS submissions ON submissions.id = answers.submission_id
			WHERE submissions.id IS NULL AND answers.id > %d
			ORDER BY answers.id
			LIMIT %d',
			array(
				$wpdb->prefix . 'sensei_lms_quiz_answers',
				$wpdb->prefix . 'sensei_lms_quiz_submissions',
			),
			'quiz answers table'
		);
	}

	/**
	 * Get the HPPS grade rows whose answer no longer exists.
	 *
	 * @since 4.28.0
	 *
	 * @return Generator<int, object>
	 */
	public static function get_grades_without_answers(): Generator {
		global $wpdb;

		return self::read(
			'SELECT grades.id, grades.answer_id, grades.question_id
			FROM %i AS grades
			LEFT JOIN %i AS answers ON answers.id = grades.answer_id
			WHERE answers.id IS NULL AND grades.id > %d
			ORDER BY grades.id
			LIMIT %d',
			array(
				$wpdb->prefix . 'sensei_lms_quiz_grades',
				$wpdb->prefix . 'sensei_lms_quiz_answers',
			),
			'quiz grades table'
		);
	}

	/**
	 * Get a lesson's quiz using the same status and title ordering as the lesson lookup.
	 *
	 * @since 4.28.0
	 *
	 * @param int $lesson_id The lesson ID.
	 * @return int The quiz ID, or zero when no quiz exists.
	 */
	public static function get_quiz_id( int $lesson_id ): int {
		global $wpdb;

		$excluded_statuses = get_post_stati( array( 'exclude_from_search' => true ) );
		$placeholders      = implode( ', ', array_fill( 0, count( $excluded_statuses ), '%s' ) );
		$status_condition  = $excluded_statuses ? ' AND post_status NOT IN (' . $placeholders . ')' : '';
		$rows              = self::query(
			'SELECT ID FROM %i WHERE post_parent = %d AND post_type = %s' . $status_condition . ' ORDER BY post_title DESC LIMIT 1',
			array_merge( array( $wpdb->posts, $lesson_id, 'quiz' ), array_values( $excluded_statuses ) ),
			'lesson quiz'
		);

		if ( $rows ) {
			return (int) $rows[0]->ID;
		}

		$quiz_id = (int) self::get_metadata( 'post', $lesson_id, '_lesson_quiz' );
		if ( ! $quiz_id ) {
			return 0;
		}

		$rows = self::query(
			'SELECT ID FROM %i WHERE ID = %d AND post_type = %s' . $status_condition . ' LIMIT 1',
			array_merge( array( $wpdb->posts, $quiz_id, 'quiz' ), array_values( $excluded_statuses ) ),
			'lesson quiz metadata target'
		);

		return $rows ? (int) $rows[0]->ID : 0;
	}

	/**
	 * Get the lesson recorded as the quiz's parent.
	 *
	 * @param int $quiz_id The quiz ID.
	 * @return int The lesson ID, or zero when the quiz does not exist.
	 */
	private static function get_lesson_id( int $quiz_id ): int {
		global $wpdb;

		$rows = self::query( 'SELECT post_parent FROM %i WHERE ID = %d', array( $wpdb->posts, $quiz_id ), 'quiz parent' );

		return $rows ? (int) $rows[0]->post_parent : 0;
	}

	/**
	 * Read a single metadata value without populating or consulting caches.
	 *
	 * @since 4.28.0
	 *
	 * @param string $type      The metadata type, post or comment.
	 * @param int    $object_id The object ID.
	 * @param string $key       The metadata key.
	 * @return mixed The first stored value, or an empty string when absent.
	 */
	public static function get_metadata( string $type, int $object_id, string $key ) {
		global $wpdb;

		$table = 'post' === $type ? $wpdb->postmeta : $wpdb->commentmeta;
		$rows  = self::query(
			'SELECT meta_value FROM %i WHERE %i = %d AND meta_key = %s ORDER BY meta_id LIMIT 1',
			array( $table, $type . '_id', $object_id, $key ),
			$type . ' metadata'
		);

		return $rows ? maybe_unserialize( $rows[0]->meta_value ) : '';
	}

	/**
	 * Read the comment selected by the comments repositories for a student and content.
	 *
	 * @param string $type    The progress type.
	 * @param int    $post_id The stored content ID.
	 * @param int    $user_id The student ID.
	 * @return object|null The stored comment.
	 */
	private static function get_progress_comment( string $type, int $post_id, int $user_id ): ?object {
		global $wpdb;

		if ( ! $user_id ) {
			return null;
		}

		$comment_type = 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status';
		$limit        = 'course' === $type ? 1 : 2;
		$rows         = self::query(
			'SELECT * FROM %i
			WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s
			ORDER BY comment_ID DESC LIMIT %d',
			array( $wpdb->comments, $post_id, $user_id, $comment_type, $limit ),
			$comment_type . ' comment'
		);

		// Course progress selects the newest duplicate; lesson and quiz repositories require one comment.
		return 1 === count( $rows ) ? $rows[0] : null;
	}

	/**
	 * Read comments progress and hydrate the existing progress model.
	 *
	 * @since 4.28.0
	 *
	 * @param string $type    The progress type.
	 * @param int    $post_id The content ID.
	 * @param int    $user_id The student ID.
	 * @return Comments_Based_Course_Progress|Comments_Based_Lesson_Progress|Comments_Based_Quiz_Progress|null
	 */
	public static function get_comments_progress( string $type, int $post_id, int $user_id ): ?object {
		$lesson_id = 'quiz' === $type ? self::get_lesson_id( $post_id ) : $post_id;
		$comment   = $lesson_id ? self::get_progress_comment( $type, $lesson_id, $user_id ) : null;
		if ( ! $comment ) {
			return null;
		}

		$classes = array(
			'course' => Comments_Based_Course_Progress::class,
			'lesson' => Comments_Based_Lesson_Progress::class,
			'quiz'   => Comments_Based_Quiz_Progress::class,
		);
		$class   = $classes[ $type ];
		$start   = self::get_metadata( 'comment', (int) $comment->comment_ID, 'start' );
		$date    = new DateTimeImmutable( $comment->comment_date, wp_timezone() );
		$status  = $comment->comment_approved;

		if ( 'course' === $type ) {
			$completed = 'complete' === $status;
		} else {
			$completed = in_array( $status, array( 'complete', 'passed', 'graded' ), true );
		}

		return new $class(
			(int) $comment->comment_ID,
			'quiz' === $type ? $post_id : (int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$status,
			Progress_Start_Date::is_recorded( $start ) ? new DateTimeImmutable( $start, wp_timezone() ) : null,
			$completed ? new DateTimeImmutable( $comment->comment_date_gmt, new DateTimeZone( 'UTC' ) ) : null,
			$date,
			$date
		);
	}

	/**
	 * Read a matching progress table row directly.
	 *
	 * @since 4.28.0
	 *
	 * @param string $type    The progress type.
	 * @param int    $post_id The content ID.
	 * @param int    $user_id The student ID.
	 * @return Tables_Based_Course_Progress|Tables_Based_Lesson_Progress|Tables_Based_Quiz_Progress|null
	 */
	public static function get_tables_progress( string $type, int $post_id, int $user_id ): ?object {
		global $wpdb;

		$rows = self::query(
			'SELECT * FROM %i WHERE type = %s AND post_id = %d AND user_id = %d LIMIT 1',
			array( $wpdb->prefix . 'sensei_lms_progress', $type, $post_id, $user_id ),
			$type . ' progress table'
		);

		return $rows ? self::create_tables_progress( $type, $rows[0] ) : null;
	}

	/**
	 * Hydrate an existing progress model from a freshly read table row.
	 *
	 * @since 4.28.0
	 *
	 * @param string $type The progress type.
	 * @param object $row  The stored progress row.
	 * @return Tables_Based_Course_Progress|Tables_Based_Lesson_Progress|Tables_Based_Quiz_Progress
	 */
	public static function create_tables_progress( string $type, object $row ): object {
		$classes  = array(
			'course' => Tables_Based_Course_Progress::class,
			'lesson' => Tables_Based_Lesson_Progress::class,
			'quiz'   => Tables_Based_Quiz_Progress::class,
		);
		$class    = $classes[ $type ];
		$timezone = new DateTimeZone( 'UTC' );

		return new $class(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			Progress_Start_Date::is_recorded( $row->started_at ) ? new DateTimeImmutable( $row->started_at, $timezone ) : null,
			$row->completed_at ? new DateTimeImmutable( $row->completed_at, $timezone ) : null,
			new DateTimeImmutable( $row->created_at, $timezone ),
			new DateTimeImmutable( $row->updated_at, $timezone )
		);
	}

	/**
	 * Read a comments submission, including the metadata identifying it as a submission.
	 *
	 * @since 4.28.0
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The student ID.
	 * @return Comments_Based_Submission|null
	 */
	public static function get_comments_submission( int $quiz_id, int $user_id ): ?Comments_Based_Submission {
		$lesson_id = self::get_lesson_id( $quiz_id );
		$comment   = $lesson_id ? self::get_progress_comment( 'lesson', $lesson_id, $user_id ) : null;
		if ( ! $comment ) {
			return null;
		}

		if ( ! self::get_metadata( 'comment', (int) $comment->comment_ID, 'questions_asked' ) ) {
			return null;
		}

		$grade = self::get_metadata( 'comment', (int) $comment->comment_ID, 'grade' );
		$start = self::get_metadata( 'comment', (int) $comment->comment_ID, 'start' );
		$date  = new DateTimeImmutable( $start ? $start : 'now', wp_timezone() );

		return new Comments_Based_Submission(
			(int) $comment->comment_ID,
			$quiz_id,
			$user_id,
			is_numeric( $grade ) ? (float) $grade : null,
			$date,
			$date
		);
	}

	/**
	 * Read a matching HPPS submission directly.
	 *
	 * @since 4.28.0
	 *
	 * @param int $quiz_id The quiz ID.
	 * @param int $user_id The student ID.
	 * @return Tables_Based_Submission|null
	 */
	public static function get_tables_submission( int $quiz_id, int $user_id ): ?Tables_Based_Submission {
		global $wpdb;

		$rows = self::query(
			'SELECT * FROM %i WHERE quiz_id = %d AND user_id = %d LIMIT 1',
			array( $wpdb->prefix . 'sensei_lms_quiz_submissions', $quiz_id, $user_id ),
			'quiz submission table'
		);
		return $rows ? self::create_tables_submission( $rows[0] ) : null;
	}

	/**
	 * Hydrate an existing submission model from a freshly read table row.
	 *
	 * @since 4.28.0
	 *
	 * @param object $row The stored submission row.
	 * @return Tables_Based_Submission
	 */
	public static function create_tables_submission( object $row ): Tables_Based_Submission {
		$timezone = new DateTimeZone( 'UTC' );

		return new Tables_Based_Submission(
			(int) $row->id,
			(int) $row->quiz_id,
			(int) $row->user_id,
			$row->final_grade,
			new DateTimeImmutable( $row->created_at, $timezone ),
			new DateTimeImmutable( $row->updated_at, $timezone )
		);
	}

	/**
	 * Read all answers for a submission from its own store.
	 *
	 * @since 4.28.0
	 *
	 * @param int    $submission_id The submission ID.
	 * @param string $store         The storage source, comments or tables.
	 * @return Answer_Interface[]
	 */
	public static function get_answers( int $submission_id, string $store ): array {
		global $wpdb;

		$answers = array();
		if ( 'comments' === $store ) {
			$values = self::get_metadata( 'comment', $submission_id, 'quiz_answers' );
			$date   = current_datetime();
			foreach ( is_array( $values ) ? $values : array() as $question_id => $value ) {
				$answers[] = new Comments_Based_Answer( $submission_id, $question_id, $value, $date, $date );
			}

			return $answers;
		}

		$rows     = self::query(
			'SELECT * FROM %i WHERE submission_id = %d ORDER BY id',
			array( $wpdb->prefix . 'sensei_lms_quiz_answers', $submission_id ),
			'quiz answers table'
		);
		$timezone = new DateTimeZone( 'UTC' );
		foreach ( $rows as $row ) {
			$answers[] = new Tables_Based_Answer(
				$row->id,
				$row->submission_id,
				$row->question_id,
				$row->value,
				new DateTimeImmutable( $row->created_at, $timezone ),
				new DateTimeImmutable( $row->updated_at, $timezone )
			);
		}

		return $answers;
	}

	/**
	 * Read all grades reachable through a submission's answers.
	 *
	 * @since 4.28.0
	 *
	 * @param int    $submission_id The submission ID.
	 * @param string $store         The storage source, comments or tables.
	 * @return Grade_Interface[]
	 */
	public static function get_grades( int $submission_id, string $store ): array {
		global $wpdb;

		$grades = array();
		if ( 'comments' === $store ) {
			$points   = self::get_metadata( 'comment', $submission_id, 'quiz_grades' );
			$feedback = self::get_metadata( 'comment', $submission_id, 'quiz_answers_feedback' );
			$date     = current_datetime();
			foreach ( is_array( $points ) ? $points : array() as $question_id => $value ) {
				$grades[] = new Comments_Based_Grade( $question_id, $value, $feedback[ $question_id ] ?? null, $date, $date );
			}

			return $grades;
		}

		$rows     = self::query(
			'SELECT grades.* FROM %i AS grades
			INNER JOIN %i AS answers ON answers.id = grades.answer_id
			WHERE answers.submission_id = %d ORDER BY grades.id',
			array( $wpdb->prefix . 'sensei_lms_quiz_grades', $wpdb->prefix . 'sensei_lms_quiz_answers', $submission_id ),
			'quiz grades table'
		);
		$timezone = new DateTimeZone( 'UTC' );
		foreach ( $rows as $row ) {
			$grades[] = new Tables_Based_Grade(
				$row->id,
				$row->answer_id,
				$row->question_id,
				$row->points,
				$row->feedback,
				new DateTimeImmutable( $row->created_at, $timezone ),
				new DateTimeImmutable( $row->updated_at, $timezone )
			);
		}

		return $grades;
	}

	/**
	 * Read stored records and report database read failures.
	 *
	 * @param string $query  The prepared statement template.
	 * @param array  $args   Placeholder values.
	 * @param string $source The storage source being read.
	 * @return object[] Stored records.
	 * @throws RuntimeException If the records cannot be read.
	 */
	private static function query( string $query, array $args, string $source ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Validation must read stored values rather than caches.
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $args ) );
		if ( $wpdb->last_error || ! is_array( $rows ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The source is a fixed identifier.
			throw new RuntimeException( 'Could not read ' . $source . ' during validation.' );
		}
		return $rows;
	}

	/**
	 * Read a query whose last two arguments are the primary key cursor and limit.
	 *
	 * @param string $query   The prepared statement template.
	 * @param array  $args    The arguments preceding the cursor and limit.
	 * @param string $source  The storage source being scanned.
	 * @return Generator<int, object>
	 * @throws RuntimeException If a batch cannot be read.
	 */
	private static function read( string $query, array $args, string $source ): Generator {
		$last_id = 0;

		do {
			$rows = self::query( $query, array_merge( $args, array( $last_id, self::BATCH_SIZE ) ), $source );

			$row_count = count( $rows );
			foreach ( $rows as $row ) {
				$last_id = (int) $row->id;
				yield $row;
			}
		} while ( self::BATCH_SIZE === $row_count );
	}
}

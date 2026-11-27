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
use Sensei\Internal\Student_Progress\Comment_Progress;
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
	private const BATCH_SIZE        = 200;
	private const COMMENT_META_KEYS = array( 'start', 'questions_asked', 'grade', 'quiz_answers', 'quiz_grades', 'quiz_answers_feedback' );

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
	 * @param string     $type      The metadata type, post or comment.
	 * @param int        $object_id The object ID.
	 * @param string     $key       The metadata key.
	 * @param array|null $records   Shared rows for one targeted inspection, or null for a fresh read.
	 * @return mixed The first stored value, or an empty string when absent.
	 */
	public static function get_metadata( string $type, int $object_id, string $key, ?array &$records = null ) {
		global $wpdb;

		if ( 'comment' === $type && null !== $records && in_array( $key, self::COMMENT_META_KEYS, true ) ) {
			foreach ( self::get_comment_metadata_rows( $object_id, $records ) as $row ) {
				if ( (int) $row->comment_id === $object_id && $row->meta_key === $key ) {
					return maybe_unserialize( $row->meta_value );
				}
			}

			return '';
		}

		$table = 'post' === $type ? $wpdb->postmeta : $wpdb->commentmeta;
		$rows  = self::query(
			'SELECT meta_value FROM %i WHERE %i = %d AND meta_key = %s ORDER BY meta_id LIMIT 1',
			array( $table, $type . '_id', $object_id, $key ),
			$type . ' metadata'
		);

		return $rows ? maybe_unserialize( $rows[0]->meta_value ) : '';
	}

	/**
	 * Read relevant metadata for all inspected comments, retaining duplicate rows.
	 *
	 * @param int        $comment_id The comment ID to use when no comments have been collected.
	 * @param array|null $records    Shared rows for one targeted inspection, or null for a fresh read.
	 * @return object[] Stored metadata rows.
	 */
	private static function get_comment_metadata_rows( int $comment_id, ?array &$records = null ): array {
		global $wpdb;

		$comment_ids = array_column( $records['comments'] ?? array(), 'comment_ID' );
		$comment_ids = $comment_ids ? $comment_ids : array( $comment_id );
		$ids         = implode( ', ', array_fill( 0, count( $comment_ids ), '%d' ) );
		$keys        = implode( ', ', array_fill( 0, count( self::COMMENT_META_KEYS ), '%s' ) );

		return self::query(
			'SELECT meta_id, comment_id, meta_key, meta_value FROM %i
			WHERE comment_id IN (' . $ids . ') AND meta_key IN (' . $keys . ') ORDER BY comment_id, meta_id',
			array_merge( array( $wpdb->commentmeta ), $comment_ids, self::COMMENT_META_KEYS ),
			'comment metadata',
			$records,
			'comment_meta'
		);
	}

	/**
	 * Read the lowest-ID progress comment, matching migration and duplicate cleanup.
	 *
	 * Historical duplicates can remain until progress is updated again.
	 *
	 * @see \Sensei_Learner::remove_duplicate_progress()
	 *
	 * @param string     $type    The progress type.
	 * @param int        $post_id The stored content ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared rows for one targeted inspection.
	 * @return object|null The stored comment.
	 */
	private static function get_progress_comment( string $type, int $post_id, int $user_id, ?array &$records = null ): ?object {
		global $wpdb;

		if ( ! $user_id ) {
			return null;
		}

		$comment_type = 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status';
		$columns      = null === $records ? '*' : 'comment_ID, comment_post_ID, user_id, comment_approved, comment_date, comment_date_gmt';
		$rows         = self::query(
			'SELECT ' . $columns . ' FROM %i
			WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s
			ORDER BY comment_ID' . ( null === $records ? ' LIMIT 1' : '' ),
			array( $wpdb->comments, $post_id, $user_id, $comment_type ),
			$comment_type . ' comment',
			$records,
			'comments'
		);

		if ( null !== $records && $rows ) {
			self::get_comment_metadata_rows( (int) $rows[0]->comment_ID, $records );
		}

		return $rows[0] ?? null;
	}

	/**
	 * Read comments progress and hydrate the existing progress model.
	 *
	 * @since 4.28.0
	 *
	 * @param string     $type    The progress type.
	 * @param int        $post_id The content ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared rows for one targeted inspection, or null for a fresh read.
	 * @return Comments_Based_Course_Progress|Comments_Based_Lesson_Progress|Comments_Based_Quiz_Progress|null
	 */
	public static function get_comments_progress( string $type, int $post_id, int $user_id, ?array &$records = null ): ?object {
		$lesson_id = 'quiz' === $type ? self::get_lesson_id( $post_id ) : $post_id;
		$comment   = $lesson_id ? self::get_progress_comment( $type, $lesson_id, $user_id, $records ) : null;
		if ( ! $comment ) {
			return null;
		}

		$classes = array(
			'course' => Comments_Based_Course_Progress::class,
			'lesson' => Comments_Based_Lesson_Progress::class,
			'quiz'   => Comments_Based_Quiz_Progress::class,
		);
		$class   = $classes[ $type ];
		$start   = self::get_metadata( 'comment', (int) $comment->comment_ID, 'start', $records );
		$date    = new DateTimeImmutable( $comment->comment_date, wp_timezone() );
		$saved   = null;

		if ( 'post-trashed' === $comment->comment_approved ) {
			$statuses = self::get_metadata( 'post', (int) $comment->comment_post_ID, '_wp_trash_meta_comments_status' );
			$saved    = is_array( $statuses ) ? ( $statuses[ $comment->comment_ID ] ?? null ) : null;
		}
		$status = Comment_Progress::resolve_status( $type, $comment->comment_approved, $saved );
		if ( null === $status ) {
			$records['unverifiable_trash_status'] = (int) $comment->comment_ID;
			return null;
		}

		$pass_required = true;
		if ( 'lesson' === $type && 'failed' === $status ) {
			$quiz_id       = self::get_quiz_id( (int) $comment->comment_post_ID );
			$pass_required = ! $quiz_id || (bool) self::get_metadata( 'post', $quiz_id, '_pass_required' );
		}
		$completed_at = Comment_Progress::get_completed_at( $type, $status, $comment->comment_date_gmt, $pass_required );

		return new $class(
			(int) $comment->comment_ID,
			'quiz' === $type ? $post_id : (int) $comment->comment_post_ID,
			(int) $comment->user_id,
			$status,
			Progress_Start_Date::is_recorded( $start ) ? new DateTimeImmutable( $start, wp_timezone() ) : null,
			null !== $completed_at ? new DateTimeImmutable( $completed_at, new DateTimeZone( 'UTC' ) ) : null,
			$date,
			$date
		);
	}

	/**
	 * Read a matching progress table row directly.
	 *
	 * @since 4.28.0
	 *
	 * @param string     $type    The progress type.
	 * @param int        $post_id The content ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared rows for one targeted inspection, or null for a fresh read.
	 * @return Tables_Based_Course_Progress|Tables_Based_Lesson_Progress|Tables_Based_Quiz_Progress|null
	 */
	public static function get_tables_progress( string $type, int $post_id, int $user_id, ?array &$records = null ): ?object {
		global $wpdb;

		$rows = self::query(
			'SELECT * FROM %i WHERE type = %s AND post_id = %d AND user_id = %d' . ( null === $records ? ' LIMIT 1' : ' ORDER BY id' ),
			array( $wpdb->prefix . 'sensei_lms_progress', $type, $post_id, $user_id ),
			$type . ' progress table',
			$records,
			'tables'
		);

		return 1 === count( $rows ) ? self::create_tables_progress( $type, $rows[0] ) : null;
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
	 * @param int        $quiz_id The quiz ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared rows for one targeted inspection, or null for a fresh read.
	 * @return Comments_Based_Submission|null
	 */
	public static function get_comments_submission( int $quiz_id, int $user_id, ?array &$records = null ): ?Comments_Based_Submission {
		$lesson_id = self::get_lesson_id( $quiz_id );
		$comment   = $lesson_id ? self::get_progress_comment( 'lesson', $lesson_id, $user_id, $records ) : null;
		if ( ! $comment ) {
			return null;
		}

		if ( ! self::get_metadata( 'comment', (int) $comment->comment_ID, 'questions_asked', $records ) ) {
			return null;
		}

		$grade = self::get_metadata( 'comment', (int) $comment->comment_ID, 'grade', $records );
		$start = self::get_metadata( 'comment', (int) $comment->comment_ID, 'start', $records );
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
	 * @param int        $quiz_id The quiz ID.
	 * @param int        $user_id The student ID.
	 * @param array|null $records Shared rows for one targeted inspection, or null for a fresh read.
	 * @return Tables_Based_Submission|null
	 */
	public static function get_tables_submission( int $quiz_id, int $user_id, ?array &$records = null ): ?Tables_Based_Submission {
		global $wpdb;

		$rows = self::query(
			'SELECT * FROM %i WHERE quiz_id = %d AND user_id = %d' . ( null === $records ? ' LIMIT 1' : ' ORDER BY id' ),
			array( $wpdb->prefix . 'sensei_lms_quiz_submissions', $quiz_id, $user_id ),
			'quiz submission table',
			$records,
			'submissions'
		);
		return 1 === count( $rows ) ? self::create_tables_submission( $rows[0] ) : null;
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
	 * @param int        $submission_id The submission ID, or zero to collect rows for ambiguous submissions.
	 * @param string     $store         The storage source, comments or tables.
	 * @param array|null $records       Shared rows for one targeted inspection, or null for a fresh read.
	 * @return Answer_Interface[]
	 */
	public static function get_answers( int $submission_id, string $store, ?array &$records = null ): array {
		$answers = array();
		if ( 'comments' === $store ) {
			$values = self::get_metadata( 'comment', $submission_id, 'quiz_answers', $records );
			$date   = current_datetime();
			foreach ( is_array( $values ) ? $values : array() as $question_id => $value ) {
				$answers[] = new Comments_Based_Answer( $submission_id, $question_id, $value, $date, $date );
			}

			return $answers;
		}

		$rows = self::get_tables_answer_rows( $submission_id, $records );
		if ( ! $submission_id ) {
			return array();
		}

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
	 * Read answer rows for the inspected submissions, retaining duplicate identities.
	 *
	 * @since $$next-version$$
	 *
	 * @param int        $submission_id The submission ID to use when no submissions have been collected.
	 * @param array|null $records       Shared rows for one targeted inspection, or null for a fresh read.
	 * @return object[] Stored answer rows.
	 */
	public static function get_tables_answer_rows( int $submission_id, ?array &$records = null ): array {
		global $wpdb;

		$submission_ids = array_column( $records['submissions'] ?? array(), 'id' );
		$submission_ids = $submission_ids ? $submission_ids : array( $submission_id );
		$ids            = implode( ', ', array_fill( 0, count( $submission_ids ), '%d' ) );

		return self::query(
			'SELECT * FROM %i WHERE submission_id IN (' . $ids . ') ORDER BY id',
			array_merge( array( $wpdb->prefix . 'sensei_lms_quiz_answers' ), $submission_ids ),
			'quiz answers table',
			$records,
			'answers'
		);
	}

	/**
	 * Read all grades reachable through a submission's answers.
	 *
	 * @since 4.28.0
	 *
	 * @param int        $submission_id The submission ID, or zero to collect rows for ambiguous submissions.
	 * @param string     $store         The storage source, comments or tables.
	 * @param array|null $records       Shared rows for one targeted inspection, or null for a fresh read.
	 * @return Grade_Interface[]
	 */
	public static function get_grades( int $submission_id, string $store, ?array &$records = null ): array {
		$grades = array();
		if ( 'comments' === $store ) {
			$points   = self::get_metadata( 'comment', $submission_id, 'quiz_grades', $records );
			$feedback = self::get_metadata( 'comment', $submission_id, 'quiz_answers_feedback', $records );
			$date     = current_datetime();
			foreach ( is_array( $points ) ? $points : array() as $question_id => $value ) {
				$grades[] = new Comments_Based_Grade( $question_id, $value, $feedback[ $question_id ] ?? null, $date, $date );
			}

			return $grades;
		}

		$rows = self::get_tables_grade_rows( $submission_id, $records );
		if ( ! $submission_id ) {
			return array();
		}

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
	 * Read grade rows through the inspected submissions' answers, retaining duplicate identities.
	 *
	 * @since $$next-version$$
	 *
	 * @param int        $submission_id The submission ID to use when no submissions have been collected.
	 * @param array|null $records       Shared rows for one targeted inspection, or null for a fresh read.
	 * @return object[] Stored grade rows.
	 */
	public static function get_tables_grade_rows( int $submission_id, ?array &$records = null ): array {
		global $wpdb;

		$submission_ids = array_column( $records['submissions'] ?? array(), 'id' );
		$submission_ids = $submission_ids ? $submission_ids : array( $submission_id );
		$ids            = implode( ', ', array_fill( 0, count( $submission_ids ), '%d' ) );

		return self::query(
			'SELECT grades.* FROM %i AS grades
			INNER JOIN %i AS answers ON answers.id = grades.answer_id
			WHERE answers.submission_id IN (' . $ids . ') ORDER BY grades.id',
			array_merge( array( $wpdb->prefix . 'sensei_lms_quiz_grades', $wpdb->prefix . 'sensei_lms_quiz_answers' ), $submission_ids ),
			'quiz grades table',
			$records,
			'grades'
		);
	}

	/**
	 * Read stored records and report database read failures.
	 *
	 * @param string     $query   The prepared statement template.
	 * @param array      $args    Placeholder values.
	 * @param string     $source  The storage source being read.
	 * @param array|null $records Shared rows for one targeted inspection.
	 * @param string     $kind    The group of stored rows to retain and reuse.
	 * @return object[] Stored records.
	 * @throws RuntimeException If the records cannot be read.
	 */
	private static function query( string $query, array $args, string $source, ?array &$records = null, string $kind = '' ): array {
		global $wpdb;

		if ( null !== $records && array_key_exists( $kind, $records ) ) {
			return $records[ $kind ];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Validation must read stored values rather than caches.
		$rows = $wpdb->get_results( $wpdb->prepare( $query, $args ) );
		if ( $wpdb->last_error || ! is_array( $rows ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The source is a fixed identifier.
			throw new RuntimeException( 'Could not read ' . $source . ' during validation.' );
		}

		if ( null !== $records ) {
			$records[ $kind ] = $rows;
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

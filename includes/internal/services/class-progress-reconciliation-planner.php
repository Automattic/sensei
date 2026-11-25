<?php
/**
 * Plan progress repairs without changing stored data.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- The plan must use current database values, not cached values.

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Sensei\Internal\Migration\Validations\Quiz_Submission_Validation;
use Sensei\Internal\Migration\Validations\Validation_Records;
use Throwable;

/**
 * Work out which progress values can be copied safely, without writing them.
 *
 * @internal
 * @since $$next-version$$
 */
class Progress_Reconciliation_Planner {

	/**
	 * Find the next batch of records to check in both stores.
	 *
	 * The same student and content can appear in more than one batch. Checking
	 * them again is safe, so callers can save where they stopped without
	 * keeping a list of every record already seen.
	 *
	 * @since $$next-version$$
	 * @param array $cursor Which store is being scanned and the last row ID read.
	 * @param int   $limit Maximum number of rows to read, from 1 to 200.
	 * @return array Student and content IDs, where to resume, and whether the scan is done.
	 */
	public function discover( array $cursor = array(), int $limit = 100 ): array {
		global $wpdb;

		$stage  = (int) ( $cursor['stage'] ?? 0 );
		$after  = (int) ( $cursor['after'] ?? 0 );
		$limit  = max( 1, min( 200, $limit ) );
		$stages = array(
			array( $wpdb->comments, 'comment_ID', 'comment_post_ID', 'sensei_course_status', 'course' ),
			array( $wpdb->comments, 'comment_ID', 'comment_post_ID', 'sensei_lesson_status', 'lesson' ),
			array( $wpdb->prefix . 'sensei_lms_progress', 'id', 'post_id', null, null ),
			array( $wpdb->prefix . 'sensei_lms_quiz_submissions', 'id', 'quiz_id', null, 'quiz' ),
		);
		$units  = array();

		if ( $stage < 0 || $stage >= count( $stages ) ) {
			return array(
				'identities' => array(),
				'cursor'     => array(
					'stage' => count( $stages ),
					'after' => 0,
				),
				'complete'   => true,
			);
		}

		list( $table, $key, $content, $comment_type, $type ) = $stages[ $stage ];
		$condition = $comment_type ? $wpdb->prepare( ' AND comment_type = %s', $comment_type ) : '';
		$rows      = $this->query(
			$wpdb->prepare( 'SELECT * FROM %i WHERE %i > %d', $table, $key, $after ) . $condition
			. $wpdb->prepare( ' ORDER BY %i LIMIT %d', $key, $limit )
		);

		foreach ( $rows as $row ) {
			$units[] = $this->resolve_identity( $type ?? $row->type, (int) $row->$content, (int) $row->user_id );
			$after   = (int) $row->$key;
		}

		if ( count( $rows ) < $limit ) {
			++$stage;
			$after = 0;
		}

		return array(
			'identities' => $units,
			'cursor'     => array(
				'stage' => $stage,
				'after' => $after,
			),
			'complete'   => count( $stages ) === $stage,
		);
	}

	/**
	 * Check one course, or a lesson and its quiz, and propose safe changes.
	 *
	 * These changes describe the data just read. Read and plan again before
	 * writing, because ordinary saves may have changed it.
	 *
	 * @since $$next-version$$
	 * @param string $type Course, lesson, or quiz.
	 * @param int    $content_id The course, lesson or quiz ID.
	 * @param int    $student_id The student ID.
	 * @param string $source Store to copy from: comments or tables.
	 * @return array What differs and which changes can be proposed.
	 * @throws InvalidArgumentException If the content type, IDs or source are invalid.
	 */
	public function inspect( string $type, int $content_id, int $student_id, string $source ): array {
		if ( ! in_array( $type, array( 'course', 'lesson', 'quiz' ), true )
			|| $content_id < 1 || $student_id < 1
			|| ! in_array( $source, array( 'comments', 'tables' ), true ) ) {
			throw new InvalidArgumentException( 'Provide a positive course, lesson, or quiz identity and an explicit comments or tables source.' );
		}

		$result = array(
			'identity'                  => array(
				'type'       => $type,
				'content_id' => $content_id,
				'student_id' => $student_id,
			),
			'source'                    => $source,
			'destination'               => 'comments' === $source ? 'tables' : 'comments',
			'outcome'                   => 'failed',
			'applied'                   => false,
			'supported_fields_verified' => false,
			'equivalent'                => false,
			'unresolved'                => array(),
			'destination_only'          => array(),
			'differences'               => array(),
			'changes'                   => array(),
		);

		try {
			$this->require_source( $source );
			$identity           = $this->resolve_identity( $type, $content_id, $student_id );
			$result['identity'] = $identity;

			if ( 'quiz' === $identity['type'] ) {
				$result['outcome']      = 'unresolved';
				$result['unresolved'][] = 'The quiz has no persisted lesson mapping.';
			} elseif ( ! $this->identity_exists( $identity ) ) {
				$result['outcome']      = 'unresolved';
				$result['unresolved'][] = 'The persisted content or student no longer exists; surviving data is preserved.';
			} else {
				// Supported fields can match while an uncertain date or an extra
				// destination record still needs review.
				$bundle                              = $this->inspect_bundle( $identity );
				$plan                                = $this->plan( $bundle, $identity, $source );
				$result['changes']                   = $plan['writes'];
				$result['unresolved']                = $plan['unresolved'];
				$result['destination_only']          = $plan['destination_only'];
				$result['differences']               = $this->differences( $bundle );
				$result['supported_fields_verified'] = empty( $result['changes'] );
				$result['equivalent']                = empty( $result['differences'] ) && empty( $result['unresolved'] );
				$result['outcome']                   = $result['equivalent'] ? 'already_equivalent' : 'unresolved';
			}
		} catch ( Throwable $exception ) {
			$result['outcome']                   = 409 === $exception->getCode() ? 'conflicting' : 'failed';
			$result['supported_fields_verified'] = false;
			$result['equivalent']                = false;
			$result['changes']                   = array();
			$result['error']                     = $exception->getMessage();
		}

		return $result;
	}

	/**
	 * Check whether the current copies agree, without returning a change list.
	 *
	 * @since $$next-version$$
	 * @param string $type Course, lesson, or quiz.
	 * @param int    $content_id The course, lesson or quiz ID.
	 * @param int    $student_id The student ID.
	 * @param string $source Store to copy from: comments or tables.
	 * @return array Whether the copies agree now and what still needs review.
	 */
	public function verify( string $type, int $content_id, int $student_id, string $source ): array {
		$result = $this->inspect( $type, $content_id, $student_id, $source );
		unset( $result['changes'] );
		return $result;
	}

	/**
	 * Check that the student and course or lesson still exist.
	 *
	 * @param array $identity The student ID and the IDs for this course or lesson and quiz.
	 * @return bool
	 */
	private function identity_exists( array $identity ): bool {
		global $wpdb;

		$content = $this->query(
			$wpdb->prepare(
				'SELECT ID FROM %i WHERE ID = %d AND post_type = %s',
				$wpdb->posts,
				$identity['content_id'],
				$identity['type']
			)
		);
		$student = $this->query( $wpdb->prepare( 'SELECT ID FROM %i WHERE ID = %d', $wpdb->users, $identity['student_id'] ) );

		return ! empty( $content ) && ! empty( $student );
	}

	/**
	 * Find the IDs that belong together, keeping a lesson and its quiz in one group.
	 *
	 * @since $$next-version$$
	 * @param string $type The course, lesson, or quiz type.
	 * @param int    $content_id The course, lesson or quiz ID stored in the database.
	 * @param int    $student_id The student ID.
	 * @return array The student, course or lesson, and related quiz IDs.
	 */
	public function resolve_identity( string $type, int $content_id, int $student_id ): array {
		global $wpdb;

		$quiz_id = 0;
		if ( 'quiz' === $type ) {
			$rows    = $this->query( $wpdb->prepare( 'SELECT post_parent FROM %i WHERE ID = %d AND post_type = %s', $wpdb->posts, $content_id, 'quiz' ) );
			$quiz_id = $content_id;
			if ( $rows && (int) $rows[0]->post_parent > 0 ) {
				$type       = 'lesson';
				$content_id = (int) $rows[0]->post_parent;
			}
		} elseif ( 'lesson' === $type ) {
			$quiz_id = Validation_Records::get_quiz_id( $content_id );
		}

		return array(
			'type'       => $type,
			'content_id' => $content_id,
			'student_id' => $student_id,
			'quiz_id'    => $quiz_id,
		);
	}

	/**
	 * Stop if the saved settings no longer allow copying from this source.
	 *
	 * @since $$next-version$$
	 * @param string $source The store to copy from.
	 * @throws RuntimeException If a read or required safety check fails.
	 */
	public function require_source( string $source ): void {
		global $wpdb;

		$rows = $this->query(
			$wpdb->prepare(
				'SELECT option_name, option_value FROM %i WHERE option_name IN (%s, %s, %s)',
				$wpdb->options,
				'sensei-settings',
				'timezone_string',
				'gmt_offset'
			)
		);
		$data = array_column( $rows, 'option_value', 'option_name' );
		$raw  = maybe_unserialize( $data['sensei-settings'] ?? '' );
		$raw  = is_array( $raw ) ? $raw : array();
		if ( true !== ( $raw['experimental_progress_storage'] ?? false ) || true !== ( $raw['experimental_progress_storage_synchronization'] ?? false ) ) {
			throw new RuntimeException( 'Progress storage and synchronization must be enabled before reconciliation.', 409 );
		}
		$saved_source = Progress_Storage_Settings::TABLES_STORAGE === ( $raw['experimental_progress_storage_repository'] ?? '' )
			? 'tables' : 'comments';

		if ( $saved_source !== $source ) {
			throw new RuntimeException( 'The saved storage source changed; stop this reconciliation direction.', 409 );
		}

		// Use the saved timezone when reading dates, even if the option cache is old.
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'timezone_string', 'options' );
		wp_cache_delete( 'gmt_offset', 'options' );
	}

	/**
	 * Read the course, or read the lesson and its quiz data together.
	 *
	 * @param array $identity The student ID and the IDs for this course or lesson and quiz.
	 */
	private function inspect_bundle( array $identity ): array {
		$scopes = array( $identity['type'] => $identity['content_id'] );
		if ( $identity['quiz_id'] ) {
			$scopes['quiz'] = $identity['quiz_id'];
		}

		$bundle = array();
		foreach ( $scopes as $type => $post_id ) {
			$bundle[ $type ] = Progress_Inspection::inspect( $type, $post_id, $identity['student_id'] );
		}

		return $bundle;
	}

	/**
	 * Build the change list while keeping extra destination records for review.
	 *
	 * @param array  $bundle The course or lesson/quiz comparison results.
	 * @param array  $identity The student ID and the IDs for this course or lesson and quiz.
	 * @param string $source The store to copy from.
	 */
	private function plan( array $bundle, array $identity, string $source ): array {
		$destination = 'comments' === $source ? 'tables' : 'comments';
		$plan        = array(
			'writes'           => array(),
			'unresolved'       => array(),
			'destination_only' => array(),
		);

		foreach ( $bundle as $type => $record ) {
			if ( Validation_Records::has_duplicates( $record['rows'] ) ) {
				$plan['unresolved'][] = $type . ': duplicate persisted identities or metadata.';
				return $plan;
			}
		}

		if ( 'tables' === $destination ) {
			foreach ( $bundle as $type => $record ) {
				$inspection = $record['progress'];
				$from       = $inspection[ $source ];
				$to         = $inspection[ $destination ];

				if ( ! $from && $to && $inspection['errors'] ) {
					$plan['unresolved'][]       = $type . ': destination-only progress is preserved.';
					$plan['destination_only'][] = array(
						'kind'       => $type . '_progress',
						'content_id' => $to['post_id'],
					);
					continue;
				}
				if ( ! $from || ! $inspection['errors'] ) {
					continue;
				}

				$data    = array();
				$changes = Progress_Inspection::field_changes( $inspection, $source, array( 'status', 'started_at', 'completed_at' ) );
				foreach ( $inspection['ignored_fields'] as $field ) {
					$plan['unresolved'][] = $type . ': ' . $field . ' cannot be verified.';
				}
				foreach ( $changes as $field => $change ) {
					if ( 'started_at' === $field && null === $from[ $field ] && $to && null !== $to[ $field ] ) {
						$plan['unresolved'][] = $type . ': an unrecorded source start cannot replace the surviving destination date.';
						continue;
					}
					$data[ $field ] = $change['after'];
				}

				if ( $data ) {
					$plan['writes'][] = array(
						'kind'       => 'progress',
						'id'         => $to['id'] ?? 0,
						'data'       => $data,
						'type'       => $type,
						'post_id'    => $from['post_id'],
						'student_id' => $identity['student_id'],
					);
				}
			}
		} else {
			$this->plan_comment_progress( $bundle, $identity, $plan );
		}

		if ( isset( $bundle['quiz'] ) ) {
			$this->plan_submission( $bundle['quiz'], $identity, $source, $plan );
		}

		foreach ( $bundle as $type => $record ) {
			foreach ( $record['invalid_dates'] as $field ) {
				$plan['unresolved'][] = $type . ': invalid ' . $field . ' is preserved.';
			}
			foreach ( $record['progress']['warnings'] as $warning ) {
				$plan['unresolved'][] = $type . ': ' . $warning->get_message();
			}
		}

		$plan['unresolved'] = array_values( array_unique( $plan['unresolved'] ) );
		return $plan;
	}

	/**
	 * Plan comment changes without making lesson and quiz progress disagree.
	 *
	 * @param array $bundle The course or lesson/quiz comparison results.
	 * @param array $identity The student ID and the IDs for this course or lesson and quiz.
	 * @param array $plan Proposed changes and values that still need review.
	 */
	private function plan_comment_progress( array $bundle, array $identity, array &$plan ): void {
		$main       = $bundle[ $identity['type'] ]['progress'];
		$lesson     = $main['tables'];
		$quiz       = $bundle['quiz']['progress']['tables'] ?? null;
		$quiz_check = $bundle['quiz']['progress'] ?? array(
			'errors'         => array(),
			'ignored_fields' => array(),
		);
		$comment    = $main['comments'];

		if ( ! $lesson && ! $quiz ) {
			if ( $comment ) {
				$plan['unresolved'][]       = 'Destination-only progress is preserved.';
				$plan['destination_only'][] = array(
					'kind'       => $identity['type'] . '_progress',
					'content_id' => $identity['content_id'],
				);
			}
			return;
		}

		if ( ! $lesson && 'lesson' === $identity['type'] && ( $comment || $quiz_check['errors'] ) ) {
			$plan['unresolved'][] = 'Quiz data cannot create shared lesson progress that is absent from the source.';
			if ( $comment ) {
				$plan['destination_only'][] = array(
					'kind'       => 'lesson_progress',
					'content_id' => $identity['content_id'],
				);
			}
			return;
		}

		if ( ! $quiz && ! empty( $quiz_check['comments'] ) && $quiz_check['errors'] ) {
			// The shared comment still holds quiz progress. A missing source quiz
			// does not mean we can replace that progress with lesson values.
			$plan['unresolved'][]       = 'Destination-only quiz progress is preserved; shared lesson status and dates remain unresolved.';
			$plan['destination_only'][] = array(
				'kind'       => 'quiz_progress',
				'content_id' => $identity['quiz_id'],
			);
			return;
		}

		$needs_lesson = $lesson && $main['errors'];
		$needs_quiz   = $quiz && $quiz_check['errors'];
		if ( ! $needs_lesson && ! $needs_quiz ) {
			return;
		}

		// One comment stores both statuses. The quiz status must also give the
		// lesson its expected status when the lesson reader interprets it.
		$quiz_has_status = $quiz && ( $needs_quiz || 'in-progress' !== $quiz['status'] );
		$raw_status      = $quiz_has_status ? $quiz['status'] : $lesson['status'];
		$pass_required   = $identity['quiz_id']
			? (bool) Validation_Records::get_metadata( 'post', $identity['quiz_id'], '_pass_required' ) : false;
		$lesson_status   = in_array( $raw_status, array( 'complete', 'passed', 'graded' ), true )
			|| ( 'failed' === $raw_status && ! $pass_required ) ? 'complete' : 'in-progress';
		$compatible      = ! $lesson || $lesson_status === $lesson['status'];
		// Comment readers expose completion only for these raw statuses. Changing
		// status can therefore invent or hide a completion without changing a date.
		$has_completion = 'course' === $identity['type']
			? 'complete' === $raw_status : in_array( $raw_status, array( 'complete', 'passed', 'graded' ), true );
		if ( ( $lesson && ( null !== $lesson['completed_at'] ) !== $has_completion )
			|| ( $quiz_has_status && ( null !== $quiz['completed_at'] ) !== $has_completion ) ) {
			$compatible           = false;
			$plan['unresolved'][] = 'Source status and completion cannot share one comment.';
		}

		$data = array();
		if ( $compatible ) {
			$data['comment_approved'] = $raw_status;
		} elseif ( $lesson && $lesson_status !== $lesson['status'] ) {
			$plan['unresolved'][] = 'Lesson and quiz statuses cannot share one comment.';
		}

		foreach ( array( 'started_at', 'completed_at' ) as $field ) {
			$ignored = in_array( $field, $main['ignored_fields'], true )
				|| ( $quiz_check && in_array( $field, $quiz_check['ignored_fields'], true ) );
			if ( $quiz_has_status && null !== $quiz ) {
				$value = $quiz[ $field ];
			} else {
				$value = $lesson[ $field ] ?? null;
			}
			$other = $lesson[ $field ] ?? null;
			if ( $ignored || ( $lesson && $quiz_has_status && $value !== $other ) || ( 'completed_at' === $field && ! $compatible ) ) {
				$plan['unresolved'][] = 'Shared comment ' . $field . ' cannot be reconciled.';
				continue;
			}

			if ( 'started_at' === $field ) {
				if ( null === $value && $comment && null !== $comment[ $field ] ) {
					$plan['unresolved'][] = 'An unrecorded source start cannot replace the shared comment date.';
					continue;
				}
				if ( $value ) {
					// When clocks go back, two UTC times can share one local time.
					// Only copy it if converting back gives the original UTC value.
					$local_start = $this->local_date( $value );
					$round_trip  = new DateTimeImmutable( $local_start, wp_timezone() );
					if ( $round_trip->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) !== $value ) {
						$plan['unresolved'][] = 'The source start cannot be represented unambiguously in the comment timezone.';
						continue;
					}
				}
				if ( ! $comment || $value !== $comment[ $field ] ) {
					$data['start'] = $value ? $this->local_date( $value ) : null;
				}
			} elseif ( null === $value && $comment && null !== $comment[ $field ] && 'complete' === ( $lesson['status'] ?? '' ) ) {
				$plan['unresolved'][] = 'A completed comment cannot represent an absent completion date.';
			} elseif ( $value && ( ! $comment || $value !== $comment[ $field ] ) ) {
				$data['comment_date_gmt'] = $value;
				$data['comment_date']     = $this->local_date( $value );
			}
		}

		if ( $comment && isset( $data['comment_approved'] ) ) {
			$raw = $bundle[ $identity['type'] ]['rows']['comments'][0]->comment_approved;
			if ( $raw === $data['comment_approved'] ) {
				unset( $data['comment_approved'] );
			}
		}

		if ( $data && ( $comment || $compatible ) ) {
			$plan['writes'][] = array(
				'kind'       => 'comment',
				'id'         => $comment['id'] ?? 0,
				'data'       => $data,
				'type'       => $identity['type'],
				'post_id'    => $identity['content_id'],
				'student_id' => $identity['student_id'],
			);
		}
	}

	/**
	 * Plan answer and grade changes by question ID, keeping extra destination data.
	 *
	 * @param array  $record The quiz progress and submission comparison results.
	 * @param array  $identity The student ID and the IDs for this course or lesson and quiz.
	 * @param string $source The store to copy from.
	 * @param array  $plan Proposed changes and values that still need review.
	 */
	private function plan_submission( array $record, array $identity, string $source, array &$plan ): void {
		$inspection  = $record['submission'];
		$destination = 'comments' === $source ? 'tables' : 'comments';
		$from        = $inspection[ $source ];
		$to          = $inspection[ $destination ];
		$metadata    = array();

		foreach ( $record['rows']['comment_meta'] as $meta ) {
			$metadata[ $meta->meta_key ] = maybe_unserialize( $meta->meta_value );
			if ( in_array( $meta->meta_key, array( 'quiz_answers', 'quiz_grades', 'quiz_answers_feedback' ), true )
				&& '' !== $meta->meta_value && ! is_array( $metadata[ $meta->meta_key ] ) ) {
				$plan['unresolved'][] = 'Malformed quiz collections are preserved for review.';
				return;
			}
		}

		// Grade models omit feedback whose points are missing. Keep that raw
		// feedback visible, including when there is no submission model to compare.
		$unmatched_feedback = array_diff_key( (array) ( $metadata['quiz_answers_feedback'] ?? array() ), (array) ( $metadata['quiz_grades'] ?? array() ) );
		foreach ( $unmatched_feedback as $question_id => $feedback ) {
			if ( null === $feedback || '' === $feedback ) {
				unset( $unmatched_feedback[ $question_id ] );
				continue;
			}
			$plan['unresolved'][] = 'Feedback for question ' . $question_id . ' has no comment grade and is preserved for review.';
		}

		if ( $inspection['comments'] && $inspection['tables'] ) {
			// Comments keep the submitted question list separately from answers;
			// tables derive it from answer rows. Compare sets without replacing either.
			$questions_asked   = $metadata['questions_asked'] ?? '';
			$comment_questions = is_scalar( $questions_asked )
				? array_unique( array_map( 'intval', explode( ',', (string) $questions_asked ) ) ) : array();
			$table_questions   = array_unique( array_column( $inspection['tables']['answers'], 'question_id' ) );
			sort( $comment_questions );
			sort( $table_questions );
			if ( ! is_scalar( $questions_asked ) || $comment_questions !== $table_questions ) {
				$plan['unresolved'][] = 'Submitted question identities differ between comments and tables and are preserved for review.';
			}
		}

		if ( ! $from ) {
			if ( $to ) {
				$plan['unresolved'][]       = 'Destination-only submission and children are preserved.';
				$plan['destination_only'][] = array(
					'kind'    => 'submission',
					'quiz_id' => $identity['quiz_id'],
				);
			}
			return;
		}

		// Comments identify a submission through its question metadata. An empty
		// table submission has no answer question IDs to put in that metadata.
		if ( 'comments' === $destination && ! $to && empty( $from['answers'] ) ) {
			$plan['unresolved'][] = 'An empty HPPS submission has no question identity for a comments submission.';
			return;
		}

		$creates_comment = array_filter(
			$plan['writes'],
			static function ( array $write ): bool {
				return 'comment' === $write['kind'];
			}
		);
		if ( 'comments' === $destination && ! $record['progress']['comments'] && ! $creates_comment ) {
			$plan['unresolved'][] = 'A submission cannot invent missing shared lesson progress.';
			return;
		}

		$submission_changes = Progress_Inspection::field_changes( $inspection, $source, array( 'final_grade' ) );
		// No source grade does not prove that a surviving grade should be cleared.
		// Zero is still a real grade and must be kept too.
		if ( $to && null === $from['final_grade'] && null !== $to['final_grade'] ) {
			$plan['unresolved'][]       = 'An unexplained destination-only final grade is preserved.';
			$plan['destination_only'][] = array(
				'kind'    => 'final_grade',
				'quiz_id' => $identity['quiz_id'],
			);
		} elseif ( isset( $submission_changes['final_grade'] ) ) {
			$plan['writes'][] = array(
				'kind'        => 'submission',
				'destination' => $destination,
				'data'        => array( 'final_grade' => $from['final_grade'] ),
				'quiz_id'     => $identity['quiz_id'],
				'student_id'  => $identity['student_id'],
				'post_id'     => $identity['content_id'],
			);
		}

		foreach ( array( 'answers', 'grades' ) as $kind ) {
			$children = Quiz_Submission_Validation::question_changes( $from[ $kind ], $to[ $kind ] ?? array() );
			foreach ( $children['extra'] as $question_id => $child ) {
				$plan['unresolved'][]       = 'Destination-only ' . $kind . ' for question ' . $question_id . ' are preserved.';
				$plan['destination_only'][] = array(
					'kind'        => 'answers' === $kind ? 'answer' : 'grade',
					'quiz_id'     => $identity['quiz_id'],
					'question_id' => (int) $question_id,
				);
			}
			foreach ( $children['changed'] as $question_id => $child ) {
				if ( 'grades' === $kind && 'comments' === $destination && isset( $unmatched_feedback[ $question_id ] )
					&& $child['feedback'] !== $unmatched_feedback[ $question_id ] ) {
					// Copying points also writes feedback; do not erase or replace a
					// surviving value that the destination grade model could not read.
					continue;
				}
				// Table grades point to answer rows, so a grade needs either an
				// existing destination answer or one we can copy from the source.
				$available_answers = array_column( $from['answers'], null, 'question_id' )
					+ array_column( $to['answers'] ?? array(), null, 'question_id' );
				if ( 'grades' === $kind && 'tables' === $destination && ! isset( $available_answers[ $question_id ] ) ) {
					$plan['unresolved'][] = 'The grade for question ' . $question_id . ' has no answer and is preserved for review.';
					continue;
				}
				$plan['writes'][] = array(
					'kind'        => $kind,
					'destination' => $destination,
					'data'        => $child,
					'quiz_id'     => $identity['quiz_id'],
					'student_id'  => $identity['student_id'],
					'post_id'     => $identity['content_id'],
				);
			}
		}
	}

	/**
	 * Collect differences and warnings reported by the validators.
	 *
	 * @param array $bundle The course or lesson/quiz comparison results.
	 */
	private function differences( array $bundle ): array {
		$differences = array();
		foreach ( $bundle as $type => $record ) {
			foreach ( array_merge( $record['progress']['errors'], $record['progress']['warnings'], $record['submission']['errors'] ?? array() ) as $error ) {
				$differences[] = array(
					'type'    => $type,
					'message' => $error->get_message(),
					'data'    => $error->get_data(),
				);
			}
		}
		return $differences;
	}

	/**
	 * Convert a UTC time to the site timezone used by comments.
	 *
	 * @param string $utc The UTC datetime value.
	 */
	private function local_date( string $utc ): string {
		$date = new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) );
		return $date->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Read database rows and stop if the query fails.
	 *
	 * @param string $sql The SQL read to run, with any values already prepared.
	 * @throws RuntimeException If a read or required safety check fails.
	 */
	private function query( string $sql ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are prepared by the caller; table names come from wpdb.
		$rows = $wpdb->get_results( $sql );
		if ( $wpdb->last_error || ! is_array( $rows ) ) {
			throw new RuntimeException( 'Could not read persisted reconciliation data.' );
		}
		return $rows;
	}
}

<?php
/**
 * Targeted recovery preview integration tests.
 *
 * @package sensei-tests
 */

namespace SenseiTest\CLI;

use Sensei_Progress_Recovery_Preview;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Tables_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Tables_Based_Grade_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Tables_Based_Submission_Repository;

/**
 * Verify targeted inspection and conditional repair descriptions.
 *
 * @covers \Sensei_Progress_Recovery_Preview
 */
class Sensei_Progress_Recovery_Preview_Test extends \WP_UnitTestCase {
	use \Sensei_HPPS_Helpers;

	protected function setUp(): void {
		parent::setUp();
		$this->maybe_enable_hpps_tables_repository();
	}

	public function tearDown(): void {
		$this->maybe_reset_hpps_repository();
		parent::tearDown();
	}

	/**
	 * A missing copy cannot establish whether progress should survive.
	 *
	 * @dataProvider missing_copy_cases
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_OneCopyWasMissing_RequiresIntentReview( string $surviving_store ): void {
		$course_id  = ( new \Sensei_Factory() )->course->create();
		$student_id = self::factory()->user->create();
		$this->create_progress( 'course', $course_id, $student_id, $surviving_store );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', $course_id, $student_id, $surviving_store );

		$progress = $preview['records'][0]['progress'];
		self::assertSame( 'ambiguous_missing', $progress['status'], 'A surviving row is not evidence of copy intent.' );
		self::assertStringContainsString( 'intended deletion', $progress['review'], 'Explain the failed-delete alternative.' );
		self::assertSame( 'copy', $progress['changes'][0]['if_confirmed'], 'Copying is only conditional on confirmed intent.' );
	}

	/**
	 * Missing copies can occur in either direction.
	 *
	 * @return array
	 */
	public static function missing_copy_cases(): array {
		return array(
			'comments survived' => array( 'comments' ),
			'tables survived'   => array( 'tables' ),
		);
	}

	/**
	 * An absent hypothetical source must never propose deleting the surviving copy.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_ChosenSourceWasAbsent_ProposesNoDeletion(): void {
		$this->create_progress( 'course', 987654, 876543, 'tables' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', 987654, 876543, 'comments' );

		$progress = $preview['records'][0]['progress'];
		self::assertSame( array(), $progress['changes'], 'No operation should remove the surviving orphan identity.' );
		self::assertStringContainsString( 'no deletion is proposed', $progress['review'], 'Explain why choosing an absent source is insufficient.' );
	}

	/**
	 * Choosing no source leaves differing values for the operator to resolve.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_NoSourceWasChosen_ReportsBothRepairAlternatives(): void {
		$this->create_progress( 'course', 987654, 876543, 'comments', 'complete' );
		$this->create_progress( 'course', 987654, 876543, 'tables', 'in-progress' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', 987654, 876543 );

		$progress = $preview['records'][0]['progress'];
		self::assertSame( 'different', $progress['status'], 'Stored IDs remain inspectable after user or content removal.' );
		self::assertSame(
			array(
				'Use comments values only after confirming them.',
				'Use tables values only after confirming them.',
			),
			$progress['alternatives'],
			'Offer both stores for review without selecting either one.'
		);
		self::assertSame( array(), $progress['changes'], 'The selected read store must not select the repair source.' );
	}

	/**
	 * Source selection must remain independent of the active read store.
	 *
	 * @dataProvider source_cases
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_SourceWasExplicit_PreviewsOnlyItsDifferingValues( string $source, string $comments_status, string $tables_status, string $expected_status, array $expected_changes ): void {
		$this->create_progress( 'course', 123, 456, 'comments', $comments_status );
		$this->create_progress( 'course', 123, 456, 'tables', $tables_status );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', 123, 456, $source );

		$progress = $preview['records'][0]['progress'];
		self::assertSame( $expected_status, $progress['status'], 'Distinguish matching copies from differing values.' );
		self::assertSame( $expected_changes, $progress['changes'], 'Describe only differing fields, with the chosen direction and exact before and after values.' );
	}

	/**
	 * Both sources are available regardless of the configured read store.
	 *
	 * @return array
	 */
	public static function source_cases(): array {
		return array(
			'matching copies' => array( 'comments', 'in-progress', 'in-progress', 'equivalent', array() ),
			'comments source' => array(
				'comments',
				'complete',
				'in-progress',
				'different',
				array(
					array(
						'if_confirmed' => 'replace',
						'from'         => 'comments',
						'to'           => 'tables',
						'field'        => 'status',
						'before'       => 'in-progress',
						'after'        => 'complete',
					),
					array(
						'if_confirmed' => 'replace',
						'from'         => 'comments',
						'to'           => 'tables',
						'field'        => 'completed_at',
						'before'       => null,
						'after'        => '2025-01-03 00:00:00',
					),
				),
			),
			'tables source'   => array(
				'tables',
				'complete',
				'in-progress',
				'different',
				array(
					array(
						'if_confirmed' => 'replace',
						'from'         => 'tables',
						'to'           => 'comments',
						'field'        => 'status',
						'before'       => 'complete',
						'after'        => 'in-progress',
					),
					array(
						'if_confirmed' => 'replace',
						'from'         => 'tables',
						'to'           => 'comments',
						'field'        => 'completed_at',
						'before'       => '2025-01-03 00:00:00',
						'after'        => null,
					),
				),
			),
		);
	}

	/**
	 * Quiz bundles must show answers and grades even when the comment was deleted.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_QuizCommentWasDeleted_PreservesAndShowsTheWholeBundle(): void {
		global $wpdb;
		$factory    = new \Sensei_Factory();
		$lesson_id  = $factory->lesson->create();
		$quiz_id    = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$student_id = self::factory()->user->create();
		$question   = $factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, $student_id, 0 );
		$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'retained answer' );
		$grade      = ( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 0, 'retained feedback' );
		$before     = $this->snapshot_database();

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, $student_id, 'tables' );

		$bundle = $preview['records'][0]['submission'];
		self::assertSame( 0.0, $bundle['tables']['final_grade'], 'A stored zero grade is distinct from no grade.' );
		self::assertSame( 'retained answer', $bundle['tables']['answers'][0]['value'], 'Include the answer value.' );
		self::assertSame( 'retained feedback', $bundle['tables']['grades'][0]['feedback'], 'Include the question grade feedback.' );
		self::assertSame( (string) $grade->get_id(), $preview['records'][0]['stored']['grades'][0]->id, 'Show the actual stored grade ID.' );
		self::assertSame( $before, $this->snapshot_database(), 'Preview must leave every database table unchanged.' );
	}

	/**
	 * Changes to a submission must include its answer and question grade differences.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_QuizValuesDiffered_PreviewsTheRelatedSubmissionValues(): void {
		global $wpdb;
		$factory   = new \Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$question  = $factory->question->create( array( 'quiz_id' => $quiz_id ) );
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Locate the stored fixture without repository mapping.
		$comment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d", $lesson_id ) );
		update_comment_meta( $comment_id, 'questions_asked', (string) $question );
		update_comment_meta( $comment_id, 'grade', 80 );
		update_comment_meta( $comment_id, 'quiz_answers', array( $question => 'comments answer' ) );
		update_comment_meta( $comment_id, 'quiz_grades', array( $question => 2 ) );
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, 456, 20 );
		$answer     = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question, 'tables answer' );
		( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer, $question, 1 );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, 456, 'comments' );

		$changes = array_column( $preview['records'][0]['submission']['changes'], null, 'field' );
		self::assertSame( 80.0, $changes['final_grade']['after'], 'Include the final grade difference.' );
		self::assertSame( 'comments answer', $changes['answers']['after'][0]['value'], 'Include the stored answer difference.' );
		self::assertSame( 2, $changes['grades']['after'][0]['points'], 'Include the related question grade difference.' );
	}

	/**
	 * One-sided question data cannot establish an intended deletion.
	 *
	 * @dataProvider missing_quiz_child_cases
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_QuizChildWasMissing_RequiresIntentReview(
		string $kind,
		string $source,
		array $comments,
		array $tables,
		string $missing_from,
		array $expected_changes,
		array $expected_withheld_fields
	): void {
		global $wpdb;
		$factory   = new \Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Locate the stored fixture without repository mapping.
		$comment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d", $lesson_id ) );
		update_comment_meta( $comment_id, 'questions_asked', '9' );
		update_comment_meta( $comment_id, 'quiz_answers', $comments['answers'] );
		update_comment_meta( $comment_id, 'quiz_grades', $comments['grades'] );
		$submission    = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, 456 );
		$answer_models = array();

		foreach ( $tables['answers'] as $question_id => $value ) {
			$answer_models[ $question_id ] = ( new Tables_Based_Answer_Repository( $wpdb ) )->create( $submission, $question_id, $value );
		}

		foreach ( $tables['grades'] as $question_id => $points ) {
			( new Tables_Based_Grade_Repository( $wpdb ) )->create( $submission, $answer_models[ $question_id ], $question_id, $points );
		}

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, 456, $source );

		$bundle = $preview['records'][0]['submission'];
		self::assertSame( 'ambiguous_missing', $bundle['status'], 'Child absence remains ambiguous when both submissions exist.' );
		self::assertSame(
			array(
				array(
					'kind'         => $kind,
					'question_id'  => 9,
					'missing_from' => $missing_from,
				),
			),
			$bundle['missing_children'],
			'Identify the question, child kind, and missing store.'
		);
		self::assertSame( $expected_changes, $bundle['changes'], 'Allow conditional copying of a surviving child, but never propose its removal from an absent source.' );
		self::assertSame( $expected_withheld_fields, $bundle['withheld_fields'] ?? array(), 'Withhold only collections whose replacement would remove a surviving child.' );
		self::assertStringContainsString( 'intended deletion', $bundle['review'], 'Explain the failed-copy versus intended-deletion ambiguity.' );
		self::assertSame(
			array(
				'Copy a surviving child only after confirming that it should exist in both stores.',
				'Remove a surviving child only after confirming an intended deletion; no removal is proposed.',
			),
			$bundle['alternatives'],
			'Explain copying and deletion as separate outcomes requiring confirmation.'
		);
	}

	/**
	 * Answers and grades may be missing from either store while both parents exist.
	 *
	 * @return array
	 */
	public static function missing_quiz_child_cases(): array {
		$empty    = array(
			'answers' => array(),
			'grades'  => array(),
		);
		$answered = array(
			'answers' => array( 9 => 'retained' ),
			'grades'  => array(),
		);
		$graded   = array(
			'answers' => array( 9 => 'retained' ),
			'grades'  => array( 9 => 2 ),
		);

		return array(
			'answer missing from comments' => array(
				'answers',
				'comments',
				array(
					'answers' => array( 10 => 'other saved answer' ),
					'grades'  => array(),
				),
				array(
					'answers' => array(
						9  => 'retained',
						10 => 'other saved answer',
					),
					'grades'  => array(),
				),
				'comments',
				array(),
				array( 'answers' ),
			),
			'answer missing from tables'   => array( 'answers', 'tables', $answered, $empty, 'tables', array(), array( 'answers' ) ),
			'grade missing from comments'  => array( 'grades', 'comments', $answered, $graded, 'comments', array(), array( 'grades' ) ),
			'grade missing from tables'    => array( 'grades', 'tables', $graded, $answered, 'tables', array(), array( 'grades' ) ),
			'surviving answer source'      => array(
				'answers',
				'comments',
				$answered,
				$empty,
				'tables',
				array(
					array(
						'if_confirmed' => 'replace',
						'from'         => 'comments',
						'to'           => 'tables',
						'field'        => 'answers',
						'before'       => array(),
						'after'        => array(
							array(
								'question_id' => 9,
								'value'       => 'retained',
							),
						),
					),
				),
				array(),
			),
			'surviving grade source'       => array(
				'grades',
				'tables',
				$answered,
				$graded,
				'comments',
				array(
					array(
						'if_confirmed' => 'replace',
						'from'         => 'tables',
						'to'           => 'comments',
						'field'        => 'grades',
						'before'       => array(),
						'after'        => array(
							array(
								'question_id' => 9,
								'points'      => 2,
								'feedback'    => '',
							),
						),
					),
				),
				array(),
			),
		);
	}

	/**
	 * Question order does not change answer identity or values.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_QuestionInsertionOrderDiffered_ProposesOnlyTheChangedGrade(): void {
		global $wpdb;
		$factory   = new \Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Locate the stored fixture without repository mapping.
		$comment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d", $lesson_id ) );
		update_comment_meta( $comment_id, 'questions_asked', '902,901' );
		update_comment_meta( $comment_id, 'grade', 80 );
		update_comment_meta(
			$comment_id,
			'quiz_answers',
			array(
				902 => 'b',
				901 => 'a',
			)
		);
		$submission = ( new Tables_Based_Submission_Repository( $wpdb ) )->create( $quiz_id, 456, 20 );
		$answers    = new Tables_Based_Answer_Repository( $wpdb );
		$answers->create( $submission, 901, 'a' );
		$answers->create( $submission, 902, 'b' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, 456, 'comments' );

		self::assertSame( array( 'final_grade' ), array_column( $preview['records'][0]['submission']['changes'], 'field' ), 'Question insertion order must not create answer differences.' );
	}

	/**
	 * Repository translation filters must not redirect the identity being inspected.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_LookupFilterWasInstalled_UsesTheStoredIdentity(): void {
		$lesson_id = ( new \Sensei_Factory() )->lesson->create();
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments' );
		$redirect = static function () {
			return 999999;
		};
		add_filter( 'sensei_lesson_progress_get_lesson_id', $redirect );

		try {
			$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'lesson', $lesson_id, 456 );
		} finally {
			remove_filter( 'sensei_lesson_progress_get_lesson_id', $redirect );
		}

		self::assertSame( $lesson_id, $preview['records'][0]['progress']['comments']['post_id'], 'Inspect the stored identity even when repository lookups would remap it.' );
	}

	/**
	 * Quiz visits do not require an independently representable comment copy.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_QuizWasOnlyVisited_ProposesNoCopy(): void {
		$quiz_id = ( new \Sensei_Factory() )->quiz->create();
		$this->create_progress( 'quiz', $quiz_id, 456, 'tables' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, 456, 'tables' );

		self::assertSame( 'equivalent', $preview['records'][0]['progress']['status'], 'Preserve the validator exception for quiz visits.' );
		self::assertSame( array(), $preview['records'][0]['progress']['changes'], 'Do not manufacture lesson comments from a quiz visit.' );
	}

	/**
	 * Shared lesson progress does not prove that its quiz was attempted.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_QuizWasNeverAttempted_ProposesNoQuizProgress(): void {
		$factory   = new \Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'quiz', $quiz_id, 456, 'comments' );

		self::assertSame( array(), $preview['records'][0]['progress']['changes'], 'Shared lesson progress must not invent a quiz attempt.' );
	}

	/**
	 * A lost lesson start must stay excluded even when status needs review.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_LessonStartWasOverwrittenByQuizStart_ExcludesDateFromRepair(): void {
		$factory   = new \Sensei_Factory();
		$lesson_id = $factory->lesson->create();
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments', 'passed', '2025-01-02 00:00:00' );
		$this->create_progress( 'lesson', $lesson_id, 456, 'tables', 'in-progress' );
		$this->create_progress( 'quiz', $quiz_id, 456, 'tables', 'in-progress', '2025-01-02 00:00:00' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'lesson', $lesson_id, 456, 'comments' );

		$progress = $preview['records'][0]['progress'];
		self::assertCount( 1, $progress['warnings'], 'Retain the unverifiable lesson start warning.' );
		self::assertNotContains( 'started_at', array_column( $progress['changes'], 'field' ), 'Do not repair an unverifiable lesson date.' );
		self::assertContains( 'status', array_column( $progress['changes'], 'field' ), 'Other differing values still need review.' );
		self::assertCount( 2, $preview['records'], 'Lesson scope includes its related quiz bundle.' );
	}

	/**
	 * Duplicate rows need review before any copy is selected.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_DuplicateCommentsWereStored_ReportsAmbiguity(): void {
		$this->create_progress( 'course', 123, 456, 'comments' );
		$this->create_progress( 'course', 123, 456, 'comments', 'complete' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', 123, 456, 'comments' );

		self::assertSame( 'ambiguous', $preview['records'][0]['status'], 'Do not silently select the newest duplicate.' );
		self::assertCount( 2, $preview['records'][0]['stored']['comments'], 'Show both stored IDs for review.' );
	}

	/**
	 * Conflicting start metadata must be reported before interpreting either date.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_DuplicateStartMetadataWasStored_ReportsAmbiguity(): void {
		global $wpdb;
		$this->create_progress( 'course', 123, 456, 'comments', 'in-progress', 'unparseable date' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Locate the stored fixture without repository mapping.
		$comment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d", 123 ) );
		add_comment_meta( $comment_id, 'start', '2025-01-01 00:00:00' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', 123, 456, 'comments' );

		self::assertSame( 'ambiguous', $preview['records'][0]['status'], 'Neither conflicting start value should be selected.' );
		self::assertCount( 2, $preview['records'][0]['stored']['comment_meta'], 'Retain both original values for inspection.' );
	}

	/**
	 * Course inspection stays bounded to course progress.
	 *
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_CourseScopeWasGiven_InspectsOnlyCourseProgress(): void {
		$factory   = new \Sensei_Factory();
		$course_id = $factory->course->create();
		$lesson_id = $factory->lesson->create(
			array(
				'post_status' => 'publish',
				'meta_input'  => array( '_lesson_course' => $course_id ),
			)
		);
		$quiz_id   = $factory->maybe_create_quiz_for_lesson( $lesson_id );
		$this->create_progress( 'lesson', $lesson_id, 456, 'comments' );
		$this->create_progress( 'lesson', $lesson_id, 456, 'tables' );
		$this->create_progress( 'quiz', $quiz_id, 456, 'tables' );

		$preview = ( new Sensei_Progress_Recovery_Preview() )->inspect( 'course', $course_id, 456 );

		self::assertCount( 1, $preview['records'], 'Do not scan the course lessons or quizzes.' );
		self::assertSame( 'absent', $preview['records'][0]['progress']['status'], 'Report no records rather than inventing a repair.' );
	}

	/**
	 * Invalid scope must be rejected before reading any identity.
	 *
	 * @dataProvider invalid_scope_cases
	 * @covers \Sensei_Progress_Recovery_Preview::inspect
	 */
	public function testInspect_InvalidScopeWasGiven_RejectsRequest( string $type, int $content_id, int $student_id, ?string $source, string $expected_message ): void {
		$preview = new Sensei_Progress_Recovery_Preview();
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( $expected_message );

		$preview->inspect( $type, $content_id, $student_id, $source );
	}

	/**
	 * Unsupported scopes have no defined repair semantics.
	 *
	 * @return array
	 */
	public static function invalid_scope_cases(): array {
		$scope_error = 'Provide course, lesson, or quiz and positive content and student IDs.';

		return array(
			'unknown type' => array( 'post', 123, 456, null, $scope_error ),
			'no content'   => array( 'lesson', 0, 456, null, $scope_error ),
			'no student'   => array( 'lesson', 123, 0, null, $scope_error ),
			'bad source'   => array( 'lesson', 123, 456, 'active', 'The hypothetical source must be comments or tables.' ),
		);
	}

	/**
	 * Store only the rows needed to reproduce progress drift.
	 *
	 * @param string $type       The progress type.
	 * @param int    $content_id The stored content ID.
	 * @param int    $student_id The student ID.
	 * @param string $store      The store to populate.
	 * @param string $status     The stored status.
	 * @param string $start      The UTC start date.
	 */
	private function create_progress( string $type, int $content_id, int $student_id, string $store, string $status = 'in-progress', string $start = '2025-01-01 00:00:00' ): void {
		global $wpdb;

		if ( 'comments' === $store ) {
			$comment_id = wp_insert_comment(
				array(
					'comment_post_ID'  => $content_id,
					'user_id'          => $student_id,
					'comment_type'     => 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status',
					'comment_approved' => $status,
					'comment_date'     => '2025-01-03 00:00:00',
					'comment_date_gmt' => '2025-01-03 00:00:00',
				)
			);
			update_comment_meta( $comment_id, 'start', $start );
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reproduce a write that reached only one store.
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'type'         => $type,
				'post_id'      => $content_id,
				'user_id'      => $student_id,
				'status'       => $status,
				'started_at'   => $start,
				'completed_at' => null,
				'created_at'   => '2025-01-01 00:00:00',
				'updated_at'   => '2025-01-01 00:00:00',
			)
		);
	}

	/**
	 * Snapshot persisted values without consulting WordPress caches.
	 *
	 * @return array All database rows, ordered deterministically.
	 */
	private function snapshot_database(): array {
		global $wpdb;

		$snapshot = array();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Compare actual persistence before and after preview.
		foreach ( $wpdb->get_col( 'SHOW TABLES' ) as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Read the complete stored state without cache effects.
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table ), ARRAY_A );
			sort( $rows );
			$snapshot[ $table ] = $rows;
		}

		return $snapshot;
	}
}

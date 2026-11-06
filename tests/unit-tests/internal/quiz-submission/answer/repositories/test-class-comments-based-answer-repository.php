<?php

namespace SenseiTest\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Persistence_Exception;
use Sensei\Internal\Quiz_Submission\Answer\Models\Comments_Based_Answer;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Models\Comments_Based_Submission;
use Sensei_Utils;

/**
 * Class Comments_Based_Answer_Repository_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository
 */
class Comments_Based_Answer_Repository_Test extends \WP_UnitTestCase {

	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new \Sensei_Factory();
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testCreate_WhenCalled_ReturnsAnswer(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$answer_repository = new Comments_Based_Answer_Repository();
		$submission        = $this->create_submission( $lesson_id, $user_id );

		/* Act. */
		$answer = $answer_repository->create( $submission, 1, 'Yes' );

		/* Assert. */
		$expected = [
			'question_id' => 1,
			'value'       => 'Yes',
		];

		$this->assertSame( $expected, $this->export_answer( $answer ) );
	}

	public function testCreate_WhenCalled_SavesTheAnswer(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		/* Act. */
		$answer_repository->create( $submission, 1, 'Yes' );
		$answer_repository->create( $submission, 2, 'No' );

		/* Assert. */
		$this->assertSame(
			[
				1 => 'Yes',
				2 => 'No',
			],
			get_comment_meta( $submission->get_id(), 'quiz_answers', true )
		);
	}

	public function testCreate_WhenCalled_SavesTheQuestionsAsked(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		/* Act. */
		$answer_repository->create( $submission, 1, 'Yes' );
		$answer_repository->create( $submission, 2, 'No' );

		/* Assert. */
		$this->assertSame(
			'1,2',
			get_comment_meta( $submission->get_id(), 'questions_asked', true )
		);
	}

	/**
	 * Metadata failures keep existing answers and allow the same answer to be saved later.
	 *
	 * @dataProvider answer_metadata_keys
	 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository::create
	 */
	public function testCreate_MetadataUpdateFailed_PreservesExistingAnswerAndAllowsLaterSave( string $key ): void {
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$submission = $this->create_submission( $lesson_id, $user_id );
		$repository = new Comments_Based_Answer_Repository();
		$repository->create( $submission, 1, 'First answer' );
		$wpdb  = $GLOBALS['wpdb'];
		$fault = static function ( $query ) use ( $wpdb, $key ) {
			if ( 0 === strpos( $query, "UPDATE `{$wpdb->commentmeta}`" ) && false !== strpos( $query, $key ) ) {
				return 'UPDATE sensei_missing_quiz_metadata SET meta_value = 1';
			}
			return $query;
		};
		$previous_errors = $wpdb->suppress_errors( true );
		add_filter( 'query', $fault );

		try {
			$repository->create( $submission, 2, 'Second answer' );
			self::fail( 'A failed metadata update must not return a saved answer.' );
		} catch ( Persistence_Exception $exception ) {
			self::assertSame( 'First answer', $repository->get_all( $submission->get_id() )[0]->get_value() );
		} finally {
			remove_filter( 'query', $fault );
			$wpdb->suppress_errors( $previous_errors );
		}
		$repository->create( $submission, 2, 'Second answer' );

		self::assertSame( array( 1 => 'First answer', 2 => 'Second answer' ), get_comment_meta( $submission->get_id(), 'quiz_answers', true ), 'A later save must retain both answers.' );
		self::assertSame( '1,2', get_comment_meta( $submission->get_id(), 'questions_asked', true ), 'A later save must finish the question list.' );
	}

	/**
	 * Required metadata for a comment-backed answer.
	 *
	 * @return array
	 */
	public static function answer_metadata_keys(): array {
		return array(
			'answers'   => array( 'quiz_answers' ),
			'questions' => array( 'questions_asked' ),
		);
	}

	/**
	 * A failed map read must not replace saved answers with an incomplete map.
	 *
	 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository::create
	 */
	public function testCreate_AnswerMapReadFailed_PreservesSavedAnswers(): void {
		$lesson_id  = $this->factory->lesson->create();
		$user_id    = $this->factory->user->create();
		$submission = $this->create_submission( $lesson_id, $user_id );
		$repository = new Comments_Based_Answer_Repository();
		$repository->create( $submission, 1, 'First answer' );
		wp_cache_delete( $submission->get_id(), 'comment_meta' );
		$wpdb  = $GLOBALS['wpdb'];
		$fault = static function ( $query ) use ( $wpdb ) {
			return false !== strpos( $query, "FROM {$wpdb->commentmeta}" )
				? 'SELECT * FROM sensei_missing_quiz_metadata'
				: $query;
		};
		$previous_errors = $wpdb->suppress_errors( true );
		add_filter( 'query', $fault );

		try {
			$repository->create( $submission, 2, 'Second answer' );
			self::fail( 'A failed map read must stop the write.' );
		} catch ( Persistence_Exception $exception ) {
			self::assertInstanceOf( Persistence_Exception::class, $exception );
		} finally {
			remove_filter( 'query', $fault );
			$wpdb->suppress_errors( $previous_errors );
		}

		self::assertSame( array( 1 => 'First answer' ), get_comment_meta( $submission->get_id(), 'quiz_answers', true ) );
	}

	public function testGetAll_WhenCalled_ReturnsAllAnswers(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		$answer_1 = $answer_repository->create( $submission, 1, 'Yes' );
		$answer_2 = $answer_repository->create( $submission, 2, 'No' );

		/* Act. */
		$answers = $answer_repository->get_all( $submission->get_id() );

		/* Assert. */
		$this->assertSame(
			[
				$this->export_answer( $answer_1 ),
				$this->export_answer( $answer_2 ),
			],
			array_map(
				[ $this, 'export_answer' ],
				$answers
			)
		);
	}

	private function create_submission( $lesson_id, $user_id ): Comments_Based_Submission {
		$submission_id = Sensei_Utils::sensei_start_lesson( $lesson_id, $user_id );
		return new Comments_Based_Submission(
			$submission_id,
			$lesson_id,
			$user_id,
			null,
			new \DateTimeImmutable(),
			new \DateTimeImmutable()
		);
	}

	public function testDeleteAll_WhenCalled_DeletesAllGrades(): void {
		/* Arrange. */
		$lesson_id         = $this->factory->lesson->create();
		$user_id           = $this->factory->user->create();
		$submission        = $this->create_submission( $lesson_id, $user_id );
		$answer_repository = new Comments_Based_Answer_Repository();

		$answer_repository->create( $submission, 1, 'Yes' );

		/* Act. */
		$answer_repository->delete_all( $submission );

		/* Assert. */
		$this->assertSame(
			[],
			$answer_repository->get_all( $submission->get_id() )
		);
	}

	private function export_answer( Comments_Based_Answer $answer ): array {
		return [
			'question_id' => $answer->get_question_id(),
			'value'       => $answer->get_value(),
		];
	}

}

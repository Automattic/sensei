<?php

use Sensei\Internal\Emails\Generators\Course_Welcome;

/**
 * Tests for Sensei_Update_Backfill_Course_Welcome_Email_Sent class.
 *
 * @group update-tasks
 * @group background-jobs
 */
class Sensei_Update_Backfill_Course_Welcome_Email_Sent_Test extends WP_UnitTestCase {
	use Sensei_HPPS_Helpers;

	/**
	 * Sensei Factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	public function testRunBatch_CourseProgressExists_MarksWelcomeEmailSentForEveryRelationship() {
		$this->maybe_enable_hpps_tables_repository();

		/* Arrange. */
		$relationships = $this->create_course_progress( 3 );

		/* Act. */
		$this->run_job_to_completion( 2 );

		/* Cleanup. */
		$this->maybe_reset_hpps_repository();

		/* Assert. */
		foreach ( $relationships as $relationship ) {
			$flag = get_user_meta( $relationship['user_id'], Course_Welcome::get_welcome_sent_meta_key( $relationship['course_id'] ), true );
			self::assertSame( Course_Welcome::WELCOME_ASSUMED, $flag, 'Every existing course progress relationship should be flagged with the sentinel.' );
		}
	}

	public function testRunBatch_FlagAlreadySet_DoesNotOverwriteExistingValue() {
		$this->maybe_enable_hpps_tables_repository();

		/* Arrange. */
		$relationships = $this->create_course_progress( 2 );
		$sentinel      = '2020-01-01 00:00:00';

		update_user_meta(
			$relationships[0]['user_id'],
			Course_Welcome::get_welcome_sent_meta_key( $relationships[0]['course_id'] ),
			$sentinel
		);

		/* Act. */
		$this->run_job_to_completion( 2 );

		/* Cleanup. */
		$this->maybe_reset_hpps_repository();

		/* Assert. */
		$preserved = get_user_meta( $relationships[0]['user_id'], Course_Welcome::get_welcome_sent_meta_key( $relationships[0]['course_id'] ), true );
		self::assertSame( $sentinel, $preserved, 'An already-set flag should not be overwritten by the backfill.' );

		$backfilled = get_user_meta( $relationships[1]['user_id'], Course_Welcome::get_welcome_sent_meta_key( $relationships[1]['course_id'] ), true );
		self::assertNotEmpty( $backfilled, 'A missing flag should be backfilled.' );
	}

	/**
	 * Create course progress for a set of distinct student/course pairs.
	 *
	 * @param int $count Number of relationships to create.
	 *
	 * @return array[] Array of [ 'user_id' => int, 'course_id' => int ] pairs.
	 */
	private function create_course_progress( int $count ): array {
		$relationships = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$user_id   = $this->factory->user->create();
			$course_id = $this->factory->course->create();

			Sensei()->course_progress_repository->create( $course_id, $user_id );

			$relationships[] = array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
			);
		}

		return $relationships;
	}

	/**
	 * Run the backfill job to completion using a small batch size so multiple
	 * batches are exercised.
	 *
	 * @param int $batch_size Batch size to use.
	 */
	private function run_job_to_completion( int $batch_size ): void {
		$id       = null;
		$complete = false;
		$guard    = 0;

		do {
			$job = $this->getMockBuilder( Sensei_Update_Backfill_Course_Welcome_Email_Sent::class )
				->setMethods( array( 'get_batch_size' ) )
				->setConstructorArgs( array( array(), $id ) )
				->getMock();
			$job->method( 'get_batch_size' )->willReturn( $batch_size );

			$job->run();
			$job->persist();

			$id       = $job->get_id();
			$complete = $job->is_complete();
		} while ( ! $complete && ++$guard < 100 );

		self::assertTrue( $complete, 'The backfill job should complete.' );
	}
}

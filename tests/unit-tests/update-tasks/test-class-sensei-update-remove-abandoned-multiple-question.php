<?php

/**
 * Tests for Sensei_Update_Remove_Abandoned_Multiple_Question class.
 *
 * @group update-tasks
 * @group background-jobs
 */
class Sensei_Update_Remove_Abandoned_Multiple_Question_Test extends WP_UnitTestCase {

	/**
	 * Sensei Factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
	}

	/**
	 * Tests a simple batch of quiz posts.
	 */
	public function testBasicRun() {
		$quiz_id               = $this->factory->quiz->create();
		$quiz_id_b             = $this->factory->quiz->create();
		$multiple_question_ids = $this->factory->multiple_question->create_many(
			4,
			[
				'quiz_id'              => $quiz_id,
				'question_number'      => 1,
				'question_category_id' => 0,
			]
		);

		$kept_question_id         = $multiple_question_ids[0];
		$deleted_question_id      = $multiple_question_ids[1];
		$cleared_question_id      = $multiple_question_ids[2];
		$shared_question_id       = $multiple_question_ids[3];
		$deleted_quiz_question_id = $this->factory->multiple_question->create();
		$abandoned_question_id    = $this->factory->multiple_question->create();

		delete_post_meta( $deleted_question_id, '_quiz_id', $quiz_id );

		// This also isn't done, but make sure we test for it in case it ever was.
		update_post_meta( $cleared_question_id, '_quiz_id', '', $quiz_id );

		// Note: This should never have happened, but just in case we did at one point.
		add_post_meta( $shared_question_id, '_quiz_id', $quiz_id_b, false );
		delete_post_meta( $shared_question_id, '_quiz_id', $quiz_id );

		add_post_meta( $deleted_quiz_question_id, '_quiz_id', 99999999, false );

		$instance = new Sensei_Update_Remove_Abandoned_Multiple_Question( [], '' );
		$instance->run();

		$this->assertTrue( 'multiple_question' === get_post_type( $kept_question_id ) );
		$this->assertTrue( 'multiple_question' === get_post_type( $shared_question_id ) );
		$this->assertNull( get_post( $deleted_question_id ) );
		$this->assertNull( get_post( $cleared_question_id ) );
		$this->assertNull( get_post( $abandoned_question_id ) );
		$this->assertNull( get_post( $deleted_quiz_question_id ) );
	}


	/**
	 * Get mock for Sensei_Update_Remove_Abandoned_Multiple_Question.
	 *
	 * @param array  $question_query_response Posts to return.
	 * @param int    $query_total_results     Total results.
	 * @param int    $batch_size              Batch size.
	 * @param string $id                      Job ID.
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|Sensei_Update_Fix_Question_Author
	 */
	private function getInstanceMock( $question_query_response = [], $query_total_results = 15, $batch_size = 10, $id = null ) {
		$mock = $this->getMockBuilder( Sensei_Update_Remove_Abandoned_Multiple_Question::class )
						->setMethods( [ 'get_multiple_question_query', 'get_batch_size' ] )
						->setConstructorArgs( [ [], $id ] )
						->getMock();

		$query              = new WP_Query();
		$query->found_posts = $query_total_results;
		$query->posts       = $question_query_response;
		$query->post_count  = count( $question_query_response );

		$mock->expects( $this->once() )->method( 'get_multiple_question_query' )->willReturn( $query );
		$mock->expects( $this->any() )->method( 'get_batch_size' )->willReturn( $batch_size );

		return $mock;
	}

	/**
	 * Get an array of WP_Post objects that haven't been inserted.
	 *
	 * @param int $n Number to return.
	 *
	 * @return WP_Post[]
	 */
	private function getFakeQuizzes( $n ) {
		$fake_posts = array_map(
			function( $id ) {
				$quiz            = new WP_Post( new stdClass() );
				$quiz->ID        = $id;
				$quiz->post_type = 'quiz';

				return $quiz;
			},
			range( 1, $n )
		);

		return $fake_posts;
	}
}

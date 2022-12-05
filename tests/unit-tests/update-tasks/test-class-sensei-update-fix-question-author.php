<?php

/**
 * Tests for Sensei_Update_Fix_Question_Author class.
 *
 * @group update-tasks
 * @group background-jobs
 */
class Sensei_Update_Fix_Question_Author_Test extends WP_UnitTestCase {
	/**
	 * Quiz mock.
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject|Sensei_Quiz
	 */
	private $quiz_mock;

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
		$this->factory   = new Sensei_Factory();
		$this->quiz_mock = Sensei()->quiz = $this->getMockBuilder( Sensei_Quiz::class )->setMethods( [ 'update_quiz_author' ] )->getMock();
	}

	/**
	 * Tests a simple batch of quiz posts.
	 */
	public function testSimpleBatch() {
		$batch_size   = 10;
		$total_count  = 11;
		$fake_quizzes = $this->getFakeQuizzes( $total_count );
		$this->quiz_mock->expects( $this->exactly( $total_count ) )->method( 'update_quiz_author' );

		$instance_a = $this->getInstanceMock( array_slice( $fake_quizzes, 0, $batch_size ), $total_count, $batch_size );

		$instance_a->run();
		$instance_a->persist();
		$this->assertFalse( $instance_a->is_complete() );

		$instance_b = $this->getInstanceMock( array_slice( $fake_quizzes, $batch_size, $batch_size ), $total_count, $batch_size, $instance_a->get_id() );

		$instance_b->run();
		$this->assertTrue( $instance_b->is_complete() );
	}

	/**
	 * Tests for when the batch size equals the number of quizzes
	 */
	public function testExactBatchSize() {
		$batch_size   = 10;
		$total_count  = 10;
		$fake_quizzes = $this->getFakeQuizzes( $total_count );
		$this->quiz_mock->expects( $this->exactly( $total_count ) )->method( 'update_quiz_author' );

		$instance = $this->getInstanceMock( array_slice( $fake_quizzes, 0, $batch_size ), $total_count, $batch_size );

		$instance->run();
		$this->assertTrue( $instance->is_complete() );
	}

	/**
	 * Tests to make sure the quiz query is pulling correctly.
	 */
	public function testQuizQuery() {
		$total_count = 11;

		$this->factory->post->create();
		$quiz_ids           = $this->factory->quiz->create_many( $total_count );
		$expected_arguments = [];
		foreach ( $quiz_ids as $quiz_id ) {
			$expected_arguments[] = [ $quiz_id, get_current_user_id() ];
		}

		$method_mock = $this->quiz_mock->expects( $this->exactly( $total_count ) )->method( 'update_quiz_author' );
		call_user_func_array( [ $method_mock, 'withConsecutive' ], $expected_arguments );

		$instance_a = new Sensei_Update_Fix_Question_Author();
		$instance_a->run();
		$instance_a->persist();

		$this->assertFalse( $instance_a->is_complete() );

		$instance_b = new Sensei_Update_Fix_Question_Author( [], $instance_a->get_id() );
		$instance_b->run();

		$this->assertTrue( $instance_b->is_complete() );
	}

	/**
	 * Get mock for question author.
	 *
	 * @param array  $quiz_query_response Posts to return.
	 * @param int    $query_total_results Total results.
	 * @param int    $batch_size          Batch size.
	 * @param string $id                  Job ID.
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|Sensei_Update_Fix_Question_Author
	 */
	private function getInstanceMock( $quiz_query_response = [], $query_total_results = 15, $batch_size = 10, $id = null ) {
		$mock = $this->getMockBuilder( Sensei_Update_Fix_Question_Author::class )
						->setMethods( [ 'get_quiz_query', 'get_batch_size' ] )
						->setConstructorArgs( [ [], $id ] )
						->getMock();

		$query              = new WP_Query();
		$query->found_posts = $query_total_results;
		$query->posts       = $quiz_query_response;
		$query->post_count  = count( $quiz_query_response );

		$mock->expects( $this->once() )->method( 'get_quiz_query' )->willReturn( $query );
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

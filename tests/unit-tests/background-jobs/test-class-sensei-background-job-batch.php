<?php

/**
 * Tests for Sensei_Background_Job_Batch class.
 *
 * @group background-jobs
 */
class Sensei_Background_Job_Batch_Test extends WP_UnitTestCase {
	/**
	 * Tests to make sure offset is incremented by batch size.
	 */
	public function testRun() {
		$instance = $this->getInstanceMock( [ 'run_batch' ], 10 );

		$instance->expects( $this->exactly( 3 ) )
			->method( 'run_batch' )
			->withConsecutive( [ 0 ], [ 10 ], [ 20 ] )
			->willReturnOnConsecutiveCalls( true, true, false );

		$instance->run();
		$this->assertFalse( $instance->is_complete() );

		$instance->run();
		$this->assertFalse( $instance->is_complete() );

		$instance->run();
		$this->assertTrue( $instance->is_complete() );

		$this->assertEquals( 20, $instance->get_state( Sensei_Background_Job_Batch::STATE_OFFSET ) );
	}

	/**
	 * Get mock for class.
	 *
	 * @param array $methods    Methods for mocking.
	 * @param int   $batch_size Batch size.
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|Sensei_Background_Job_Batch
	 */
	private function getInstanceMock( $methods = [], $batch_size = 10 ) {
		$methods[] = 'get_batch_size';
		$mock      = $this->getMockBuilder( Sensei_Background_Job_Batch::class )
						->setMethods( $methods )
						->getMockForAbstractClass();

		$mock->expects( $this->any() )->method( 'get_batch_size' )->willReturn( $batch_size );

		return $mock;
	}
}

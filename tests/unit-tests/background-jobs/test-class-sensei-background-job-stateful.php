<?php

/**
 * Tests for Sensei_Background_Job_Stateful class.
 *
 * @group background-jobs
 */
class Sensei_Background_Job_Stateful_Test extends WP_UnitTestCase {

	/**
	 * Simple test to make sure state is set and fetched in the object correctly.
	 *
	 * @covers \Sensei_Background_Job_Stateful::get_state
	 * @covers \Sensei_Background_Job_Stateful::set_state
	 */
	public function testSetGetState() {
		$instance = $this->getInstanceMock();
		$instance->set_state( 'test', 'value' );
		$this->assertEquals( 'value', $instance->get_state( 'test' ) );
	}

	/**
	 * Simple test to make sure state persists between calls.
	 *
	 * @covers \Sensei_Background_Job_Stateful::get_state
	 * @covers \Sensei_Background_Job_Stateful::set_state
	 * @covers \Sensei_Background_Job_Stateful::persist
	 * @covers \Sensei_Background_Job_Stateful::restore_state
	 */
	public function testStatePersists() {
		$id         = 'state-test';
		$instance_a = $this->getInstanceMock( $id );
		$instance_a->set_state( 'test', 'value' );
		$instance_a->persist();

		$instance_b = $this->getInstanceMock( $id );
		$this->assertEquals( 'value', $instance_b->get_state( 'test' ) );
	}

	/**
	 * Make sure state does not persist after cleanup.
	 *
	 * @covers \Sensei_Background_Job_Stateful::get_state
	 * @covers \Sensei_Background_Job_Stateful::set_state
	 * @covers \Sensei_Background_Job_Stateful::persist
	 * @covers \Sensei_Background_Job_Stateful::restore_state
	 * @covers \Sensei_Background_Job_Stateful::cleanup
	 */
	public function testStateDoesNotPersistAfterCleanup() {
		$id         = 'state-test';
		$instance_a = $this->getInstanceMock( $id );
		$instance_a->set_state( 'test', 'value' );
		$instance_a->cleanup();
		$instance_a->persist();

		$instance_b = $this->getInstanceMock( $id );
		$this->assertEquals( null, $instance_b->get_state( 'test' ) );
	}

	/**
	 * Make sure state is not shared when arguments change.
	 *
	 * @covers \Sensei_Background_Job_Stateful::get_state_transient_name
	 * @covers \Sensei_Background_Job_Stateful::restore_state
	 */
	public function testStateNotSharedWithDifferentArguments() {
		$id         = 'state-test';
		$instance_a = $this->getInstanceMock( $id, [ 'dinosaurs' => true ] );
		$instance_a->set_state( 'test', 'value' );
		$instance_a->persist();

		$instance_b = $this->getInstanceMock( $id, [ 'dinosaurs' => false ] );
		$this->assertEquals( null, $instance_b->get_state( 'test' ) );
	}

	/**
	 * Get the instance of the test object.
	 *
	 * @param string $id                       The job ID.
	 * @param array  $args                     The job args.
	 * @param array  $methods                  The methods to mock.
	 * @param false  $allow_multiple_instances If multiple instances are allowed of this job.
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject|Sensei_Background_Job_Stateful
	 */
	private function getInstanceMock( $id = null, $args = [], $methods = [], $allow_multiple_instances = false ) {
		$methods[] = 'allow_multiple_instances';

		$mock = $this->getMockBuilder( Sensei_Background_Job_Stateful::class )
						->setConstructorArgs( [ $args, $id ] )
						->setMethods( $methods )
						->getMockForAbstractClass();

		$mock->expects( $this->any() )->method( 'allow_multiple_instances' )->willReturn( $allow_multiple_instances );

		return $mock;
	}
}

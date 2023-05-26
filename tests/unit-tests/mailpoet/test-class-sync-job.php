<?php
/**
 * File with class for testing Sensei MailPoet integration.
 *
 * @package sensei-tests
 */

/**
 * Class for testing Sensei\Emails\MailPoet\Sync_Job class.
 *
 * @group Sensei MailPoet
 */
class Sync_Job_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Manual_Test_Helpers;
	/**
	 * Factory object.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		$mailpoet_api  = Sensei_MailPoet_API_Factory::MP();
		new Sensei\Emails\MailPoet\Main( $mailpoet_api );
	}

	/**
	 * Tests to make sure offset is incremented by batch size.
	 */
	public function testRun() {
		$instance = $this->getInstanceMock( array( 'run_batch' ), 10 );

		$instance->expects( $this->exactly( 3 ) )->method( 'run_batch' )->withConsecutive( array( 0 ), array( 10 ), array( 20 ) )->willReturnOnConsecutiveCalls( true, true, false );

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
	private function getInstanceMock( $methods = array(), $batch_size = 10 ) {
		$methods[] = 'get_batch_size';
		$mock      = $this->getMockBuilder( Sensei\Emails\MailPoet\Sync_Job::class )->setMethods( $methods )->getMockForAbstractClass();

		$mock->expects( $this->any() )->method( 'get_batch_size' )->willReturn( $batch_size );

		return $mock;
	}
}

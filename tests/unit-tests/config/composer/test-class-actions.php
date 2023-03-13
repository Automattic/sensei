<?php

namespace SenseiTest\Config\Composer;

use Sensei\Config\Composer\Actions;

/**
 * Tests for the Actions class.
 *
 * @covers \Sensei\Config\Composer\Actions
 */
class Actions_Test extends \WP_UnitTestCase {
	public function testPrefixDependencies_WhenCalledInDevMode_RunsScript() {
		/* Arrange. */
		$event = $this->getEventMock();
		$event->method( 'isDevMode' )
			->willReturn( true );

		$event_dispatcher = $event->getComposer()->getEventDispatcher();

		/* Assert. */
		$event_dispatcher
			->expects( $this->once() )
			->method( 'dispatchScript' )
			->with( 'prefix-dependencies', true );

		/* Act. */
		Actions::prefix_dependencies( $event );
	}

	public function testPrefixDependencies_WhenCalledNotInDevMode_DoesntRunScript() {
		/* Arrange. */
		$event = $this->getEventMock();
		$event->method( 'isDevMode' )
			->willReturn( false );

		$event_dispatcher = $event->getComposer()->getEventDispatcher();

		/* Assert. */
		$event_dispatcher
			->expects( $this->never() )
			->method( 'dispatchScript' );

		/* Act. */
		Actions::prefix_dependencies( $event );
	}

	private function getEventMock() {
		$event_dispatcher = $this->getMockBuilder( 'Composer\EventDispatcher\EventDispatcher' )
			->setMethods( [ 'dispatchScript' ] )
			->getMock();

		$composer = $this->getMockBuilder( 'Composer\Composer' )
			->setMethods( [ 'getEventDispatcher' ] )
			->getMock();
		$composer->method( 'getEventDispatcher' )
			->willReturn( $event_dispatcher );

		$io = $this->getMockBuilder( 'Composer\IO\IOInterface' )
			->setMethods( [ 'write' ] )
			->getMock();

		$event = $this->getMockBuilder( 'Composer\Script\Event' )
			->setMethods( [ 'getComposer', 'getIO', 'isDevMode' ] )
			->getMock();
		$event->method( 'getComposer' )
			->willReturn( $composer );
		$event->method( 'getIO' )
			->willReturn( $io );

		return $event;
	}
}

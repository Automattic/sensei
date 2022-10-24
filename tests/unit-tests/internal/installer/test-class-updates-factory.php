<?php

namespace SenseiTest\Internal\Installer;

use Sensei\Internal\Installer\Updates_Factory;
use Sensei_Updates;

/**
 * Tests for Sensei\Internal\Installer\Updates_Factory.
 *
 * @covers \Sensei\Internal\Installer\Updates_Factory
 */
class Updates_Factory_Test extends \WP_UnitTestCase {
	public function testCreate_WhenCalled_ReturnsSenseiUpdates(): void {
		/* Arrange */
		$factory = new Updates_Factory();

		/* Act. */
		$updates = $factory->create( '1.2.3', SENSEI_LMS_VERSION, true );

		/* Assert. */
		$this->assertInstanceOf( Sensei_Updates::class, $updates );
	}
}

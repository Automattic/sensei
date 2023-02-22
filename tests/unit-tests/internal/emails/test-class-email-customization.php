<?php
namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Customization;
use Sensei\Internal\Emails\Email_Post_Type;
use Sensei_Settings;

/**
 * Tests for the Email_Customization class.
 *
 * @covers \Sensei\Internal\Emails\Email_Customization
 */
class Email_Customization_Test extends \WP_UnitTestCase {
	public function testInstance_WhenCalled_ReturnsInstance() {
		/* Arrange. */
		$settings = $this->createMock( Sensei_Settings::class );

		/* Act. */
		$result = Email_Customization::instance( $settings );

		/* Assert. */
		$this->assertInstanceOf( Email_Customization::class, $result );
	}
}

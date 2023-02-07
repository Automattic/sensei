<?php
namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Customization;
use Sensei\Internal\Emails\Email_Post_Type;

/**
 * Tests for the Email_Customization class.
 *
 * @covers \Sensei\Internal\Emails\Email_Customization
 */
class Email_Customization_Test extends \WP_UnitTestCase {
	public function testInstance_WhenCalled_ReturnsInstance() {
		/* Act. */
		$result = Email_Customization::instance();

		/* Assert. */
		$this->assertInstanceOf( Email_Customization::class, $result );
	}
}

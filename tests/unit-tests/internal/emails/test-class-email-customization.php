<?php
namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Sensei_Email_Customization;
use Sensei\Internal\Emails\Sensei_Email_Post_Type;

/**
 * Tests for the Sensei_Email_Customization class.
 *
 * @covers \Sensei\Internal\Emails\Sensei_Email_Customization
 */
class Sensei_Email_Customization_Test extends \WP_UnitTestCase {
	public function testInstance_WhenCalled_ReturnsInstance() {
		/* Act. */
		$result = Sensei_Email_Customization::instance();

		/* Assert. */
		$this->assertInstanceOf( Sensei_Email_Customization::class, $result );
	}
}

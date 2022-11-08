<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory;

/**
 * Class Submission_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory
 */
class Submission_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsSubmissionRepository(): void {
		/* Arrange. */
		$factory = new Submission_Repository_Factory();

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Submission_Repository_Interface::class, $actual );
	}

}

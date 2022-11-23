<?php

namespace SenseiTest\Internal\Quiz_Submission\Grade\Repositories;

use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory;

/**
 * Class Grade_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory
 */
class Grade_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsGradeRepository(): void {
		/* Arrange. */
		$factory = new Grade_Repository_Factory();

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Grade_Repository_Interface::class, $actual );
	}

}

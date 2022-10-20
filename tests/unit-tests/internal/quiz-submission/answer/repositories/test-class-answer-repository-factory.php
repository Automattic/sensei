<?php

namespace SenseiTest\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Interface;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory;

/**
 * Class Answer_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory
 */
class Answer_Repository_Factory_Test extends \WP_UnitTestCase {

	public function testCreate_WhenCalled_ReturnsAnswerRepository(): void {
		/* Arrange. */
		$factory = new Answer_Repository_Factory();

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Answer_Repository_Interface::class, $actual );
	}

}

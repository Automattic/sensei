<?php

namespace SenseiTest\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Aggregate_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory;

/**
 * Class Answer_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory
 */
class Answer_Repository_Factory_Test extends \WP_UnitTestCase {

	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsAnswerRepository
	 */
	public function testCreate_WhenCalled_ReturnsAnswerRepository( bool $use_tables ): void {
		/* Arrange. */
		$factory = new Answer_Repository_Factory( $use_tables );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Aggregate_Answer_Repository::class, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsAnswerRepository(): array {
		return [
			'use tables'        => [ true ],
			'do not use tables' => [ false ],
		];
	}
}

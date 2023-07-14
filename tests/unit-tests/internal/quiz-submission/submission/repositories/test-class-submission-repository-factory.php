<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Submission\Repositories\Aggregate_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory;

/**
 * Class Submission_Repository_Factory_Test
 *
 * @covers \Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory
 */
class Submission_Repository_Factory_Test extends \WP_UnitTestCase {

	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsSubmissionRepository
	 */
	public function testCreate_WhenCalled_ReturnsSubmissionRepository( bool $use_tables ): void {
		/* Arrange. */
		$factory = new Submission_Repository_Factory( $use_tables );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( Aggregate_Submission_Repository::class, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsSubmissionRepository(): array {
		return [
			'use tables'        => [ true ],
			'do not use tables' => [ false ],
		];
	}
}

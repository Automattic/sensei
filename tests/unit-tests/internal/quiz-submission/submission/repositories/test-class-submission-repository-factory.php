<?php

namespace SenseiTest\Internal\Quiz_Submission\Submission\Repositories;

use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comment_Reading_Aggregate_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Submission\Repositories\Table_Reading_Aggregate_Submission_Repository;

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
	public function testCreate_WhenCalled_ReturnsSubmissionRepository( bool $tables_enabled, bool $read_tables, string $expected ): void {
		/* Arrange. */
		$factory = new Submission_Repository_Factory( $tables_enabled, $read_tables );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( $expected, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsSubmissionRepository(): array {
		return [
			'tables disabled, don\'t read tables' => [ false, false, Comments_Based_Submission_Repository::class ],
			'tables disabled, read tables'        => [ false, true, Comments_Based_Submission_Repository::class ],
			'tables enabled, don\'t read tables'  => [ true, false, Comment_Reading_Aggregate_Submission_Repository::class ],
			'tables enabled, read tables'         => [ true, true, Table_Reading_Aggregate_Submission_Repository::class ],
		];
	}
}

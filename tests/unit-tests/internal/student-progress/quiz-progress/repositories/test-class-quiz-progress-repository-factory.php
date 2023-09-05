<?php

namespace SenseiTest\Internal\Student_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Aggregate_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository;

/**
 * Tests for the Quiz_Progress_Repository_Factory class.
 *
 * @covers \Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory
 */
class Quiz_Progress_Repository_Factory_Test extends \WP_UnitTestCase {

	/**
	 * Tests that the factory creates the correct repository.
	 *
	 * @dataProvider providerCreate_WhenCalled_ReturnsQuizProgressRepository
	 */
	public function testCreate_WhenCalled_ReturnsQuizProgressRepository( bool $use_tables ): void {
		/* Arrange. */
		$factory = new Quiz_Progress_Repository_Factory( $use_tables );

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( Aggregate_Quiz_Progress_Repository::class, $actual_repository );
	}

	public function providerCreate_WhenCalled_ReturnsQuizProgressRepository(): array {
		return [
			'use tables'        => [ true ],
			'do not use tables' => [ false ],
		];
	}

	public function testCreateTablesBasedRepository_Always_ReturnsTablesBasedRepository(): void {
		/* Arrange. */
		$factory = new Quiz_Progress_Repository_Factory( true );

		/* Act. */
		$actual_repository = $factory->create_tables_based_repository();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Quiz_Progress_Repository::class, $actual_repository );
	}
}

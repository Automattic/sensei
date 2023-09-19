<?php

namespace SenseiTest\Internal\Student_Progress\Repositories;

use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comment_Reading_Aggregate_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory;
use Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Table_Reading_Aggregate_Quiz_Progress_Repository;
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
	public function testCreate_WhenCalled_ReturnsQuizProgressRepository( bool $tables_enabled, bool $read_tables, string $expected ): void {
		/* Arrange. */
		$factory = new Quiz_Progress_Repository_Factory( $tables_enabled, $read_tables );

		/* Act. */
		$actual_repository = $factory->create();

		/* Assert. */
		$this->assertInstanceOf( $expected, $actual_repository );
	}

	public function providerCreate_WhenCalled_ReturnsQuizProgressRepository(): array {
		return array(
			'tables enabled, readig disabled'   => array(
				true,
				false,
				Comment_Reading_Aggregate_Quiz_Progress_Repository::class,
			),
			'tables enabled, reading enabled'   => array(
				true,
				true,
				Table_Reading_Aggregate_Quiz_Progress_Repository::class,
			),
			'tables disabled, reading disabled' => array(
				false,
				false,
				Comments_Based_Quiz_Progress_Repository::class,
			),
			'tables disabled, reading enabled'  => array(
				false,
				true,
				Comments_Based_Quiz_Progress_Repository::class,
			),
		);
	}

	public function testCreateTablesBasedRepository_Always_ReturnsTablesBasedRepository(): void {
		/* Arrange. */
		$factory = new Quiz_Progress_Repository_Factory( true, true );

		/* Act. */
		$actual_repository = $factory->create_tables_based_repository();

		/* Assert. */
		$this->assertInstanceOf( Tables_Based_Quiz_Progress_Repository::class, $actual_repository );
	}
}

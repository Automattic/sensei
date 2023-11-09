<?php

namespace SenseiTest\Internal\Quiz_Submission\Answer\Repositories;

use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comment_Reading_Aggregate_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository;
use Sensei\Internal\Quiz_Submission\Answer\Repositories\Table_Reading_Aggregate_Answer_Repository;

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
	public function testCreate_WhenCalled_ReturnsAnswerRepository( bool $tables_enabled, bool $read_tables, string $expected ): void {
		/* Arrange. */
		$factory = new Answer_Repository_Factory( $tables_enabled, $read_tables );

		/* Act. */
		$actual = $factory->create();

		/* Assert. */
		self::assertInstanceOf( $expected, $actual );
	}

	public function providerCreate_WhenCalled_ReturnsAnswerRepository(): array {
		return array(
			'tables disabled, don\'t read tables' => array( false, false, Comments_Based_Answer_Repository::class ),
			'tables disabled, read tables'        => array( false, true, Comments_Based_Answer_Repository::class ),
			'tables enabled, don\'t read tables'  => array( true, false, Comment_Reading_Aggregate_Answer_Repository::class ),
			'tables enabled, read tables'         => array( true, true, Table_Reading_Aggregate_Answer_Repository::class ),
		);
	}
}

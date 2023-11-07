<?php

namespace SenseiTest\Internal\Migration;

use Sensei\Internal\Migration\Migrations\Student_Progress_Migration;
use Sensei\Internal\Migration\Migration_Job;

/**
 * Class Migration_Job_Test
 *
 * @covers \Sensei\Internal\Migration\Migration_Job
*/
class Migration_Job_Test extends \WP_UnitTestCase {
	public function testRun_Always_RunsMigration() {
		/* Arrange. */
		$migration = $this->createMock( Student_Progress_Migration::class );
		$job       = new Migration_Job( 'student_progress_migration', $migration );

		/* Expect & Act. */
		$migration
			->expects( $this->once() )
			->method( 'run' )
			->with( false )
			->willReturn( 1 );
		$job->run();
	}

	public function testIsComplete_MigrationRanWithoutInsertions_ReturnsTrue() {
		/* Arrange. */
		$migration = $this->createMock( Student_Progress_Migration::class );
		$migration->method( 'run' )->willReturn( 0 );

		$job = new Migration_Job( 'student_progress_migration', $migration );
		$job->run();

		/* Act. */
		$actual = $job->is_complete();

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testIsComplete_MigrationRanWithInsertions_ReturnsFalse() {
		/* Arrange. */
		$migration = $this->createMock( Student_Progress_Migration::class );
		$migration->method( 'run' )->willReturn( 1 );

		$job = new Migration_Job( 'student_progress_migration', $migration );
		$job->run();

		/* Act. */
		$actual = $job->is_complete();

		/* Assert. */
		$this->assertFalse( $actual );
	}

	/**
	 * Test get_errors returns the errors from the migration.
	 *
	 * @dataProvider providerGetErrors_Always_ReturnsMigrationErrors
	 */
	public function testGetErrors_Always_ReturnsMigrationErrors( $errors ) {
		/* Arrange. */
		$migration = $this->createMock( Student_Progress_Migration::class );
		$migration->method( 'get_errors' )->willReturn( $errors );

		$job = new Migration_Job( 'student_progress_migration', $migration );

		/* Act. */
		$actual = $job->get_errors();

		/* Assert. */
		$this->assertEquals( $errors, $actual );
	}

	public function providerGetErrors_Always_ReturnsMigrationErrors(): array {
		return array(
			'no errors'  => array(
				array(),
			),
			'has errors' => array(
				array( 'error' ),
			),
		);
	}

	public function testGetName_Always_ReturnsMatchingValue() {
		/* Arrange. */
		$migration = new Student_Progress_Migration();
		$job       = new Migration_Job( 'student_progress_migration', $migration );

		/* Act. */
		$actual = $job->get_name();

		/* Assert. */
		$this->assertSame( 'student_progress_migration', $actual );
	}
}

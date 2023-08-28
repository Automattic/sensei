<?php

namespace SenseiTest\Internal\Student_Progress\Jobs;

use Sensei\Internal\Installer\Migrations\Student_Progress_Migration;
use Sensei\Internal\Student_Progress\Jobs\Migration_Job;

/**
 * Class Migration_Job_Test
 *
 * @covers \Sensei\Internal\Student_Progress\Jobs\Migration_Job
*/
class Migration_Job_Test extends \WP_UnitTestCase {
	public function testRun_Always_RunsMigration() {
		/* Arrange. */
		$migration = $this->createMock( Student_Progress_Migration::class );
		$job       = new Migration_Job( $migration );

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

		$job = new Migration_Job( $migration );
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

		$job = new Migration_Job( $migration );
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

		$job = new Migration_Job( $migration );

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

	public function testGetJobName_Always_ReturnsMatchingValue() {
		/* Arrange. */
		$migration = $this->createMock( Student_Progress_Migration::class );
		$job       = new Migration_Job( $migration );

		/* Act. */
		$actual = $job->get_job_name();

		/* Assert. */
		$this->assertEquals( 'progress_migration', $actual );
	}
}

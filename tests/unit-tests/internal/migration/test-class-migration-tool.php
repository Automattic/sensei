<?php
namespace SenseiTest\Internal\Migration;

use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Migration\Migration_Tool;
use Sensei_Tools;

/**
 * Class Migration_Tool_Test
 *
 * @covers \Sensei\Internal\Migration\Migration_Tool
 */
class Migration_Tool_Test extends \WP_UnitTestCase {
	public function testInit_Always_AddsFilter(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$before_init = has_filter( 'sensei_tools', [ $tool, 'register_tool' ] );
		$tool->init();
		$after_init = 10 === has_filter( 'sensei_tools', [ $tool, 'register_tool' ] );

		$actual = array(
			'before' => $before_init,
			'after'  => $after_init,
		);

		/* Assert. */
		$expected = array(
			'before' => false,
			'after'  => true,
		);
		$this->assertSame( $expected, $actual );
	}

	public function testRegisterTool_Always_AddsItselfToTools(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->register_tool( array() );

		/* Assert. */
		$expected = array( $tool );
		$this->assertSame( $expected, $actual );
	}

	public function testGetId_Always_ReturnsMatchingValue(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->get_id();

		/* Assert. */
		$this->assertSame( 'student-progress-migration', $actual );
	}

	public function testGetName_Always_ReturnsMatchingValue(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->get_name();

		/* Assert. */
		$this->assertSame( 'Migrate comment-based student progress', $actual );
	}

	public function testGetDescription_WhenMigrationNotStarted_ReturnsStatusNone(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->get_description();

		/* Assert. */
		$this->assertStringContainsString( 'Status: None', $actual );
	}

	public function testGetDescription_WhenMigrationStarted_ReturnsStatusInProgress(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		update_option( Migration_Job_Scheduler::STARTED_OPTION_NAME, time() );

		/* Act. */
		$actual = $tool->get_description();

		/* Assert. */
		$this->assertStringContainsString( 'Status: In progress', $actual );
	}

	public function testGetDescription_WhenMigrationComplete_ReturnsStatusCompleted(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		update_option( Migration_Job_Scheduler::STARTED_OPTION_NAME, time() );
		update_option( Migration_Job_Scheduler::COMPLETED_OPTION_NAME, time() + 1 );

		/* Act. */
		$actual = $tool->get_description();

		/* Assert. */
		$this->assertStringContainsString( 'Status: Completed', $actual );
	}

	public function testGetDescription_WhenHasErrors_ReturnsErrors(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		update_option( Migration_Job_Scheduler::ERRORS_OPTION_NAME, [ 'error 1', 'error 2' ] );

		/* Act. */
		$actual = $tool->get_description();

		/* Assert. */
		$this->assertStringContainsString( 'Errors: error 1, error 2', $actual );
	}

	public function testProcess_Always_SchedulesTheMigrationJob(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Assert. */
		$scheduler->expects( $this->once() )
			->method( 'schedule' );

		/* Act. */
		$tool->process();
	}

	public function testProcess_Always_AddsUserMessage(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Assert. */
		$tools->expects( $this->once() )
			->method( 'add_user_message' )
			->with( 'Migration scheduled.' );

		/* Act. */
		$tool->process();
	}

	public function testIsAvailable_Always_ReturnsTrue(): void {
		/* Arrange. */
		$tools     = $this->createMock( Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->is_available();

		/* Assert. */
		$this->assertTrue( $actual );
	}
}

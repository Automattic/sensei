<?php
namespace SenseiTest\Internal\Student_Progress\Tools;

use Sensei\Internal\Student_Progress\Jobs\Migration_Job_Scheduler;
use Sensei\Internal\Student_Progress\Tools\Migration_Tool;

/**
 * Class Migration_Tool_Test
 *
 * @covers \SenseiTest\Internal\Student_Progress\Tools\Migration_Tool
 */
class Migration_Tool_Test extends \WP_UnitTestCase {
	public function testGetId_Always_ReturnsMatchingValue(): void {
		/* Arrange. */
		$tools     = $this->createMock( \Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->get_id();

		/* Assert. */
		$this->assertSame( 'student-progress-migration', $actual );
	}

	public function testInit_Always_AddsFilter(): void {
		/* Arrange. */
		$tools     = $this->createMock( \Sensei_Tools::class );
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
		$tools     = $this->createMock( \Sensei_Tools::class );
		$scheduler = $this->createMock( Migration_Job_Scheduler::class );
		$tool      = new Migration_Tool( $tools, $scheduler );

		/* Act. */
		$actual = $tool->register_tool( array() );

		/* Assert. */
		$expected = array( $tool );
		$this->assertSame( $expected, $actual );
	}
}

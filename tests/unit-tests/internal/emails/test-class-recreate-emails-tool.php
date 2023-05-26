<?php

namespace SenseiTest\Internal\Emails;

use Sensei\Internal\Emails\Email_Seeder;
use Sensei\Internal\Emails\Recreate_Emails_Tool;

/**
 * Tests for Sensei\Internal\Emails\Recreate_Emails_Tool class.
 *
 * @covers \Sensei\Internal\Emails\Recreate_Emails_Tool
 */
class Recreate_Emails_Tool_Test extends \WP_UnitTestCase {
	public function testInit_WhenCalled_AddsFilter(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Act. */
		$tool->init();

		/* Assert. */
		self::assertSame( 10, has_filter( 'sensei_tools', [ $tool, 'register_tool' ] ) );
	}

	public function testRegisterTool_ArrayGiven_ReturnsArrayContainingTool(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Act. */
		$result = $tool->register_tool( [] );

		/* Assert. */
		self::assertSame( [ $tool ], $result );
	}

	public function testGetId_Always_ReturnsExpectedId(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Act. */
		$result = $tool->get_id();

		/* Assert. */
		self::assertSame( 'recreate-emails', $result );
	}

	public function testGetName_Always_ReturnsExpectedName(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Act. */
		$result = $tool->get_name();

		/* Assert. */
		self::assertSame( 'Recreate Emails', $result );
	}

	public function testGetDescription_Always_ReturnsExpectedDescription(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Act. */
		$result = $tool->get_description();

		/* Assert. */
		$expected = 'Recreate all emails. Existing customizations will be lost.';
		self::assertSame( $expected, $result );
	}

	public function testIsAvailable_Always_ReturnsTrue(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Act. */
		$result = $tool->is_available();

		/* Assert. */
		self::assertTrue( $result );
	}

	public function testProcess_Always_CallsSeeder(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$tools  = $this->createMock( \Sensei_Tools::class );
		$tool   = new Recreate_Emails_Tool( $seeder, $tools );

		/* Expect & Act. */
		$seeder
			->expects( $this->once() )
			->method( 'create_all' );
		$tool->process();
	}

	public function testProcess_CreatedWithoutIssues_AddsMatchingMessage(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$seeder->method( 'create_all' )->willReturn( true );
		$tools = $this->createMock( \Sensei_Tools::class );
		$tool  = new Recreate_Emails_Tool( $seeder, $tools );

		/* Expect & Act. */
		$tools
			->expects( $this->once() )
			->method( 'add_user_message' )
			->with( 'Emails were recreated successfully.' );
		$tool->process();
	}

	public function testProcess_CreatedWithIssues_AddsMatchingMessage(): void {
		/* Arrange. */
		$seeder = $this->createMock( Email_Seeder::class );
		$seeder->method( 'create_all' )->willReturn( false );
		$tools = $this->createMock( \Sensei_Tools::class );
		$tool  = new Recreate_Emails_Tool( $seeder, $tools );

		/* Expect & Act. */
		$tools
			->expects( $this->once() )
			->method( 'add_user_message' )
			->with( 'There were errors while recreating emails.' );
		$tool->process();
	}
}


<?php

namespace SenseiTest\Internal\Installer;

use Sensei\Internal\Installer\Installer;
use Sensei\Internal\Installer\Schema;
use Sensei\Internal\Installer\Updates_Factory;
use Sensei_Factory;
use Sensei_Utils;

/**
 * Test for \Sensei\Internal\Installer\Installer.
 *
 * @covers \Sensei\Internal\Installer\Installer
 */
class Installer_Test extends \WP_UnitTestCase {
	/**
	 * The Installer instance.
	 *
	 * @var Installer
	 */
	protected $installer;

	/**
	 * Sensei Factory.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * Test specific setup.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->factory   = new Sensei_Factory();
		$this->installer = Installer::instance( SENSEI_LMS_VERSION );

		$this->reset_installer_state();
	}

	public function testInit_WhenCalled_RegistersHooks(): void {
		/* Arrange. */
		$schema    = $this->createMock( Schema::class );
		$installer = new Installer( $schema, new Updates_Factory(), SENSEI_LMS_VERSION );

		/* Act. */
		$installer->init();

		/* Assert. */
		$this->assertSame( 10, has_action( 'plugins_loaded', [ $installer, 'install' ] ) );
		$this->assertSame( 10, has_action( 'init', [ $installer, 'update' ] ) );
	}

	public function testInstall_WhenAlreadyInstalling_ShouldNotRun(): void {
		/* Arrange. */
		set_transient( 'sensei_lms_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		$install_runs = did_action( 'sensei_lms_installed' );

		/* Act. */
		$this->installer->install();

		/* Assert. */
		$this->assertSame( $install_runs, did_action( 'sensei_lms_installed' ) );
	}

	public function testInstall_WhenNotInstalling_ShouldRun(): void {
		/* Arrange. */
		$install_runs = did_action( 'sensei_lms_installed' );

		/* Act. */
		$this->installer->install();

		/* Assert. */
		$this->assertSame( $install_runs + 1, did_action( 'sensei_lms_installed' ) );
	}

	public function testInstall_WhenTheVersionIsUpToDate_ShouldNotRun(): void {
		/* Arrange. */
		update_option( 'sensei-version', SENSEI_LMS_VERSION );

		$install_runs = did_action( 'sensei_lms_installed' );

		/* Act. */
		$this->installer->install();

		/* Assert. */
		$this->assertSame( $install_runs, did_action( 'sensei_lms_installed' ) );
	}

	public function testInstall_WhenTheVersionIsNotUpToDate_ShouldRun(): void {
		/* Arrange. */
		update_option( 'sensei-version', '0.0.1' );

		$install_runs = did_action( 'sensei_lms_installed' );

		/* Act. */
		$this->installer->install();

		/* Assert. */
		$this->assertSame( $install_runs + 1, did_action( 'sensei_lms_installed' ) );
	}

	public function testInstall_WhenCalled_ShouldNotUpdateTheVersion(): void {
		/* Arrange. */
		update_option( 'sensei-version', '0.0.1' );
		/* Act. */
		$this->installer->install();

		/* Assert. */
		$version = get_option( 'sensei-version' );
		$this->assertSame( '0.0.1', $version );
	}

	public function testGetSchema_ConstructedWithSchema_ReturnsSameSchema(): void {
		/* Arrange. */
		$schema          = $this->createMock( Schema::class );
		$updates_factory = $this->createMock( Updates_Factory::class );
		$installer       = new Installer( $schema, $updates_factory, '1.0.0' );

		/* Act. */
		$actual = $installer->get_schema();

		/* Assert. */
		$this->assertSame( $schema, $actual );
	}

	public function testUpdate_WhenNewInstall_SetsExpectedVersion(): void {
		/* Arrange. */
		$this->resetUpdateOptions();

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_version = get_option( 'sensei-version' );
		$this->assertEquals( SENSEI_LMS_VERSION, $actual_version );
	}

	public function testUpdate_WhenNewInstall_DoesntSetLegacyUpdateFlag(): void {
		/* Arrange. */
		$this->resetUpdateOptions();

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_flag = get_option( 'sensei_enrolment_legacy' );
		$this->assertEmpty( $actual_flag, 'Legacy update flag option should not be set on new installs' );
	}
	public function testUpdate_WhenOldInstallWithProgress_SetsExpectedVersion(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_version = get_option( 'sensei-version' );
		$this->assertEquals( SENSEI_LMS_VERSION, $actual_version );
	}

	public function testUpdate_WhenOldInstallWithProgress_SetsLegacyUpdateFlag(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_flag = get_option( 'sensei_enrolment_legacy' );
		$this->assertNotEmpty( $actual_flag, 'Legacy update flag option should be set on updates even when course and progress artifacts exist' );
	}

	public function testUpdate_WhenCurrentVersionIsV1AndProgressExists_SetsExpectedVersion(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		update_option( 'sensei-version', '1.9.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_version = get_option( 'sensei-version' );
		$this->assertEquals( Sensei()->version, $actual_version );
	}

	public function testUpdate_WhenCurrentVersionIsV1AndProgressExists_SetsLegacyUpdateFlag(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		update_option( 'sensei-version', '1.9.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_flag = get_option( 'sensei_enrolment_legacy' );
		$this->assertNotEmpty( $actual_flag, 'Legacy update flag option should be set during v1 updates with progress artifacts' );
	}

	public function testUpdate_WhenCurrentVersionIsV1AndWithoutProgress_SetsExpectedVersion(): void {
		/* Arrange. */
		$this->resetUpdateOptions();

		update_option( 'sensei-version', '1.9.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_version = get_option( 'sensei-version' );
		$this->assertEquals( Sensei()->version, $actual_version );
	}

	public function testUpdate_WhenCurrentVersionIsV1AndWithoutProgress_SetsLegacyUpdateFlag(): void {
		/* Arrange. */
		$this->resetUpdateOptions();

		update_option( 'sensei-version', '1.9.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_flag = get_option( 'sensei_enrolment_legacy' );
		$this->assertNotEmpty( $actual_flag, 'Legacy update flag option should be set during v1 updates without progress artifacts' );
	}

	public function testUpdate_WhenCurrentVersionIsV2AndWithoutProgress_SetsExpectedVersion(): void {
		/* Arrange. */
		$this->resetUpdateOptions();

		update_option( 'sensei-version', '2.4.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_version = get_option( 'sensei-version' );
		$this->assertEquals( Sensei()->version, $actual_version );
	}

	public function testUpdate_WhenCurrentVersionIsV2AndWithoutProgress_SetsUpdateLegacyFlag(): void {
		/* Arrange. */
		$this->resetUpdateOptions();

		update_option( 'sensei-version', '2.4.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_flag = get_option( 'sensei_enrolment_legacy' );
		$this->assertNotEmpty( $actual_flag, 'Legacy update flag option should be set during v2 updates with known previous version' );
	}

	public function testUpdate_WhenCurrentVersionIsV2AndWithProgress_SetsExpectedVersion(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		update_option( 'sensei-version', '2.4.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_version = get_option( 'sensei-version' );
		$this->assertEquals( Sensei()->version, $actual_version );
	}

	public function testUpdate_WhenCurrentVersionIsV2AndWithProgress_SetsLegacyUpdateFlag(): void {
		/* Arrange. */
		$user_id   = $this->factory->user->create();
		$course_id = $this->factory->course->create();
		Sensei_Utils::user_start_course( $user_id, $course_id );

		$this->resetUpdateOptions();

		update_option( 'sensei-version', '2.4.0' );

		/* Act. */
		Installer::instance( SENSEI_LMS_VERSION )->update();

		/* Assert. */
		$actual_flag = get_option( 'sensei_enrolment_legacy' );
		$this->assertNotEmpty( $actual_flag, 'Legacy update flag option should be set during v2 updates with progress' );
	}

	public function testUpdate_WhenInitHookWasTriggered_CallsTheMethod(): void {
		/* Arrange. */
		global $wp_filter;
		$initial_wp_filter = $wp_filter;
		$wp_filter         = [];

		$schema          = $this->createMock( Schema::class );
		$updates         = $this->createMock( \Sensei_Updates::class );
		$updates_factory = $this->createMock( Updates_Factory::class );
		$updates_factory->method( 'create' )->willReturn( $updates );

		$installer = new Installer( $schema, $updates_factory, SENSEI_LMS_VERSION );
		$installer->init();

		/* Expect & Act. */
		$updates->expects( $this->once() )->method( 'run_updates' );
		do_action( 'init' );

		$wp_filter = $initial_wp_filter;
	}

	/**
	 * Resets the update options.
	 */
	private function resetUpdateOptions() {
		delete_option( 'sensei-version' );
		delete_option( 'sensei_enrolment_legacy' );
	}

	/*
	 * Reset the installer state.
	 *
	 * This is needed, because the `Installer::install()` method is called once before the tests are run.
	 */
	private function reset_installer_state(): void {
		delete_transient( 'sensei_lms_installing' );
		delete_option( 'sensei-version' );
	}
}

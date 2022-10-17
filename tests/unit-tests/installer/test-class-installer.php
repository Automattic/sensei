<?php

namespace SenseiTest\Installer;

use Sensei\Installer\Installer;
use Sensei\Installer\Schema;

/**
 * Test for \Sensei_Pro_Installer\Installer.
 *
 * @covers \Sensei\Installer\Installer
 */
class Installer_Test extends \WP_UnitTestCase {
	/**
	 * The Installer instance.
	 *
	 * @var Installer
	 */
	protected $installer;

	/**
	 * Test specific setup.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->installer = Installer::instance( SENSEI_LMS_VERSION );

		$this->reset_installer_state();
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
		$schema    = $this->createMock( Schema::class );
		$installer = new Installer( $schema, '1.0.0' );

		/* Act. */
		$actual = $installer->get_schema();

		/* Assert. */
		$this->assertSame( $schema, $actual );
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

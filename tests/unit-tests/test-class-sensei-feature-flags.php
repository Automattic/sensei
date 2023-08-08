<?php

/**
 * Tests for Sensei_Feature_Flags class.
 *
 * @covers Sensei_Feature_Flags
 */
class Sensei_Class_Feature_Flags_Test extends WP_UnitTestCase {

	public function add_mock_flags( $arr ) {
		return array(
			'foo_feature' => false,
		);
	}

	/**
	 * Ran in a separate process to avoid conflicts with other tests.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testFlags() {
		add_filter( 'sensei_default_feature_flag_settings', array( $this, 'add_mock_flags' ) );
		$flags = new Sensei_Feature_Flags();

		$this->assertFalse( $flags->is_enabled( 'foo_feature' ) );

		define( 'SENSEI_FEATURE_FLAG_FOO_FEATURE', true );

		$flags = new Sensei_Feature_Flags();

		$this->assertTrue( $flags->is_enabled( 'foo_feature' ), 'overriden by define' );

		add_filter( 'sensei_feature_flag_foo_feature', '__return_false' );

		$this->assertFalse( $flags->is_enabled( 'foo_feature' ), 'overriden by filter' );
	}

	public function testConstruct_WhenCalled_AddsRegisterScriptsHook(): void {
		/* Arrange & Act. */
		$flags = new Sensei_Feature_Flags();

		/* Assert. */
		$priority = has_action( 'init', [ $flags, 'register_scripts' ] );
		$this->assertSame( 9, $priority );
	}

	public function testRegisterScripts_WhenCalled_RegistersFeatureFlagsScript(): void {
		/* Arrange & Act. */
		$flags = new Sensei_Feature_Flags();

		/* Assert. */
		$this->assertTrue( wp_script_is( 'sensei-feature-flags', 'registered' ) );
	}

	public function testRegisterScripts_WhenCalled_AddsFeatureFlagsInlineScript(): void {
		/* Arrange. */
		$flags = new Sensei_Feature_Flags();

		wp_deregister_script( 'sensei-feature-flags' );
		add_filter( 'sensei_default_feature_flag_settings', array( $this, 'add_mock_flags' ) );

		/* Act. */
		$flags->register_scripts();
		$inline_script = wp_scripts()->get_data( 'sensei-feature-flags', 'after' )[1];

		/* Assert. */
		$expected = 'window.sensei = window.sensei || {}; window.sensei.featureFlags = {"foo_feature":false};';
		$this->assertSame( $expected, $inline_script );
	}

	public function testIsEnabled_WhenEmailFeatureGivenAndFlagEnabled_ReturnsTrue(): void {
		/* Arrange. */
		$flags = new Sensei_Feature_Flags();

		add_filter( 'sensei_feature_flag_email_customization', '__return_true' );

		/* Act. */
		$actual = $flags->is_enabled( 'email_customization' );

		/* Assert. */
		$this->assertTrue( $actual );
	}

	public function testIsEnabled_WhenEmailFeatureGivenAndFlagDisabled_ReturnsFalse(): void {
		/* Arrange. */
		$flags = new Sensei_Feature_Flags();

		add_filter( 'sensei_feature_flag_email_customization', '__return_false' );

		/* Act. */
		$actual = $flags->is_enabled( 'email_customization' );

		/* Assert. */
		$this->assertFalse( $actual );
	}

	/**
	 * Ran in a separate process to avoid conflicts with other tests.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testIsEnabled_WhenInDevelopmentEnvironmentAndFlagEnabled_ReturnsTrue(): void {
		/* Arrange. */
		$flags = new class() extends Sensei_Feature_Flags {
			protected const DEFAULT_FEATURE_FLAGS = [
				'production'  => [
					'foo' => false,
				],
				'development' => [
					'foo' => true,
				],
			];
		};

		define( 'WP_RUN_CORE_TESTS', true );
		define( 'WP_ENVIRONMENT_TYPE', 'development' );

		/* Act. */
		$actual = $flags->is_enabled( 'foo' );

		/* Assert. */
		$this->assertTrue( $actual );
	}

	/**
	 * Ran in a separate process to avoid conflicts with other tests.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testIsEnabled_WhenInProductionEnvironmentAndFlagDisabled_ReturnsFalse(): void {
		/* Arrange. */
		$flags = new class() extends Sensei_Feature_Flags {
			protected const DEFAULT_FEATURE_FLAGS = [
				'production'  => [
					'foo' => false,
				],
				'development' => [
					'foo' => true,
				],
			];
		};

		define( 'WP_RUN_CORE_TESTS', true );
		define( 'WP_ENVIRONMENT_TYPE', 'production' );

		/* Act. */
		$actual = $flags->is_enabled( 'foo' );

		/* Assert. */
		$this->assertFalse( $actual );
	}

	public function testIsEnabled_WhenNoEnvironmentSet_ReturnProductionFlagValue(): void {
		/* Arrange. */
		$flags = new class() extends Sensei_Feature_Flags {
			protected const DEFAULT_FEATURE_FLAGS = [
				'production'  => [
					'foo' => true,
				],
				'development' => [
					'foo' => false,
				],
			];
		};

		/* Act. */
		$actual = $flags->is_enabled( 'foo' );

		/* Assert. */
		$this->assertTrue( $actual );
	}
}

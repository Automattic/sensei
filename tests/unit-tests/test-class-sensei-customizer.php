<?php
require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';

class Sensei_Customizer_Test extends WP_UnitTestCase {

	private $customizer;

	public function setUp() {
		parent::setUp();
		$this->customizer = new Sensei_Customizer();
	}

	public function tearDown() {
		parent::tearDown();
		remove_action( 'customize_register', array( $this->customizer, 'add_customizer_settings' ) );
		remove_action( 'customize_preview_init', array( $this->customizer, 'enqueue_customizer_helper' ) );
		remove_action( 'wp_head', array( $this->customizer, 'output_custom_settings' ) );
		$this->customizer = null;
	}

	public function testAddCustomizerSettings_CustomizeManagerGiven_AddsSectionToCustomizeManager() {
		$customize_manager = new WP_Customize_Manager();

		$this->customizer->add_customizer_settings( $customize_manager );
		$section = $customize_manager->get_section( 'sensei-course-theme' );

		$expected = [
			'priority'       => 40,
			'capability'     => 'manage_sensei',
			'theme_supports' => '',
			'title'          => 'Learning Mode (Sensei LMS)',
		];
		self::assertSame( $expected, $this->exportSection( $section ) );

	}

	private function exportSection( WP_Customize_Section $section ): array {
		return [
			'priority'       => $section->priority,
			'capability'     => $section->capability,
			'theme_supports' => $section->theme_supports,
			'title'          => $section->title,
		];
	}

	/**
	 * Test that customizer adds settings to the customizer manager.
	 *
	 * @param string $setting_id Setting identifier.
	 * @param array $expected Expected setting value.
	 *
	 * @dataProvider providerAddCustomizerSettings_CustomizeManagerGiven_AddsSettingToCustomizeManager
	 */
	public function testAddCustomizerSettings_CustomizeManagerGiven_AddsSettingToCustomizeManager( $setting_id, $expected ) {
		$customize_manager = new WP_Customize_Manager();

		$this->customizer->add_customizer_settings( $customize_manager );
		$setting = $customize_manager->get_setting( $setting_id );

		self::assertSame( $expected, $this->exportSetting( $setting ) );
	}

	public function exportSetting( WP_Customize_Setting $setting ): array {
		return [
			'default'   => $setting->default,
			'transport' => $setting->transport,
			'type'      => $setting->type,
		];
	}

	public function providerAddCustomizerSettings_CustomizeManagerGiven_AddsSettingToCustomizeManager(): array {
		return [
			'sensei-course-theme-primary-color'    => [
				'sensei-course-theme-primary-color',
				[
					'default'   => '#1e1e1e',
					'transport' => 'postMessage',
					'type'      => 'option',
				],
			],
			'sensei-course-theme-background-color' => [
				'sensei-course-theme-background-color',
				[
					'default'   => '#ffffff',
					'transport' => 'postMessage',
					'type'      => 'option',
				],
			],
			'sensei-course-theme-foreground-color' => [
				'sensei-course-theme-primary-color',
				[
					'default'   => '#1e1e1e',
					'transport' => 'postMessage',
					'type'      => 'option',
				],
			],
		];
	}
}

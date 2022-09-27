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

	public function testConstruct_Constructed_AddsActions() {
		$actual = [
			'customize_register'     => has_action( 'customize_register', array( $this->customizer, 'add_customizer_settings' ) ),
			'customize_preview_init' => has_action( 'customize_preview_init', array( $this->customizer, 'enqueue_customizer_helper' ) ),
			'wp_head'                => has_action( 'wp_head', array( $this->customizer, 'output_custom_settings' ) ),
		];

		$expected = [
			'customize_register'     => 10,
			'customize_preview_init' => 10,
			'wp_head'                => 10,
		];

		self::assertSame( $expected, $actual );
	}

	public function testAddCustomizerSettings_CustomizeManagerGiven_AddsSectionToCustomizeManager() {
		$customize_manager = new WP_Customize_Manager();

		$this->customizer->add_customizer_settings( $customize_manager );
		$section = $customize_manager->get_section( 'sensei-learning-mode' );

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
			'sensei-learning-mode-primary-color'    => [
				'sensei-learning-mode-primary-color',
				[
					'default'   => '#1e1e1e',
					'transport' => 'postMessage',
					'type'      => 'option',
				],
			],
			'sensei-learning-mode-background-color' => [
				'sensei-learning-mode-background-color',
				[
					'default'   => '#ffffff',
					'transport' => 'postMessage',
					'type'      => 'option',
				],
			],
			'sensei-learning-mode-foreground-color' => [
				'sensei-learning-mode-foreground-color',
				[
					'default'   => '#1e1e1e',
					'transport' => 'postMessage',
					'type'      => 'option',
				],
			],
		];
	}

	/**
	 * Test that customizer adds controls to the customizer manager.
	 *
	 * @param string $setting_id Setting identifier.
	 * @param array $expected Expected setting value.
	 *
	 * @dataProvider providerAddCustomizerSettings_CustomizeManagerGiven_AddsControlToCustomizeManager
	 */
	public function testAddCustomizerSettings_CustomizeManagerGiven_AddsControlToCustomizeManager( $setting_id, $expected ) {
		$customize_manager = new WP_Customize_Manager();

		$this->customizer->add_customizer_settings( $customize_manager );
		$control = $customize_manager->get_control( $setting_id );

		self::assertSame( $expected, $this->exportControl( $control ) );
	}

	public function exportControl( WP_Customize_Control $control ): array {
		return [
			'label'   => $control->label,
			'section' => $control->section,
			'type'    => $control->type,
		];
	}

	public function providerAddCustomizerSettings_CustomizeManagerGiven_AddsControlToCustomizeManager(): array {
		return [
			'sensei-learning-mode-primary-color'    => [
				'sensei-learning-mode-primary-color',
				[
					'label'   => 'Primary Color',
					'section' => 'sensei-learning-mode',
					'type'    => 'color',
				],
			],
			'sensei-learning-mode-background-color' => [
				'sensei-learning-mode-background-color',
				[
					'label'   => 'Background Color',
					'section' => 'sensei-learning-mode',
					'type'    => 'color',
				],
			],
			'sensei-learning-mode-foreground-color' => [
				'sensei-learning-mode-foreground-color',
				[
					'label'   => 'Text Color',
					'section' => 'sensei-learning-mode',
					'type'    => 'color',
				],
			],
		];
	}

	public function testOutputCustomSettings_DefaultValuesSet_OutputsCustomSettings() {
		add_option( 'sensei-learning-mode-primary-color', '#1e1e1e' );
		add_option( 'sensei-learning-mode-background-color', '#ffffff' );
		add_option( 'sensei-learning-mode-foreground-color', '#1e1e1e' );

		ob_start();
		$this->customizer->output_custom_settings();
		$actual = ob_get_clean();

		$expected = '		<style>
			:root {
						}
		</style>
		';

		self::assertSame( $expected, $actual );
	}

	public function testOutputCustomSettings_CustomValuesSet_OutputsCustomSettings() {
		add_option( 'sensei-learning-mode-primary-color', 'a' );
		add_option( 'sensei-learning-mode-background-color', 'b' );
		add_option( 'sensei-learning-mode-foreground-color', 'c' );

		ob_start();
		$this->customizer->output_custom_settings();
		$actual = ob_get_clean();

		$expected = '		<style>
			:root {
			--sensei-learning-mode-primary-color: a;
--sensei-learning-mode-background-color: b;
--sensei-learning-mode-foreground-color: c;
			}
		</style>
		';

		self::assertSame( $expected, $actual );
	}

	public function testOutputCustomizerHelper_Constructed_OutputsCustomizerHelper() {
		ob_start();
		$this->customizer->output_customizer_helper();
		$actual = ob_get_clean();

		$expected = '		<script type="text/javascript">
						wp.customize( \'sensei-learning-mode-primary-color\', ( setting ) => {
				setting.bind( ( value ) => {
					document.documentElement.style.setProperty( \'--sensei-learning-mode-primary-color\', value )
				} );
			} );
							wp.customize( \'sensei-learning-mode-background-color\', ( setting ) => {
				setting.bind( ( value ) => {
					document.documentElement.style.setProperty( \'--sensei-learning-mode-background-color\', value )
				} );
			} );
							wp.customize( \'sensei-learning-mode-foreground-color\', ( setting ) => {
				setting.bind( ( value ) => {
					document.documentElement.style.setProperty( \'--sensei-learning-mode-foreground-color\', value )
				} );
			} );
						</script>
		';

		self::assertSame( $expected, $actual );
	}

	public function testEnqueueCustomizerHelper_Constructed_AddsAction() {
		$this->customizer->enqueue_customizer_helper();

		$actual = has_action( 'wp_print_footer_scripts', [ $this->customizer, 'output_customizer_helper' ] );

		self::assertSame( 10, $actual );
	}
}

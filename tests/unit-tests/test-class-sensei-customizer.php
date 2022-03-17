<?php
require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';

class Sensei_Customizer_Test extends WP_UnitTestCase {

	private $customizer;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		$bootstrap = Sensei_Unit_Tests_Bootstrap::instance();

		//      $bootstrap->

	}

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
}

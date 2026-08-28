<?php
namespace SenseiTest\WPML;

use Sensei\WPML\Slug;

/**
 * Class Slug_Test
 *
 * @covers \Sensei\WPML\Slug
 */
class Slug_Test extends \WP_UnitTestCase {

	public function tear_down(): void {
		unset( \Sensei()->settings->settings['wpml_slug_translation'] );
		parent::tear_down();
	}

	public function testMaybeActivateWpmlSlugTranslation_OptionEnabledGiven_RegistersTheSlugStrings() {
		/* Arrange. */
		$slug = new Slug();
		\Sensei()->settings->settings['wpml_slug_translation'] = true;

		$registered      = array();
		$register_filter = function ( $context, $name, $value ) use ( &$registered ) {
			$registered[] = array( $context, $name, $value );
		};
		add_action( 'wpml_register_single_string', $register_filter, 10, 3 );

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Clean up & Assert. */
		remove_action( 'wpml_register_single_string', $register_filter );
		$this->assertSame(
			array(
				array( 'WordPress', 'URL slug: course', 'course' ),
				array( 'WordPress', 'URL slug: lesson', 'lesson' ),
				array( 'WordPress', 'URL slug: quiz', 'quiz' ),
			),
			$registered
		);
	}

	public function testMaybeActivateWpmlSlugTranslation_OptionDisabledGiven_RegistersNothing() {
		/* Arrange. */
		$slug = new Slug();

		$registered      = array();
		$register_filter = function ( $context, $name, $value ) use ( &$registered ) {
			$registered[] = array( $context, $name, $value );
		};
		add_action( 'wpml_register_single_string', $register_filter, 10, 3 );

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Clean up & Assert. */
		remove_action( 'wpml_register_single_string', $register_filter );
		$this->assertSame( array(), $registered );
	}

	public function testMaybeActivateWpmlSlugTranslation_NoPriorSlugSettingsGiven_SavesTheTypesWithTheMasterSwitchOn() {
		/* Arrange. */
		$slug = $this->create_slug_with_settings_spy( $saved );
		\Sensei()->settings->settings['wpml_slug_translation'] = true;

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Assert. */
		$this->assertSame(
			array(
				array(
					'types' => array(
						'course' => 1,
						'lesson' => 1,
						'quiz'   => 1,
					),
					'on'    => 1,
				),
			),
			$saved
		);
	}

	public function testMaybeActivateWpmlSlugTranslation_MasterSwitchOffWithAForeignTypeGiven_KeepsTheMasterSwitchOff() {
		/* Arrange. */
		$slug = $this->create_slug_with_settings_spy( $saved );
		\Sensei()->settings->settings['wpml_slug_translation'] = true;

		$setting_filter = $this->stub_wpml_slug_setting(
			array(
				'on'    => 0,
				'types' => array( 'product' => 1 ),
			)
		);

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Clean up & Assert. */
		remove_filter( 'wpml_setting', $setting_filter );
		$this->assertSame(
			array(
				array(
					'on'    => 0,
					'types' => array(
						'product' => 1,
						'course'  => 1,
						'lesson'  => 1,
						'quiz'    => 1,
					),
				),
			),
			$saved
		);
	}

	public function testMaybeActivateWpmlSlugTranslation_MasterSwitchExplicitlyOffWithOnlySenseiTypesGiven_KeepsTheMasterSwitchOff() {
		/* Arrange. */
		$slug = $this->create_slug_with_settings_spy( $saved );
		\Sensei()->settings->settings['wpml_slug_translation'] = true;

		$setting_filter = $this->stub_wpml_slug_setting( array( 'on' => 0 ) );

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Clean up & Assert. */
		remove_filter( 'wpml_setting', $setting_filter );
		$this->assertSame(
			array(
				array(
					'on'    => 0,
					'types' => array(
						'course' => 1,
						'lesson' => 1,
						'quiz'   => 1,
					),
				),
			),
			$saved
		);
	}

	public function testMaybeActivateWpmlSlugTranslation_SlugTranslationAlreadyActiveGiven_SavesNothing() {
		/* Arrange. */
		$slug = $this->create_slug_with_settings_spy( $saved );
		\Sensei()->settings->settings['wpml_slug_translation'] = true;

		$setting_filter = $this->stub_wpml_slug_setting(
			array(
				'on'    => 1,
				'types' => array(
					'course' => 1,
					'lesson' => 1,
					'quiz'   => 1,
				),
			)
		);

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Clean up & Assert. */
		remove_filter( 'wpml_setting', $setting_filter );
		$this->assertSame( array(), $saved );
	}

	public function testMaybeActivateWpmlSlugTranslation_OptionDisabledGiven_SavesNothing() {
		/* Arrange. */
		$slug = $this->create_slug_with_settings_spy( $saved );

		/* Act. */
		$slug->maybe_activate_wpml_slug_translation();

		/* Assert. */
		$this->assertSame( array(), $saved );
	}

	/**
	 * Stub the `wpml_setting` filter for `posts_slug_translation`.
	 *
	 * @param array $slug_settings The stubbed setting value.
	 * @return callable The filter, so the test can remove it.
	 */
	private function stub_wpml_slug_setting( array $slug_settings ) {
		$setting_filter = function ( $default_value, $key ) use ( $slug_settings ) {
			if ( 'posts_slug_translation' === $key ) {
				return $slug_settings;
			}
			return $default_value;
		};
		add_filter( 'wpml_setting', $setting_filter, 10, 2 );

		return $setting_filter;
	}

	/**
	 * Build a Slug instance whose settings write records into $saved.
	 *
	 * @param array|null $saved Filled with each saved settings array (by reference).
	 * @return Slug
	 */
	private function create_slug_with_settings_spy( &$saved ) {
		$saved = array();

		return new class( $saved ) extends Slug {
			private $saved;

			public function __construct( &$saved ) {
				$this->saved = &$saved;
			}

			protected function save_slug_translation_settings( $slug_settings ) {
				$this->saved[] = $slug_settings;
			}
		};
	}
}

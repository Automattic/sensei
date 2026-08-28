<?php
/**
 * File containing the \Sensei\WPML\Settings class.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Compatibility code with WPML.
 *
 * @since 4.23.1
 *
 * @internal
 */
class Settings {
	use WPML_API;

	/**
	 * Init hooks.
	 */
	public function init() {
		if ( ! $this->is_wpml_active() ) {
			return;
		}

		add_filter( 'sensei_settings_tabs', array( $this, 'add_tab' ) );
		add_filter( 'sensei_settings_fields', array( $this, 'add_fields' ) );
	}

	/**
	 * Add WPML tab.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param array $sections Settings sections.
	 * @return array
	 */
	public function add_tab( $sections ) {
		$sections['sensei-wpml-settings'] = array(
			'name'        => __( 'WPML', 'sensei-lms' ),
			'description' => __( 'Settings related to WMPL.', 'sensei-lms' ),
		);

		return $sections;
	}

	/**
	 * Add WPML fields.
	 *
	 * @since 4.23.1
	 *
	 * @internal
	 *
	 * @param array $fields Settings fields.
	 * @return array
	 */
	public function add_fields( $fields ) {
		$fields['wpml_slug_translation'] = array(
			'name'        => __( 'Don\'t translate Sensei slugs', 'sensei-lms' ),
			'description' => __( 'Sensei slugs will not be translated. If you disable this setting, translated slugs may return 404 errors.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => true,
			'section'     => 'sensei-wpml-settings',
		);

		return $fields;
	}
}

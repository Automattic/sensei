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
			'name'        => __( 'Use WPML slug translation', 'sensei-lms' ),
			'description' => __( 'Sensei stops translating its course, lesson, and quiz URL slugs and registers them with WPML String Translation instead. Translate the "URL slug" strings there to localize your URLs. Enable this if your translated courses return a 404 page.', 'sensei-lms' ),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'sensei-wpml-settings',
		);

		return $fields;
	}
}

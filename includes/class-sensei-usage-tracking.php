<?php
/**
 * Usage Tracking subclass for Sensei.
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require dirname( __FILE__ ) . '/lib/usage-tracking/class-usage-tracking-base.php';

/**
 * Sensei Usage Tracking subclass.
 **/
class Sensei_Usage_Tracking extends Sensei_Usage_Tracking_Base {

	const SENSEI_SETTING_NAME = 'sensei_usage_tracking_enabled';

	const SENSEI_TRACKING_INFO_URL = 'https://senseilms.com/documentation/what-data-does-sensei-track/';

	protected function __construct() {
		parent::__construct();

		// Add filter for settings.
		add_filter( 'sensei_settings_fields', array( $this, 'add_setting_field' ) );

		// Init event logging source filters.
		add_action( 'init', [ $this, 'init_event_logging_sources' ] );
	}

	/*
	 * Initalization.
	 */

	/**
	 * Initialize filters for event logging sources.
	 *
	 * @since 2.1.0
	 *
	 * @access private
	 */
	public function init_event_logging_sources() {
		add_filter( 'sensei_event_logging_source', [ $this, 'detect_event_logging_source' ], 1 );
		add_filter( 'template_redirect', [ $this, 'set_event_logging_source_frontend' ] );
		add_filter( 'import_start', [ $this, 'set_event_logging_source_data_import' ] );
	}

	/*
	 * Implementation for abstract functions.
	 */

	public static function get_instance() {
		return self::get_instance_for_subclass( get_class() );
	}

	protected function get_prefix() {
		return 'sensei';
	}

	protected function get_text_domain() {
		return 'sensei-lms';
	}

	public function get_tracking_enabled() {
		return Sensei()->settings->get( self::SENSEI_SETTING_NAME ) || false;
	}

	protected function set_tracking_enabled( $enable ) {
		Sensei()->settings->set( self::SENSEI_SETTING_NAME, $enable );

		// Refresh settings in-memory so we get the right value.
		Sensei()->settings->get_settings();
	}

	protected function current_user_can_manage_tracking() {
		return current_user_can( 'manage_sensei' );
	}

	protected function opt_in_dialog_text() {
		return sprintf(
			/*
			 * translators: the href tag contains the URL for the page telling
			 * users what data Sensei tracks.
			 */
			__(
				"We'd love if you helped us make Sensei LMS better by allowing us to collect
				<a href=\"%s\" target=\"_blank\">usage tracking data</a>.
				No sensitive information is collected, and you can opt out at any time.",
				'sensei-lms'
			),
			self::SENSEI_TRACKING_INFO_URL
		);
	}

	protected function do_track_plugin( $plugin_slug ) {
		if ( 1 === preg_match( '/(^sensei|\-sensei$)/', $plugin_slug ) ) {
			return true;
		}
		$third_party_plugins = array(
			'classic-editor',
			'jetpack',
			'polylang',
			'sitepress-multilingual-cms',
			'woocommerce',
			'woocommerce-memberships',
			'woocommerce-product-vendors',
			'woocommerce-subscriptions',
			'woothemes-updater',
			'wp-quicklatex',
		);
		if ( in_array( $plugin_slug, $third_party_plugins, true ) ) {
			return true;
		}
		return false;
	}


	/*
	 * Hooks.
	 */

	public function add_setting_field( $fields ) {
		$fields[ self::SENSEI_SETTING_NAME ] = array(
			'name'        => __( 'Enable usage tracking', 'sensei-lms' ),
			'description' => sprintf(
				/*
				 * translators: the href tag contains the URL for the page telling
				 * users what data Sensei tracks.
				 */
				__(
					'Help us make Sensei LMS better by allowing us to collect
					<a href="%s" target="_blank">usage tracking data</a>.
					No sensitive information is collected.',
					'sensei-lms'
				),
				self::SENSEI_TRACKING_INFO_URL
			),
			'type'        => 'checkbox',
			'default'     => false,
			'section'     => 'default-settings',
		);

		return $fields;
	}

	/**
	 * Attempt to detect the source of the event logging request.
	 *
	 * @since 2.1.0
	 *
	 * @access private
	 *
	 * @param  string $source The initial source.
	 * @return string         The detected source.
	 */
	public static function detect_event_logging_source( $source ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest-api';
		}

		if ( is_admin() ) {
			return 'wp-admin';
		}

		return $source;
	}


	/**
	 * Set the event logging source to `frontend`.
	 *
	 * @since 2.1.0
	 *
	 * @access private
	 */
	public function set_event_logging_source_frontend() {
		add_filter(
			'sensei_event_logging_source',
			function( $fields ) {
				return 'frontend';
			}
		);
	}

	/**
	 * Set the event logging source to `data-import`.
	 *
	 * @since 2.1.0
	 *
	 * @access private
	 */
	public function set_event_logging_source_data_import() {
		add_filter(
			'sensei_event_logging_source',
			function( $fields ) {
				return 'data-import';
			}
		);
	}
}

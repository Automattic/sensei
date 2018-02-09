<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include dirname( __FILE__ ) . '/../lib/usage-tracking/class-usage-tracking-base.php';

/**
 * Sensei Usage Tracking subclass.
 **/
class Sensei_Usage_Tracking extends Sensei_Usage_Tracking_Base {

	const SENSEI_SETTING_NAME = 'sensei_usage_tracking_enabled';

	protected function __construct() {
		parent::__construct();

		// Add filter for settings
		add_filter( 'sensei_settings_fields', array( $this, 'add_setting_field' ) );
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

	protected function get_tracking_enabled() {
		return Sensei()->settings->get( self::SENSEI_SETTING_NAME ) || false;
	}

	protected function set_tracking_enabled( $enable ) {
		Sensei()->settings->set( self::SENSEI_SETTING_NAME, $enable );
	}

	protected function current_user_can_manage_tracking() {
		return current_user_can( 'manage_sensei' );
	}

	protected function opt_in_dialog_text() {
		return sprintf( __( "We'd love if you helped us make Sensei better by allowing us to collect
			<a href=\"%s\" target=\"_blank\">usage tracking data</a>.
			No sensitive information is collected, and you can opt out at any time.",
			'woothemes-sensei' ), 'https://docs.woocommerce.com/document/what-data-does-sensei-track' );
	}


	/*
	 * Hooks.
	 */

	public function add_setting_field( $fields ) {
		// default-settings
		$fields[ self::SENSEI_SETTING_NAME ] = array(
			'name' => __( 'Enable usage tracking', 'woothemes-sensei' ),
			'description' => __(
				'Help us make Sensei better by allowing us to collect
				<a href="https://docs.woocommerce.com/document/what-data-does-sensei-track" target="_blank">usage tracking data</a>.
				No sensitive information is collected.', 'woothemes-sensei' ),
			'type' => 'checkbox',
			'default' => false,
			'section' => 'default-settings'
		);

		return $fields;
	}
}

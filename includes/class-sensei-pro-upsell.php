<?php
/**
 * File containing the Sensei_Pro_Upsell class.
 *
 * @package sensei-lms
 * @since   4.23.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Pro_Upsell
 *
 * Handles the generation of Pro upsell URLs.
 *
 * @since 4.24.6
 */
class Sensei_Pro_Upsell {
	/**
	 * Get the base upsell URL based on the environment.
	 *
	 * @return string The base upsell URL.
	 */
	public static function get_base_url(): string {
		if ( get_option( 'wpcom_active_subscriptions' ) ) {
			$site_slug = wp_parse_url( get_site_url(), PHP_URL_HOST );
			return "https://wordpress.com/plugins/sensei-pro/{$site_slug}";
		}

		return 'https://senseilms.com/sensei-pro/';
	}

	/**
	 * Get the default UTM parameters.
	 *
	 * @param string $campaign The campaign name.
	 * @return array The default UTM parameters.
	 */
	public static function get_default_utm_params( string $campaign = 'default' ): array {
		return [
			'utm_source'   => 'plugin_sensei',
			'utm_medium'   => 'upsell',
			'utm_campaign' => $campaign,
		];
	}
}

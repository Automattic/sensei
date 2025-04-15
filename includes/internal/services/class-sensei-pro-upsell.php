<?php
/**
 * File containing the Sensei_Pro_Upsell class.
 *
 * @package sensei-lms
 * @since   4.25.0
 * @internal This class is not meant to be used by third-party code.
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Pro_Upsell
 *
 * Handles the generation of Pro upsell URLs.
 *
 * @internal This class is not meant to be used by third-party code.
 * @since 4.25.0
 * @package sensei
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
	 * Get the checkout URL based on the environment.
	 *
	 * @return string The checkout URL.
	 */
	public static function get_checkout_url(): string {
		if ( get_option( 'wpcom_active_subscriptions' ) ) {
			$site_slug = wp_parse_url( get_site_url(), PHP_URL_HOST );
			return "https://wordpress.com/checkout/{$site_slug}/sensei_pro_monthly";
		}

		return 'https://senseilms.com/checkout/?add-to-cart=7009';
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

	/**
	 * Get the Sensei Pro upsell URL.
	 *
	 * @param string $campaign The campaign name.
	 * @return string The Sensei Pro upsell URL.
	 */
	public static function get_sensei_pro_upsell_url( string $campaign = 'default' ): string {
		$base_url   = self::get_base_url();
		$utm_params = self::get_default_utm_params( $campaign );

		// Using WordPress core function to build URL with query parameters.
		return add_query_arg( $utm_params, $base_url );
	}
}

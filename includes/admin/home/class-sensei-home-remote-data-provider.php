<?php
/**
 * File containing Sensei_Home_Remote_Data_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class responsible for fetching data from SenseiLMS.com.
 */
class Sensei_Home_Remote_Data_Provider {
	const API_BASE_URL     = 'https://senseilms.com/wp-json/senseilms-home/1.0/';
	const CACHE_KEY_PREFIX = 'sensei_home_remote_data_';
	const CACHE_TTL        = DAY_IN_SECONDS;

	/**
	 * The primary plugin to pull data for.
	 *
	 * @var string
	 */
	private $primary_plugin_slug;

	/**
	 * Sensei_Home_Remote_Data_Provider constructor.
	 *
	 * @param string $primary_plugin_slug The primary plugin slug.
	 */
	public function __construct( string $primary_plugin_slug ) {
		$this->primary_plugin_slug = $primary_plugin_slug;
	}

	/**
	 * Fetch data from SenseiLMS.com.
	 *
	 * @param int $max_age Maximum age of the cached data in seconds. Max is 1 day (in seconds).
	 *
	 * @return array|false
	 */
	public function fetch( int $max_age = null ) {
		$url           = $this->get_api_url();
		$transient_key = self::CACHE_KEY_PREFIX . md5( $url );
		$data          = get_transient( $transient_key );

		// If the data is too old, fetch it again.
		if ( $max_age && is_array( $data ) ) {
			$age = time() - ( $data['_fetched'] ?? 0 );
			if ( $age > $max_age ) {
				$data = false;
			}

			unset( $data['_fetched'] );
		}

		if ( false === $data ) {
			$response = wp_safe_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $data ) ) {
				return false;
			}

			set_transient( $transient_key, array_merge( $data, [ '_fetched' => time() ] ), self::CACHE_TTL );
		}

		return $data;
	}

	/**
	 * Get the primary plugin slug.
	 *
	 * @return string
	 */
	private function get_primary_plugin_slug() : string {
		/**
		 * Filter the primary plugin slug.
		 *
		 * @since $$next-version$$
		 * @hook sensei_home_remote_data_primary_plugin_slug
		 *
		 * @param {string} $primary_plugin_slug The primary plugin slug.
		 *
		 * @return {string} The filtered primary plugin slug.
		 */
		return apply_filters( 'sensei_home_remote_data_primary_plugin_slug', $this->primary_plugin_slug );
	}

	/**
	 * Get the other plugin slugs.
	 *
	 * @return array
	 */
	private function get_other_plugins() : array {
		/**
		* Filter the other plugins used for Sensei Home.
		*
		* @since $$next-version$$
		* @hook sensei_home_remote_data_other_plugins
		*
		* @param {array} $other_plugins The other plugins.
		*
		* @return {array} The filtered other plugins.
		*/
		return array_diff( apply_filters( 'sensei_home_remote_data_other_plugins', [] ), [ $this->get_primary_plugin_slug() ] );
	}

	/**
	 * Get the API URL to use.
	 *
	 * @return string
	 */
	private function get_api_url() : string {
		$url = sprintf( self::API_BASE_URL . '%s.json', $this->get_primary_plugin_slug() );

		$query_args = [
			'version' => Sensei()->version,
			'lang'    => determine_locale(),
		];

		$other_plugins = $this->get_other_plugins();
		if ( ! empty( $other_plugins ) ) {
			$query_args['other_plugins'] = implode( ',', $other_plugins );
		}

		$url = add_query_arg( $query_args, $url );

		return $url;
	}
}

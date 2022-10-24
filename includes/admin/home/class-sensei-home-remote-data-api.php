<?php
/**
 * File containing Sensei_Home_Remote_Data_API class.
 *
 * @package sensei-lms
 * @since   4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class responsible for fetching data from SenseiLMS.com.
 */
class Sensei_Home_Remote_Data_API {
	const API_BASE_URL     = 'https://senseilms.com/wp-json/senseilms-home/1.0/';
	const CACHE_KEY_PREFIX = 'sensei_home_remote_data_';
	const CACHE_TTL        = DAY_IN_SECONDS;
	const CACHE_FAIL_TTL   = HOUR_IN_SECONDS;

	/**
	 * The primary plugin to pull data for.
	 *
	 * @var string
	 */
	private $primary_plugin_slug;

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $plugin_version;

	/**
	 * The local cache for multiple calls to `fetch` within the same request.
	 *
	 * @var array
	 */
	private $remote_data = [];

	/**
	 * Sensei_Home_Remote_Data_API constructor.
	 *
	 * @param string $primary_plugin_slug The primary plugin slug.
	 * @param string $plugin_version      The plugin version.
	 */
	public function __construct( string $primary_plugin_slug, string $plugin_version ) {
		$this->primary_plugin_slug = $primary_plugin_slug;
		$this->plugin_version      = $plugin_version;
	}

	/**
	 * Fetch data from SenseiLMS.com.
	 *
	 * @param int $max_age Maximum age of the cached data in seconds. Max is 1 day (in seconds).
	 *
	 * @return array|\WP_Error
	 */
	public function fetch( int $max_age = null ) {
		$url       = $this->get_api_url();
		$cache_key = self::CACHE_KEY_PREFIX . md5( $url );
		$data      = $this->remote_data[ $cache_key ] ?? get_transient( $cache_key );

		/**
		 * Filter if we should retry errors when fetching remote data.
		 *
		 * @since 4.8.0
		 * @hook sensei_home_remote_data_retry_error
		 *
		 * @param {bool} $retry_error If we should retry errors. Default true.
		 *
		 * @return {bool} If we should retry errors.
		 */
		$retry_error = apply_filters( 'sensei_home_remote_data_retry_error', true );

		// If the cached data is an error, return it unless we've forced a refresh.
		if ( isset( $data['error'] ) ) {
			// Don't retry if we aren't supposed to or if the error was from the same PHP execution.
			if ( ! $retry_error || ! empty( $data['force_no_retry'] ) ) {
				return $this->unserialize_wp_error( $data['error'] );
			}

			$data = false;
		}

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
				$this->set_fail_retry( $cache_key, $response );
				return $response;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $data ) ) {
				$last_error = new WP_Error( 'sensei-home-remote-data-invalid-response' );
				$this->set_fail_retry( $cache_key, $last_error );

				return $last_error;
			}

			$this->remote_data[ $cache_key ] = array_merge( $data, [ '_fetched' => time() ] );
			set_transient( $cache_key, $this->remote_data[ $cache_key ], self::CACHE_TTL );
		}

		return $data;
	}

	/**
	 * Set a timeout to retry the request after a failure.
	 *
	 * @param string    $cache_key The cache key.
	 * @param \WP_Error $error     The error.
	 */
	private function set_fail_retry( $cache_key, $error ) {
		$data = [
			'error' => $this->serialize_wp_error( $error ),
		];

		$this->remote_data[ $cache_key ] = $data;

		// We don't want to retry on the same request.
		$this->remote_data[ $cache_key ]['force_no_retry'] = true;

		set_transient( $cache_key, $data, self::CACHE_FAIL_TTL );
	}

	/**
	 * Safely serialize a WP_Error object.
	 *
	 * @param \WP_Error $error The error to serialize.
	 *
	 * @return array
	 */
	private function serialize_wp_error( \WP_Error $error ) {
		$data = [];
		foreach ( $error->get_error_codes() as $code ) {
			$data[ $code ] = [
				'messages' => $error->get_error_messages( $code ),
				'data'     => $error->get_all_error_data( $code ),
			];
		}

		return $data;
	}

	/**
	 * Unserialize a WP Error array.
	 *
	 * @param array $data The data to unserialize.
	 *
	 * @return \WP_Error
	 */
	private function unserialize_wp_error( array $data ) {
		$error = new \WP_Error();
		foreach ( $data as $code => $error_data ) {
			foreach ( $error_data['messages'] as $message ) {
				$error->add( $code, $message, $error_data['data'] );
			}
		}

		return $error;
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
		 * @since 4.8.0
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
		 * @since 4.8.0
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
	protected function get_api_url() : string {
		$url = sprintf( self::API_BASE_URL . '%s.json', $this->get_primary_plugin_slug() );

		$query_args = [
			'version' => $this->plugin_version,
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

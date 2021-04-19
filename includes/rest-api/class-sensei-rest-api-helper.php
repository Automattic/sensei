<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @deprecated 3.11.0
 */
class Sensei_REST_API_Helper {
	/**
	 * @var Sensei_REST_API_V1
	 */
	private $api;

	/**
	 * Sensei_REST_API_Helpers constructor.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param $api Sensei_REST_API_V1
	 */
	public function __construct( $api ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->api = $api;
	}

	/**
	 * @deprecated 3.11.0
	 */
	public function is_numeric( $thing, $request, $key ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return is_numeric( $thing );
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @param $rest_namespace
	 * @param $domain_model
	 * @param $args
	 * @return string
	 */
	public function build_url_for_item( $rest_namespace, $domain_model ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->rest_url() . $rest_namespace . '/' . $domain_model->get_id();
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @return string
	 */
	public function rest_url() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return rest_url();
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @return string
	 */
	public function base_namespace_url() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->rest_url() . $this->api->get_api_prefix();
	}

}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Sensei_REST_API_Helper {
	/**
	 * @var Sensei_REST_API_V1
	 */
	private $api;

	/**
	 * Sensei_REST_API_Helpers constructor.
	 *
	 * @param $api Sensei_REST_API_V1
	 */
	public function __construct( $api ) {
		$this->api = $api;
	}

	public function is_numeric( $thing, $request, $key ) {
		return is_numeric( $thing );
	}

	/**
	 * @param $rest_namespace
	 * @param $domain_model
	 * @param $args
	 * @return string
	 */
	public function build_url_for_item( $rest_namespace, $domain_model ) {
		return $this->rest_url() . $rest_namespace . '/' . $domain_model->get_id();
	}

	public function rest_url() {
		return rest_url();
	}

	public function base_namespace_url() {
		return $this->rest_url() . $this->api->get_api_prefix();
	}


	/**
	 * Helper to register multiple REST API endpoints with some common options.
	 * Registered callbacks are passed the request parameters as the first argument.
	 *
	 * @param string $namespace      URL prefix.
	 * @param array  $endpoints      Endpoint descriptors.
	 * @param array  $common_options Shared options between endpoint or arguments.
	 */
	public static function register_endpoints( $namespace, $endpoints, $common_options = [] ) {

		$add_arg_common = function( $arg ) use ( $common_options ) {
			return isset( $common_options['arg'] ) ? array_merge( $common_options['arg'], $arg ) : $arg;
		};

		$add_endpoint_common = function( $endpoint ) use ( $common_options, $add_arg_common ) {
			if ( isset( $common_options['endpoint'] ) ) {
				$endpoint = array_merge( $common_options['endpoint'], $endpoint );
			}
			if ( isset( $endpoint['args'] ) ) {
				$endpoint['args'] = array_map( $add_arg_common, $endpoint['args'] );
			}
			$callback = $endpoint['callback'];

			$endpoint['callback'] = function( $request ) use ( $callback ) {
				$data = $request->get_params();
				return call_user_func( $callback, $data, $request );
			};

			return $endpoint;
		};

		foreach ( $endpoints as $name => $endpoint ) {
			register_rest_route(
				$namespace,
				$name,
				array_map( $add_endpoint_common, $endpoint )
			);
		}
	}

}

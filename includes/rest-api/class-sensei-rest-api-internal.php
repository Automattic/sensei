<?php
/**
 * Internal REST API for Sensei.
 *
 * @package Sensei
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_REST_API_Internal
 *
 * @package rest-api
 */
class Sensei_REST_API_Internal {

	/**
	 * Internal API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'sensei-internal/v1';

	/**
	 * Endpoint configurations.
	 *
	 * @var WP_REST_Controller[]
	 */
	private $controllers = [];

	/**
	 * Sensei_REST_API_Internal constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	/**
	 * Register internal endpoints.
	 */
	public function register() {

		$this->controllers = [
		];

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}
}

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
	 * Sensei Home REST Mapper.
	 *
	 * @var Sensei_REST_API_Home_Controller_Mapper
	 */
	private $home_controller_mapper;

	/**
	 * Sensei Home Quick Links provider.
	 *
	 * @var Sensei_Home_Quick_Links_Provider
	 */
	private $quick_links_provider;

	/**
	 * Sensei Home Help provider.
	 *
	 * @var Sensei_Home_Help_Provider
	 */
	private $help_provider;

	/**
	 * Sensei_REST_API_Internal constructor.
	 */
	public function __construct() {
		$this->home_controller_mapper = new Sensei_REST_API_Home_Controller_Mapper();
		$this->quick_links_provider   = new Sensei_Home_Quick_Links_Provider();
		$this->help_provider          = new Sensei_Home_Help_Provider();
		add_action( 'rest_api_init', [ $this, 'register' ] );
	}

	/**
	 * Register internal endpoints.
	 */
	public function register() {

		$this->controllers = [
			new Sensei_REST_API_Setup_Wizard_Controller( $this->namespace ),
			new Sensei_REST_API_Import_Controller( $this->namespace ),
			new Sensei_REST_API_Export_Controller( $this->namespace ),
			new Sensei_REST_API_Course_Structure_Controller( $this->namespace ),
			new Sensei_REST_API_Lesson_Quiz_Controller( $this->namespace ),
			new Sensei_REST_API_Question_Options_Controller( $this->namespace ),
			new Sensei_REST_API_Extensions_Controller( $this->namespace ),
			new Sensei_REST_API_Send_Message_Controller( $this->namespace ),
			new Sensei_REST_API_Course_Students_Controller( $this->namespace ),
			new Sensei_REST_API_Course_Progress_Controller( $this->namespace ),
			new Sensei_REST_API_Home_Controller(
				$this->namespace,
				$this->home_controller_mapper,
				$this->quick_links_provider,
				$this->help_provider
			),
		];

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}
}

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
	 * Sensei Home Promo Banner provider.
	 *
	 * @var Sensei_Home_Promo_Banner_Provider
	 */
	private $promo_provider;

	/**
	 * Sensei Home Tasks provider.
	 *
	 * @var Sensei_Home_Tasks_Provider
	 */
	private $tasks_provider;

	/**
	 * Sensei Home Data provider.
	 *
	 * @var Sensei_Home_Remote_Data_Provider
	 */
	private $remote_data_provider;

	/**
	 * Sensei_REST_API_Internal constructor.
	 */
	public function __construct() {
		$this->quick_links_provider = new Sensei_Home_Quick_Links_Provider();
		$this->help_provider        = new Sensei_Home_Help_Provider();
		$this->promo_provider       = new Sensei_Home_Promo_Banner_Provider();
		$this->tasks_provider       = new Sensei_Home_Tasks_Provider();
		$this->remote_data_provider   = new Sensei_Home_Remote_Data_Provider( 'sensei-lms' );
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
				$this->remote_data_provider,
				$this->quick_links_provider,
				$this->help_provider,
				$this->promo_provider,
				$this->tasks_provider
			),
		];

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}
}

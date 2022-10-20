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
	 * Sensei Home News provider.
	 *
	 * @var Sensei_Home_News_Provider
	 */
	private $news_provider;

	/**
	 * Sensei Home Guides provider.
	 *
	 * @var Sensei_Home_Guides_Provider
	 */
	private $guides_provider;

	/**
	 * Sensei Notices provider.
	 *
	 * @var Sensei_Home_Notices_Provider
	 */
	private $notices_provider;

	/**
	 * Sensei_REST_API_Internal constructor.
	 */
	public function __construct() {
		$remote_data_api = Sensei_Home::instance()->get_remote_data_api();

		$this->quick_links_provider = new Sensei_Home_Quick_Links_Provider();
		$this->help_provider        = new Sensei_Home_Help_Provider();
		$this->promo_provider       = new Sensei_Home_Promo_Banner_Provider();
		$this->tasks_provider       = new Sensei_Home_Tasks_Provider();
		$this->news_provider        = new Sensei_Home_News_Provider( $remote_data_api );
		$this->guides_provider      = new Sensei_Home_Guides_Provider( $remote_data_api );
		$this->notices_provider     = new Sensei_Home_Notices_Provider( Sensei_Admin_Notices::instance(), Sensei_Home::SCREEN_ID );

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
				$this->quick_links_provider,
				$this->help_provider,
				$this->promo_provider,
				$this->tasks_provider,
				$this->news_provider,
				$this->guides_provider,
				$this->notices_provider
			),
		];

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}
}

<?php
/**
 * Sensei Home REST API.
 *
 * @package Sensei\Admin
 * @since   4.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Home REST API endpoints.
 *
 * @since 4.8.0
 */
class Sensei_REST_API_Home_Controller extends \WP_REST_Controller {

	/**
	 * Routes namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'home';

	/**
	 * Quick Links provider.
	 *
	 * @var Sensei_Home_Quick_Links_Provider
	 */
	private $quick_links_provider;

	/**
	 * Help provider.
	 *
	 * @var Sensei_Home_Help_Provider
	 */
	private $help_provider;

	/**
	 * Promo banner provider.
	 *
	 * @var Sensei_Home_Promo_Banner_Provider
	 */
	private $promo_banner_provider;

	/**
	 * Tasks provider.
	 *
	 * @var Sensei_Home_Tasks_Provider
	 */
	private $tasks_provider;

	/**
	 * News provider.
	 *
	 * @var Sensei_Home_News_Provider
	 */
	private $news_provider;

	/**
	 * Guides provider.
	 *
	 * @var Sensei_Home_Guides_Provider
	 */
	private $guides_provider;

	/**
	 * Notices provider.
	 *
	 * @var Sensei_Home_Notices_Provider
	 */
	private $notices_provider;

	/**
	 * Sensei_REST_API_Home_Controller constructor.
	 *
	 * @param string                            $api_namespace             Routes namespace.
	 * @param Sensei_Home_Quick_Links_Provider  $quick_links_provider  Quick Links provider.
	 * @param Sensei_Home_Help_Provider         $help_provider         Help provider.
	 * @param Sensei_Home_Promo_Banner_Provider $promo_banner_provider Promo banner provider.
	 * @param Sensei_Home_Tasks_Provider        $tasks_provider        Tasks provider.
	 * @param Sensei_Home_News_Provider         $news_provider         News provider.
	 * @param Sensei_Home_Guides_Provider       $guides_provider       Guides provider.
	 * @param Sensei_Home_Notices_Provider      $notices_provider      Notices provider.
	 */
	public function __construct(
		$api_namespace,
		Sensei_Home_Quick_Links_Provider $quick_links_provider,
		Sensei_Home_Help_Provider $help_provider,
		Sensei_Home_Promo_Banner_Provider $promo_banner_provider,
		Sensei_Home_Tasks_Provider $tasks_provider,
		Sensei_Home_News_Provider $news_provider,
		Sensei_Home_Guides_Provider $guides_provider,
		Sensei_Home_Notices_Provider $notices_provider
	) {
		$this->namespace             = $api_namespace;
		$this->quick_links_provider  = $quick_links_provider;
		$this->help_provider         = $help_provider;
		$this->promo_banner_provider = $promo_banner_provider;
		$this->tasks_provider        = $tasks_provider;
		$this->news_provider         = $news_provider;
		$this->guides_provider       = $guides_provider;
		$this->notices_provider      = $notices_provider;
	}

	/**
	 * Register the REST API endpoints for Home.
	 */
	public function register_routes() {
		$this->register_get_data_route();
		$this->register_mark_tasks_complete_route();
		$this->register_sensei_pro_upsell_redirect_route();
	}

	/**
	 * Check user permission for REST API access.
	 *
	 * @return bool Whether the user can access the Sensei Home REST API.
	 */
	public function can_user_access_rest_api() {
		return current_user_can( Sensei_Admin::get_top_menu_capability() );
	}

	/**
	 * Register GET / endpoint.
	 */
	public function register_get_data_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_data' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
			]
		);
	}

	/**
	 * Register POST /tasks/complete endpoint.
	 */
	public function register_mark_tasks_complete_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/tasks/complete',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'mark_tasks_completed' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
			]
		);
	}

	/**
	 * Register POST /tasks/sensei-pro-upsell-redirect endpoint.
	 */
	public function register_sensei_pro_upsell_redirect_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/sensei-pro-upsell-redirect',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'sensei_pro_upsell_redirect' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
			]
		);
	}

	/**
	 * Get data for Sensei Home frontend.
	 *
	 * @return array Setup Wizard data
	 */
	public function get_data() {
		$show_extensions        = current_user_can( 'activate_plugins' ) && current_user_can( 'update_plugins' );
		$can_user_manage_sensei = current_user_can( 'manage_sensei' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Sensei capability.

		$data = [
			'news'            => $this->news_provider->get(),
			'guides'          => $this->guides_provider->get(),
			'show_extensions' => $show_extensions,
			'notices'         => $this->notices_provider->get(),
		];

		if ( $can_user_manage_sensei ) {
			$data['quick_links']  = $this->quick_links_provider->get();
			$data['help']         = $this->help_provider->get();
			$data['promo_banner'] = $this->promo_banner_provider->get();
			$data['tasks']        = $this->tasks_provider->get();
		}

		return $data;
	}

	/**
	 * Mark tasks list as fully completed for the first time.
	 *
	 * @return array
	 */
	public function mark_tasks_completed() {
		$this->tasks_provider->mark_as_completed( true );
		return [ 'success' => true ];
	}

	/**
	 * Redirect to the Sensei Pro upsell page.
	 *
	 * @return void Redirects to the Sensei Pro upsell page.
	 */
	public function sensei_pro_upsell_redirect() {
		Sensei_Home_Task_Pro_Upsell::mark_completed_and_redirect();
	}
}

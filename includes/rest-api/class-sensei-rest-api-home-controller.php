<?php
/**
 * Sensei Home REST API.
 *
 * @package Sensei\Admin
 * @since   $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Home REST API endpoints.
 *
 * @since $$next-version$$
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
	 * Mapper.
	 *
	 * @var Sensei_REST_API_Home_Controller_Mapper
	 */
	private $mapper;

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
	 * Sensei Pro Promo provider.
	 *
	 * @var Sensei_Home_Sensei_Pro_Promo_Provider
	 */
	private $pro_promo_provider;


	/**
	 * Sensei_REST_API_Home_Controller constructor.
	 *
	 * @param string                                 $namespace Routes namespace.
	 * @param Sensei_REST_API_Home_Controller_Mapper $mapper Sensei Home REST API mapper.
	 * @param Sensei_Home_Quick_Links_Provider       $quick_links_provider Quick Links provider.
	 * @param Sensei_Home_Help_Provider              $help_provider Help provider.
	 * @param Sensei_Home_Sensei_Pro_Promo_Provider  $pro_promo_provider Sensei Pro Promo provider.
	 */
	public function __construct(
		$namespace,
		Sensei_REST_API_Home_Controller_Mapper $mapper,
		Sensei_Home_Quick_Links_Provider $quick_links_provider,
		Sensei_Home_Help_Provider $help_provider,
		Sensei_Home_Sensei_Pro_Promo_Provider $pro_promo_provider
	) {
		$this->namespace            = $namespace;
		$this->mapper               = $mapper;
		$this->quick_links_provider = $quick_links_provider;
		$this->help_provider        = $help_provider;
		$this->pro_promo_provider   = $pro_promo_provider;
	}

	/**
	 * Register the REST API endpoints for Home.
	 */
	public function register_routes() {
		$this->register_get_data_route();
	}

	/**
	 * Check user permission for REST API access.
	 *
	 * @return bool Whether the user can access the Sensei Home REST API.
	 */
	public function can_user_access_rest_api() {
		return current_user_can( 'manage_sensei' );
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
	 * Get data for Sensei Home frontend.
	 *
	 * @return array Setup Wizard data
	 */
	public function get_data() {

		return [
			'tasks_list'            => [
				'tasks' => [
					// TODO: Generate based on Setup Wizard data + site info.
					[
						'title' => 'Set up Course Site',
						'done'  => true,
						'url'   => null,
						'image' => 'http://...', // Optional image to be used by the frontend.
					],
					[
						'title' => 'Create your first Course',
						'done'  => false,
						'url'   => '/wp-admin/edit.php?post_type=course',
						'image' => 'http://...', // Optional image to be used by the frontend.
					],
					[
						'title' => 'Configure Learning Mode',
						'done'  => false,
						'url'   => '/wp-admin/edit.php?post_type=course&page=sensei-settings#course-settings',
						'image' => 'http://...', // Optional image to be used by the frontend.
					],
					[
						'title' => 'Publish your first Course',
						'done'  => false,
						'url'   => '???',
						'image' => 'http://...', // Optional image to be used by the frontend.
					],
				],
			],
			'quick_links'           => $this->mapper->map_quick_links( $this->quick_links_provider->get() ),
			'help'                  => $this->mapper->map_help( $this->help_provider->get() ),
			'guides'                => [
				// TODO: Load from https://senseilms.com/wp-json/senseilms-home/1.0/{sensei-lms|sensei-pro|interactive-blocks}.json.
				'items'    => [
					[
						'title' => 'How to Sell Online Courses',
						'url'   => 'http://...',
					],
					[
						'title' => 'How to Creating Video Courses',
						'url'   => 'http://...',
					],
					[
						'title' => 'How to Choose the Right Hosting Provider',
						'url'   => 'http://...',
					],
				],
				'more_url' => 'http://senseilms.com/category/guides/',
			],
			'news'                  => [
				// TODO: Load from https://senseilms.com/wp-json/senseilms-home/1.0/{sensei-lms|sensei-pro|interactive-blocks}.json.
				'items'    => [
					[
						'title' => 'Introducing Interactive Videos For WordPress',
						'date'  => '2022-08-31', // Localized for user.
						'url'   => 'http://senseilms.com/inroducing-interactive-videos/',
					],
					[
						'title' => 'New Block Visibility, Scheduled Content, and Group Features',
						'date'  => '2022-08-09', // Localized for user.
						'url'   => 'http://senseilms.com/conditional-content/',
					],
				],
				'more_url' => 'https://senseilms.com/blog/',
			],
			'extensions'            => [
				// TODO: Load from https://senseilms.com/wp-json/senseilms-home/1.0/{sensei-lms|sensei-pro}.json.
				[
					'title'        => 'Sensei LMS Post to Course Creator',
					'image'        => 'http://senseilms.com/wp-content/uploads/2022/02/sensei-post-to-course-80x80.png',
					'description'  => 'Turn your blog posts into online courses.',
					'price'        => 0,
					'product_slug' => 'sensei-post-to-course', // To be used with the installation function `Sensei_Setup_Wizard::install_extensions`.
					'more_url'     => 'http://senseilms.com/product/sensei-lms-post-to-course-creator/',
				],
			],
			'show_sensei_pro_promo' => $this->pro_promo_provider->get(),
			'notifications'         => [
				[
					'heading'     => null, // Not needed for the moment.
					'message'     => 'Your Sensei Pro license expires on 12.09.2022.',
					'actions'     => [
						[
							'label' => 'Update now',
							'url'   => 'https://...',
						],
					],
					'info_link'   => [
						'label' => 'What\'s new',
						'url'   => 'https://...',
					],
					'level'       => 'error', // One of: info, warning, error.
					'dismissible' => false, // The default value is true.
				],
				[
					'heading'     => null, // Not needed for the moment.
					'message'     => 'Good news, reminder to update to latest version',
					'actions'     => [
						[
							'label' => 'Update now',
							'url'   => 'https://...',
						],
					],
					'info_link'   => [
						'label' => 'Link for more information',
						'url'   => 'https://...',
					],
					'level'       => 'info', // One of: info, warning, error.
					'dismissible' => true, // The default value is true.
				],
			],
		];
	}
}

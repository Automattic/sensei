<?php
/**
 * Setup Wizard REST API.
 *
 * @package Sensei\Setup_Wizard
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Setup Wizard REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_REST_API_Setup_Wizard_Controller extends \WP_REST_Controller {

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
	protected $rest_base = 'setup-wizard';


	/**
	 * Main Setup Wizard instance.
	 *
	 * @var Sensei_Setup_Wizard
	 */
	private $setup_wizard;

	/**
	 * Available 'purpose' options.
	 */
	const PURPOSES = [ 'sell_courses', 'provide_certification', 'educate_students', 'train_employees', 'other' ];

	/**
	 * Available 'feature' options.
	 */
	const FEATURES = [ 'woocommerce', 'sensei-certificates' ];

	/**
	 * Sensei_REST_API_Setup_Wizard_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace    = $namespace;
		$this->setup_wizard = Sensei_Setup_Wizard::instance();
	}

	/**
	 * Register the REST API endpoints for Setup Wizard.
	 */
	public function register_routes() {
		$this->register_get_data_route();
		$this->register_submit_welcome_route();
		$this->register_submit_purpose_route();
		$this->register_submit_theme_route();
		$this->register_submit_tracking_route();
		$this->register_submit_features_route();
		$this->register_complete_wizard_route();
		$this->register_setup_wizard_settings();
	}

	/**
	 * Check user permission for REST API access.
	 *
	 * @return bool Whether the user can access the Setup Wizard REST API.
	 */
	public function can_user_access_rest_api() {
		return current_user_can( 'manage_sensei' );
	}

	/**
	 * Check user permission for install plugins.
	 *
	 * @return bool Whether the user can install plugins.
	 */
	public function can_user_install_plugins() {
		return current_user_can( 'manage_sensei' ) && current_user_can( 'install_plugins' );
	}

	/**
	 * Register /welcome endpoint.
	 */
	public function register_submit_welcome_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/welcome',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'submit_welcome' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
			]
		);
	}

	/**
	 * Register /purpose endpoint.
	 */
	public function register_submit_purpose_route() {

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/purpose',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'submit_purpose' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
					'args'                => [
						'purpose'  => [
							'type'       => 'object',
							'properties' => [
								'selected' => [
									'required' => true,
									'type'     => 'array',
									'items'    => [
										'type' => 'string',
										'enum' => self::PURPOSES,
									],
								],
								'other'    => [
									'required' => true,
									'type'     => 'string',
								],
							],
						],
						'features' => [
							'type'       => 'object',
							'properties' => [
								'selected' => [
									'required' => true,
									'type'     => 'array',
									'items'    => [
										'type' => 'string',
										'enum' => self::FEATURES,
									],
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Register /theme endpoint.
	 */
	public function register_submit_theme_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/theme',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'submit_theme' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
					'args'                => [
						'theme' => [
							'type'       => 'object',
							'properties' => [
								'install_sensei_theme' => [
									'required' => true,
									'type'     => 'boolean',
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Register /tracking endpoint.
	 */
	public function register_submit_tracking_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/tracking',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'submit_tracking' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
					'args'                => [
						'tracking' => [
							'type'       => 'object',
							'properties' => [
								'usage_tracking' => [
									'required' => true,
									'type'     => 'boolean',
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Register /features endpoint.
	 *
	 * @since 4.8.0 It just completes the setup wizard after the features were installed.
	 */
	public function register_submit_features_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/features',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'submit_features' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
			]
		);
	}

	/**
	 * Register /features-installation endpoint.
	 *
	 * @deprecated 4.8.0
	 */
	public function register_submit_features_installation_route() {
		_deprecated_function( __METHOD__, '4.8.0' );

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/features-installation',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'submit_features_installation' ],
					'permission_callback' => [ $this, 'can_user_install_plugins' ],
					'args'                => [
						'selected' => [
							'required' => true,
							'type'     => 'array',
							'items'    => [
								'type' => 'string',
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Register /ready endpoint.
	 */
	public function register_complete_wizard_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/ready',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'complete_setup_wizard' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
			]
		);
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
				'schema' => [ $this, 'get_schema' ],
			]
		);
	}

	/**
	 * Register setup wizard user data option in the settings REST API endpoint.
	 *
	 * @since 4.11.2
	 */
	public function register_setup_wizard_settings() {

		register_setting(
			'options',
			Sensei_Setup_Wizard::USER_DATA_OPTION,
			[
				'type'         => 'object',
				'show_in_rest' => [
					'schema' => [
						'properties' => [
							'features'  => $this->get_features_schema(),
							'theme'     => $this->get_theme_schema(),
							'purpose'   => $this->get_purpose_schema(),
							'tracking'  => $this->get_tracking_schema(),
							'__version' => [
								'type'     => 'integer',
								'required' => false,
							],
						],
					],
				],
			]
		);

		add_filter( 'rest_pre_update_setting', [ $this, 'update_setup_wizard_settings' ], 10, 3 );
	}

	/**
	 * Update setup wizard user data option when it's set via the REST API.
	 * Ensures the option is complete with the default values if not set.
	 *
	 * @hooked rest_pre_update_setting
	 * @access private
	 *
	 * @param bool   $updated Whether to override the default behavior for updating the
	 *                        value of a setting.
	 * @param string $name   Setting name (as shown in REST API responses).
	 * @param mixed  $value  Updated setting value.
	 */
	public function update_setup_wizard_settings( $updated, $name, $value ) {
		if ( Sensei_Setup_Wizard::USER_DATA_OPTION !== $name ) {
			return $updated;
		}
		if ( ! empty( $value ) ) {
			$default = Sensei_Setup_Wizard::instance()->get_wizard_user_data();
			$value   = wp_parse_args( $value, $default );

			update_option( Sensei_Setup_Wizard::USER_DATA_OPTION, $value );
		}

		return true;
	}

	/**
	 * Register GET / endpoint for features step.
	 *
	 * @deprecated 4.8.0
	 */
	public function register_get_features_route() {
		_deprecated_function( __METHOD__, '4.8.0' );

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/features',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_features_data' ],
					'permission_callback' => [ $this, 'can_user_access_rest_api' ],
				],
				'schema' => [ $this, 'get_features_schema' ],
			]
		);
	}

	/**
	 * Get data for Setup Wizard frontend.
	 *
	 * @return array Setup Wizard data
	 */
	public function get_data() {
		$user_data = $this->setup_wizard->get_wizard_user_data();

		return [
			'purpose'    => [
				'selected' => $user_data['purpose']['selected'],
				'other'    => $user_data['purpose']['other'],
			],
			'theme'      => [
				'install_sensei_theme' => $user_data['theme']['install_sensei_theme'],
			],
			'tracking'   => [
				'usage_tracking' => Sensei()->usage_tracking->get_tracking_enabled(),
			],
			'newsletter' => $this->setup_wizard->get_mailing_list_form_data(),
			'features'   => $this->get_features_data( $user_data ),
		];
	}

	/**
	 * Get features data for Setup Wizard frontend.
	 *
	 * @since 4.8.0 It doesn't add the wccom connection data anymore.
	 *
	 * @param mixed $user_data Optional user data param. If it's not set, it will be fetched.
	 *
	 * @return array Features data
	 */
	public function get_features_data( $user_data = null ) {
		$clear_active_plugins_cache = false;

		if ( ! $user_data || ! isset( $user_data['features'] ) ) {
			$user_data = $this->setup_wizard->get_wizard_user_data();

			// There is a problem with the `active_plugins` option cache in the first
			// fetch after starting the installation. This argument fixes that for the
			// cases where the `/features` endpoint is called.
			$clear_active_plugins_cache = true;
		}

		return [
			'selected' => $user_data['features']['selected'],
			'options'  => Sensei_Extensions::instance()->get_extensions_and_woocommerce( 'plugin' ),
		];
	}

	/**
	 * Mark the given step as completed.
	 *
	 * @deprecated 4.8.0
	 *
	 * @param string $step Step.
	 *
	 * @return bool Success.
	 */
	public function mark_step_complete( $step ) {
		_deprecated_function( __METHOD__, '4.8.0' );

		return $this->setup_wizard->update_wizard_user_data(
			[
				'steps' => array_unique( array_merge( $this->setup_wizard->get_wizard_user_data( 'steps' ), [ $step ] ) ),
			]
		);
	}

	/**
	 * Get features schema.
	 *
	 * @return array Schema object.
	 */
	public function get_features_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'selected' => [
					'description' => __( 'Slug of extensions to be installed.', 'sensei-lms' ),
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
					],
				],
				'options'  => [
					'description' => __( 'Sensei extensions.', 'sensei-lms' ),
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
					],
				],
			],
		];
	}

	/**
	 * Get themes schema.
	 *
	 * @return array Schema object.
	 */
	public function get_theme_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'install_sensei_theme' => [
					'description' => __( 'Whether user wants to install Sensei theme.', 'sensei-lms' ),
					'type'        => 'boolean',
				],
			],
		];
	}

	/**
	 * Get purpose schema.
	 *
	 * @return array Schema object.
	 */
	public function get_purpose_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'selected' => [
					'required' => true,
					'type'     => 'array',
					'items'    => [
						'type' => 'string',
						'enum' => self::PURPOSES,
					],
				],
				'other'    => [
					'required' => true,
					'type'     => 'string',
				],
			],
		];
	}

	/**
	 * Get tracking schema.
	 *
	 * @return array Schema object.
	 */
	public function get_tracking_schema() {
		return [
			'required'   => false,
			'type'       => 'object',
			'properties' => [
				'usage_tracking' => [
					'description' => __( 'Usage tracking preference given by the site owner.', 'sensei-lms' ),
					'type'        => 'boolean',
				],
			],
		];
	}

	/**
	 * Schema for the endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'features' => $this->get_features_schema(),
				'purpose'  => $this->get_purpose_schema(),
				'theme'    => $this->get_theme_schema(),
				'tracking' => $this->get_tracking_schema(),
			],
		];
	}

	/**
	 * Submit form on welcome step.
	 *
	 * @param array $data Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_welcome( $data ) {
		$this->setup_wizard->pages->create_pages();

		return true;
	}

	/**
	 * Submit form on purpose step.
	 *
	 * @since 4.8.0 Accepts features.
	 *
	 * @param array $form Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_purpose( $form ) {
		$json = $form->get_json_params();

		if ( ! in_array( 'other', $json['purpose']['selected'], true ) ) {
			$json['purpose']['other'] = '';
		}

		return $this->setup_wizard->update_wizard_user_data( $json );
	}

	/**
	 * Submit form on theme step.
	 *
	 * @param array $form Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_theme( $form ) {
		$json = $form->get_json_params();

		return $this->setup_wizard->update_wizard_user_data( $json );
	}

	/**
	 * Submit form on tracking step.
	 *
	 * @param array $data Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_tracking( $data ) {
		Sensei()->usage_tracking->set_tracking_enabled( (bool) $data['tracking']['usage_tracking'] );
		Sensei()->usage_tracking->send_usage_data();

		$setup_purpose_data = $this->setup_wizard->get_wizard_user_data( 'purpose' );
		if ( $setup_purpose_data ) {
			sensei_log_event(
				'setup_wizard_purpose_continue',
				[
					'purpose'         => join( ',', $setup_purpose_data['selected'] ?? [] ),
					'purpose_details' => $setup_purpose_data['other'] ?? '',
				]
			);
		}

		$theme_data = $this->setup_wizard->get_wizard_user_data( 'theme' );
		if ( $theme_data['install_sensei_theme'] ) {
			sensei_log_event(
				'setup_wizard_install_theme',
				[
					'theme' => 'course',
				]
			);
		}

		return true;
	}

	/**
	 * Submit form on features step.
	 *
	 * @since 4.8.0 Complete the setup wizard.
	 *
	 * @return bool Success.
	 */
	public function submit_features() {
		$this->setup_wizard->finish_setup_wizard();

		return true;
	}

	/**
	 * Submit features installation step.
	 *
	 * @deprecated 4.8.0
	 *
	 * @param array $form Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_features_installation( $form ) {
		_deprecated_function( __METHOD__, '4.8.0' );

		$this->setup_wizard->install_extensions( $form['selected'] );

		return true;
	}

	/**
	 * Complete setup wizard
	 *
	 * @deprecated 4.8.0
	 */
	public function complete_setup_wizard() {
		_deprecated_function( __METHOD__, '4.8.0' );

		$this->setup_wizard->finish_setup_wizard();

		return true;
	}

}

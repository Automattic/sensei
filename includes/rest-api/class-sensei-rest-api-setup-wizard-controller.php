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
	const PURPOSES = [ 'share_knowledge', 'generate_income', 'promote_business', 'provide_certification', 'train_employees', 'educate_students', 'other' ];

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
		$this->register_get_features_route();
		$this->register_submit_welcome_route();
		$this->register_submit_purpose_route();
		$this->register_submit_features_route();
		$this->register_submit_features_installation_route();
		$this->register_complete_wizard_route();
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
					'args'                => [
						'usage_tracking' => [
							'required' => true,
							'type'     => 'boolean',
						],
					],
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
			]
		);
	}

	/**
	 * Register /features endpoint.
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
	 * Register /features-installation endpoint.
	 */
	public function register_submit_features_installation_route() {
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
	 * Register GET / endpoint for features step.
	 */
	public function register_get_features_route() {
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
			'completedSteps' => $user_data['steps'],
			'welcome'        => [
				'usage_tracking' => Sensei()->usage_tracking->get_tracking_enabled(),
			],
			'purpose'        => [
				'selected' => $user_data['purpose']['selected'],
				'other'    => $user_data['purpose']['other'],
			],
			'features'       => $this->get_features_data( $user_data ),
			'ready'          => $this->setup_wizard->get_mailing_list_form_data(),
		];
	}


	/**
	 * Get features data for Setup Wizard frontend.
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
			'options'  => $this->setup_wizard->get_sensei_extensions( $clear_active_plugins_cache ),
			'wccom'    => Sensei_Utils::get_woocommerce_connect_data(),
		];
	}

	/**
	 * Mark the given step as completed.
	 *
	 * @param string $step Step.
	 *
	 * @return bool Success.
	 */
	public function mark_step_complete( $step ) {
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
					'description' => __( 'Slugs of extensions selected by the site owner.', 'sensei-lms' ),
					'type'        => 'array',
				],
				'options'  => [
					'description' => __( 'Sensei extensions.', 'sensei-lms' ),
					'type'        => 'array',
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
				'completedSteps' => [
					'description' => __( 'Completed steps.', 'sensei-lms' ),
					'type'        => 'array',
					'readonly'    => true,
				],
				'welcome'        => [
					'type'       => 'object',
					'properties' => [
						'usage_tracking' => [
							'description' => __( 'Usage tracking preference given by the site owner.', 'sensei-lms' ),
							'type'        => 'boolean',
						],
					],
				],
				'features'       => $this->get_features_schema(),
				'purpose'        => [
					'type'       => 'object',
					'properties' => [
						'selected' => [
							'description' => __( 'Purposes selected by the site owner.', 'sensei-lms' ),
							'type'        => 'array',
						],
						'other'    => [
							'description' => __( 'Other free-text purpose.', 'sensei-lms' ),
							'type'        => 'string',
						],
					],
				],
				'ready'          => [
					'type' => 'object',
				],
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
		$this->mark_step_complete( 'welcome' );
		$this->setup_wizard->pages->create_pages();

		Sensei()->usage_tracking->set_tracking_enabled( (bool) $data['usage_tracking'] );
		Sensei()->usage_tracking->send_usage_data();

		return true;
	}

	/**
	 * Submit form on purpose step.
	 *
	 * @param array $form Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_purpose( $form ) {

		$this->mark_step_complete( 'purpose' );

		$purpose_data = [
			'selected' => $form['selected'],
			'other'    => ( in_array( 'other', $form['selected'], true ) ? $form['other'] : '' ),
		];

		sensei_log_event(
			'setup_wizard_purpose_continue',
			[
				'purpose'         => join( ',', $purpose_data['selected'] ),
				'purpose_details' => $purpose_data['other'],
			]
		);

		return $this->setup_wizard->update_wizard_user_data(
			[
				'purpose' => $purpose_data,
			]
		);
	}

	/**
	 * Submit form on features step.
	 *
	 * @param array $form Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_features( $form ) {

		$this->mark_step_complete( 'features' );

		return $this->setup_wizard->update_wizard_user_data(
			[
				'features' => [
					'selected' => $form['selected'],
				],
			]
		);
	}

	/**
	 * Submit features installation step.
	 *
	 * @param array $form Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_features_installation( $form ) {
		$this->setup_wizard->install_extensions( $form['selected'] );

		return true;
	}

	/**
	 * Complete setup wizard
	 */
	public function complete_setup_wizard() {
		$this->mark_step_complete( 'ready' );
		$this->setup_wizard->finish_setup_wizard();

		return true;
	}

}

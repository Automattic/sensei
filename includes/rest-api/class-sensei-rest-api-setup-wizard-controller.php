<?php
/**
 * Setup Wizard REST API.
 *
 * @package Sensei\SetupWizard
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
	 * @var Sensei_Onboarding
	 */
	private $setup_wizard;

	/**
	 * Available 'purpose' options.
	 */
	const PURPOSES = [ 'share_knowledge', 'generate_income', 'promote_business', 'provide_certification', 'train_employees', 'other' ];

	/**
	 * Sensei_REST_API_Setup_Wizard_Controller constructor.
	 *
	 * @param string $namespace Routes namespace.
	 */
	public function __construct( $namespace ) {
		$this->namespace    = $namespace;
		$this->setup_wizard = Sensei_Onboarding::instance();
	}

	/**
	 * Register the REST API endpoints for Setup Wizard.
	 */
	public function register_routes() {

		$this->register_get_data_route();
		$this->register_submit_welcome_route();
		$this->register_submit_purpose_route();
		$this->register_submit_features_route();
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
								'enum' => $this->setup_wizard->plugin_slugs,
							],
						],
					],
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
	 * Get data for Setup Wizard frontend.
	 *
	 * @return array Setup Wizard data
	 */
	public function get_data() {

		$user_data = $this->setup_wizard->get_wizard_user_data();

		return [
			'completed_steps' => $user_data['steps'],
			'welcome'         => [
				'usage_tracking' => Sensei()->usage_tracking->get_tracking_enabled(),
			],
			'purpose'         => [
				'selected' => $user_data['purpose']['selected'],
				'other'    => $user_data['purpose']['other'],
			],
			'features'        => [
				'selected' => $user_data['features'],
				'options'  => $this->setup_wizard->get_sensei_extensions(),
			],
			'ready'           => [],
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
	 * Schema for the endpoint.
	 *
	 * @return array Schema object.
	 */
	public function get_schema() {
		return [
			'type'       => 'object',
			'properties' => [
				'completed_steps' => [
					'description' => __( 'Completed steps.', 'sensei-lms' ),
					'type'        => 'array',
					'readonly'    => true,
				],
				'welcome'         => [
					'type'       => 'object',
					'properties' => [
						'usage_tracking' => [
							'description' => __( 'Usage tracking preference given by the site owner.', 'sensei-lms' ),
							'type'        => 'boolean',
						],
					],
				],
				'features'        => [
					'type'       => 'object',
					'properties' => [
						'selected' => [
							'description' => __( 'Slugs of plugins selected by the site owner.', 'sensei-lms' ),
							'type'        => 'array',
						],
						'options'  => [
							'description' => __( 'Sensei extensions.', 'sensei-lms' ),
							'type'        => 'array',
						],
					],
				],
				'purpose'         => [
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
				'ready'           => [
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
		Sensei()->usage_tracking->set_tracking_enabled( (bool) $data['usage_tracking'] );
		$this->setup_wizard->pages->create_pages();

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

		return $this->setup_wizard->update_wizard_user_data(
			[
				'purpose' => [
					'selected' => $form['selected'],
					'other'    => ( in_array( 'other', $form['selected'], true ) ? $form['other'] : '' ),
				],
			]
		);
	}

	/**
	 * Submit form on features step.
	 *
	 * @param array $data Form data.
	 *
	 * @return bool Success.
	 */
	public function submit_features( $data ) {

		$this->mark_step_complete( 'features' );

		return $this->setup_wizard->update_wizard_user_data(
			[
				'features' => $data['selected'],
			]
		);
	}

}

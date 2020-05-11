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
class Sensei_Setup_Wizard_API {

	/**
	 * Main Setup Wizard instance.
	 *
	 * @var Sensei_Onboarding
	 */
	private $setupwizard;

	const PURPOSES = [ 'share_knowledge', 'generate_income', 'promote_business', 'provide_certification', 'train_employees', 'other' ];

	/**
	 * Sensei_Setup_Wizard_API constructor.
	 *
	 * @param Sensei_Onboarding $setupwizard Setup Wizard instance.
	 */
	public function __construct( $setupwizard ) {
		$this->setupwizard = $setupwizard;
	}

	/**
	 * Register the REST API endpoints for Setup Wizard.
	 */
	public function register() {
		$common = [
			'endpoint' => [
				'permission_callback' => [ $this, 'can_user_access_rest_api' ],
			],
			'arg'      => [
				'validate_callback' => 'rest_validate_request_arg',
			],
		];

		$progress_endpoint = [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_progress' ],
			],
		];

		$welcome_endpoint = [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_welcome' ],
			],
			[
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => [ $this, 'submit_welcome' ],
				'args'     => [
					'usage_tracking' => [
						'required' => true,
						'type'     => 'boolean',
					],
				],
			],
		];

		$purpose_endpoint = [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_purpose' ],
			],
			[
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => [ $this, 'submit_purpose' ],
				'args'     => [
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
		];

		$features_endpoint = [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_features' ],
			],
			[
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => [ $this, 'submit_features' ],
				'args'     => [
					'selected' => [
						'required' => true,
						'type'     => 'array',
						'items'    => [
							'type' => 'string',
							'enum' => $this->setupwizard->plugin_slugs,
						],
					],
				],
			],
		];

		Sensei_REST_API_Helper::register_endpoints(
			'sensei-internal/v1',
			[
				'setup-wizard/progress' => $progress_endpoint,
				'setup-wizard/welcome'  => $welcome_endpoint,
				'setup-wizard/purpose'  => $purpose_endpoint,
				'setup-wizard/features' => $features_endpoint,
			],
			$common
		);
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
	 * Get completed steps.
	 *
	 * @return mixed List of steps completed.
	 */
	public function get_progress() {
		return [
			'steps' => $this->setupwizard->get_wizard_user_data( 'steps' ),
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
		return $this->setupwizard->update_wizard_user_data(
			[
				'steps' => array_unique( array_merge( $this->setupwizard->get_wizard_user_data( 'steps' ), [ $step ] ) ),
			]
		);
	}

	/**
	 * Welcome step data.
	 *
	 * @return array Data used on purpose step.
	 */
	public function get_welcome() {
		return [
			'usage_tracking' => Sensei()->usage_tracking->get_tracking_enabled(),
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
		$this->setupwizard->pages->create_pages();

		return true;
	}

	/**
	 * Purpose step data.
	 *
	 * @return array Data used on purpose step.
	 */
	public function get_purpose() {
		$data = $this->setupwizard->get_wizard_user_data( 'purpose' );

		return [
			'selected' => $data['selected'],
			'other'    => $data['other'],
		];
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

		return $this->setupwizard->update_wizard_user_data(
			[
				'purpose'       => $form['selected'],
				'purpose_other' => ( in_array( 'other', $form['selected'], true ) ? $form['other'] : '' ),
			]
		);
	}


	/**
	 * Feature step data.
	 *
	 * @return array Data used on features page.
	 */
	public function get_features() {

		$data = $this->setupwizard->get_wizard_user_data( 'features' );

		return [
			'selected' => $data,
			'plugins'  => $this->setupwizard->plugin_slugs,
		];
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

		return $this->setupwizard->update_wizard_user_data(
			[
				'features' => $data['selected'],
			]
		);
	}


}

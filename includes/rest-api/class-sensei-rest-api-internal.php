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
	 *
	 * @since $$next-version$$ Added `$namespace_override` and `$rest_base_prefix` to allow changing the REST endpoint URLs.
	 *                         Notice that when it's changed, the fetch functions should also be overrided.
	 *
	 * @param WP_REST_Server|bool|string $namespace_override It receives the `WP_REST_Server` when called through the `rest_api_init` hook.
	 *                                                       but it can be set with a `string` to override the default namespace. `false` is the default value to be ignored.
	 * @param string                     $rest_base_prefix   A prefix for the `$rest_base`, in the controllers.
	 */
	public function register( $namespace_override = false, $rest_base_prefix = '' ) {
		if ( is_string( $namespace_override ) ) {
			$this->namespace = $namespace_override;
		}

		$this->controllers = [
			new Sensei_REST_API_Setup_Wizard_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Import_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Export_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Course_Structure_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Lesson_Quiz_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Question_Options_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Extensions_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Theme_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Send_Message_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Course_Students_Controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Course_Progress_Controller( $this->namespace, $rest_base_prefix ),
			Sensei_Home::instance()->get_rest_api_controller( $this->namespace, $rest_base_prefix ),
			new Sensei_REST_API_Course_Utils_Controller( $this->namespace, $rest_base_prefix ),
		];

		if ( Sensei()->tour ) {
			$this->controllers[] = new Sensei\Admin\Tour\Sensei_REST_API_Tour_Controller( $this->namespace, Sensei()->tour );
		}

		foreach ( $this->controllers as $controller ) {
			$controller->register_routes();
		}
	}
}

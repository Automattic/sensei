<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @deprecated 3.11.0
 */
class Sensei_REST_API_Endpoint_Modules extends Sensei_REST_API_Controller {

	protected $base               = '/modules';
	protected $domain_model_class = 'Sensei_Domain_Models_Module';

	/**
	 * @deprecated 3.11.0
	 */
	public function register() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$prefix = $this->api->get_api_prefix();
		register_rest_route(
			$prefix,
			$this->base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
		register_rest_route(
			$prefix,
			$this->base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * @deprecated 3.11.0
	 */
	public function get_items( $request ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( ! headers_sent() ) {
			header( 'Warning: 299 - Deprecated API' );
		}

		$item_id = isset( $request['id'] ) ? absint( $request['id'] ) : null;

		if ( null === $item_id ) {
			$models = $this->factory->all();
			$data   = $this->prepare_data_transfer_object( $models );
			return $this->succeed( $data );
		}

		$course = $this->factory->find_one_by_id( $item_id );
		if ( empty( $course ) ) {
			return $this->not_found( __( 'Module not found', 'sensei-lms' ) );
		}

		return $this->succeed( $this->prepare_data_transfer_object( $course ) );
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->admin_permissions_check( $request );
	}

	private function admin_permissions_check( $request ) {
		// we are only going to allow admins to access the rest api for now
		return Sensei()->feature_flags->is_enabled( 'rest_api_v1_skip_permissions' ) || current_user_can( 'manage_sensei' );
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @param $model Sensei_Domain_Models_Model_Abstract
	 * @return array
	 */
	protected function add_links( $model ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$helper = $this->api->get_helper();
		return array(
			'self'       => array(
				array(
					'href' => esc_url( $helper->base_namespace_url() . $this->base . '/' . $model->get_id() ),
				),
			),
			'collection' => array(
				array(
					'href' => esc_url( $helper->base_namespace_url() . $this->base ),
				),
			),
		);
	}
}

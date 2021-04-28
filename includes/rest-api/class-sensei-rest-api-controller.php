<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @deprecated 3.11.0
 */
class Sensei_REST_API_Controller extends WP_REST_Controller {
	const HTTP_CREATED   = 201;
	const HTTP_SUCCESS   = 200;
	const BAD_REQUEST    = 400;
	const HTTP_NOT_FOUND = 404;

	/**
	 * @var Sensei_REST_API_V1
	 */
	protected $api;
	/**
	 * @var string the endpoint base
	 */
	protected $base = null;
	/**
	 * @var string the domain model class this endpoint serves
	 */
	protected $domain_model_class = null;
	/**
	 * @var Sensei_Domain_Models_Factory
	 */
	protected $factory = null;

	/**
	 * Sensei_REST_API_Controller constructor.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param $api Sensei_REST_API_V1
	 * @throws Sensei_Domain_Models_Exception
	 */
	public function __construct( $api ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->api = $api;
		if ( empty( $this->base ) ) {
			throw new Sensei_Domain_Models_Exception( 'Need to put a string with a backslash in $base' );
		}
		if ( ! empty( $this->domain_model_class ) ) {
			$this->factory = Sensei_Domain_Models_Registry::get_instance()
				->get_factory( $this->domain_model_class );
		}
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @param $entity array|Sensei_Domain_Models_Model_Collection|Sensei_Domain_Models_Model_Abstract
	 * @return array
	 */
	protected function prepare_data_transfer_object( $entity ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( is_array( $entity ) ) {
			return $entity;
		}

		if ( is_a( $entity, 'Sensei_Domain_Models_Model_Collection' ) ) {
			$results = array();
			foreach ( $entity->get_items() as $model ) {
				$results[] = $this->model_to_data_transfer_object( $model );
			}
			return $results;
		}

		if ( is_a( $entity, 'Sensei_Domain_Models_Model_Abstract' ) ) {
			return $this->model_to_data_transfer_object( $entity );
		}

		return $entity;
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @param $model Sensei_Domain_Models_Model_Abstract
	 * @return array
	 */
	protected function model_to_data_transfer_object( $model ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$result = array();
		foreach ( $model->get_data_transfer_object_field_mappings() as $mapping_name => $field_name ) {
			$value                   = $model->__get( $field_name );
			$result[ $mapping_name ] = $value;
		}
		$result['_links'] = $this->add_links( $model );
		return $result;
	}

	/**
	 * @deprecated 3.11.0
	 *
	 * @param $model
	 *
	 * @return array
	 */
	protected function add_links( $model ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return array();
	}

	/**
	 * @deprecated 3.11.0
	 */
	public function register() {
		_deprecated_function( __METHOD__, '3.11.0' );

		throw new Sensei_Domain_Models_Exception( 'override me' );
	}

	/**
	 * @deprecated 3.11.0
	 */
	protected function succeed( $data ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return new WP_REST_Response( $data, self::HTTP_SUCCESS );
	}

	/**
	 * @deprecated 3.11.0
	 */
	protected function created( $data ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return new WP_REST_Response( $data, self::HTTP_CREATED );
	}

	/**
	 * @deprecated 3.11.0
	 */
	protected function fail_with( $data ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return new WP_REST_Response( $data, self::BAD_REQUEST );
	}

	/**
	 * @deprecated 3.11.0
	 */
	protected function not_found( $message ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->respond( new WP_REST_Response( array( 'message' => $message ), self::HTTP_NOT_FOUND ) );
	}

	/**
	 * @deprecated 3.11.0
	 */
	public function respond( $thing ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return rest_ensure_response( $thing );
	}
}

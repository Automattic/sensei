<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_REST_API_Endpoint_Courses extends Sensei_REST_API_Controller {

    protected $base = '/courses';
    protected $domain_model_class = 'Sensei_Domain_Models_Course';
    protected $post_type = 'course';

    public function __construct(Sensei_REST_API_V1 $api) {
        parent::__construct($api);
        $obj = get_post_type_object( $this->post_type );
        $this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;

        $this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );
    }

    public function register() {
        $prefix = $this->api->get_api_prefix();
        register_rest_route( $prefix, $this->base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            array(
                'methods'         => WP_REST_Server::CREATABLE,
                'callback'        => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( true ),
            )
        ) );
        register_rest_route( $prefix,  $this->base . '/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
            array(
                'methods'         => WP_REST_Server::EDITABLE,
                'callback'        => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( true ),
            ),
            array(
                'methods'         => WP_REST_Server::DELETABLE,
                'callback'        => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'            => $this->get_endpoint_args_for_item_schema( true ),
            ),
        ) );
    }

    public function get_items( $request ) {
        $item_id = isset( $request['id'] ) ? absint( $request['id'] ) : null;

        if (null === $item_id ) {
            $models = $this->factory->all();
            $data = $this->prepare_data_transfer_object( $models );
            return $this->succeed( $data );
        }

        $course = $this->factory->find_one_by_id($item_id);
        if ( empty( $course ) ) {
            return $this->not_found( __( 'Course not found' ) );
        }

        return $this->succeed( $this->prepare_data_transfer_object( $course ) );
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_item( $request ) {
        $is_update = false;
        return $this->create_or_update( $request, $is_update );
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_item($request) {
        $is_update = true;
        return $this->create_or_update( $request, $is_update );
    }

    /**
     * @param WP_REST_Request $request
     * @param bool $is_update
     * @return WP_REST_Response
     */
    protected function create_or_update( $request, $is_update = false ) {
        $model_to_update = null;
        if ( $is_update ) {
            $id = isset( $request['id'] ) ? absint( $request['id'] ) : null;
            if ( ! empty( $id ) ) {
                $model_to_update = $this->factory->find_one_by_id( $id );
                if ( empty( $model_to_update ) ) {
                    return $this->not_found( __( 'Course does not exist', 'woothemes-sensei' ) );
                }
            }
        }

        if ( $is_update && $model_to_update ) {
            $course = $model_to_update->merge_updates_from_request( $request );
        } else {
            $course = $this->prepare_item_for_database( $request );
        }

        if ( is_wp_error( $course ) ) {
            $wp_err = $course;
            return $this->fail_with( $wp_err );
        }

        $validation = $course->validate();
        if ( is_wp_error( $validation )  ) {
            return $this->fail_with( $validation );
        }

        $id_or_error = $course->upsert();

        if ( is_wp_error( $id_or_error ) ) {
            return $this->fail_with( $id_or_error );
        }

        return $this->created( $this->prepare_data_transfer_object( array('id' => absint( $id_or_error ) ) ) );
    }

    public function delete_item( $request ) {
        $id = isset( $request['id'] ) ? absint( $request['id'] ) : null;
        if ( empty( $id ) ) {
            return $this->fail_with( __( 'No Course ID provided', 'woothemes-sensei' ) );
        }
        $course = $this->factory->find_one_by_id( $id );
        if ( null === $course ) {
            return $this->not_found( __( 'Course does not exist', 'woothemes-sensei' ) );
        }
        $result = $course->delete();
        return $this->succeed( $result );
    }

    /**
     * Prepare the item for create or update operation.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_Error|object $prepared_item
     */
    protected function prepare_item_for_database( $request ) {
        return $this->factory->new_from_request( $request) ;
    }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    public function get_items_permissions_check( $request ) {
        return $this->admin_permissions_check( $request );
    }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    public function create_item_permissions_check( $request ) {
        return $this->admin_permissions_check( $request );
    }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    public function delete_item_permissions_check( $request ) {
        return $this->admin_permissions_check( $request );
    }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    private function admin_permissions_check( $request ) {
        // we are only going to allow admins to access the rest api for now
        return current_user_can( 'manage_sensei' );
    }

    /**
     * @param $model Sensei_Domain_Models_Course
     * @return array
     */
    protected function add_links( $model ) {
        $helper = $this->api->get_helper();
        return array(
            'self' => array(
                array(
                    'href' => esc_url( $helper->base_namespace_url() . '/courses/' . $model->get_id() )
                )
            ),
            'collection' => array(
                array(
                    'href' => esc_url( $helper->base_namespace_url() . '/courses/' )
                )
            ),
            'author' => array(
                array(
                    'href' => esc_url( $helper->rest_url() . 'wp/v2/users/' . $model->author )
                )
            )
        );
    }

    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     * @access public
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        $fields = $this->factory->get_field_declarations();
        $properties = array();
        foreach ( $fields as $field_declaration ) {
            $properties[$field_declaration->json_name] = $field_declaration->as_item_schema_property();
        }
        $schema = array(
            '$schema'    => 'http://json-schema.org/schema#',
            'title'      => 'course',
            'type'       => 'object',
            'properties' => (array)apply_filters( 'sensei_rest_api_course_schema_properties', $properties )
        );

        return $this->add_additional_fields_schema( $schema );
    }
}
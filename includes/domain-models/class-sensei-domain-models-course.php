<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


/**
 * Class Sensei_Domain_Models_Course
 * @package Core
 */
class Sensei_Domain_Models_Course {

    protected static $post_type = 'course';
    protected $wp_entity = 'WP_Post';

    protected $required_fields = array(
        'title'
    );

    protected $json_fields = array(
        'id' => 'id',
        'title' => 'title',
        'author' => 'author',
        'content' => 'content',
        'excerpt' => 'excerpt',
        'modules' => 'modules',
        'featured' => 'featured',
        'module_order' => 'module_order',
        'video_embed' => 'video_embed'
    );

    private $data;

    /**
     * @param $object_or_id_or_data_array int|WP_Post|array
     */
    function __construct( $data = array() ) {
        if ( empty( $this->wp_entity ) ) {
            throw new Sensei_Domain_Models_Exception( 'your model declaration is missing a $wp_entity' );
        }
        $this->fields = $this->group_declared_fields_by_type( $this->declare_model_fields() );
        $this->data = array();

        if ( is_numeric( $data  ) ) {
            $model_data = $this->get_wp_entity_from_id( absint( $data ) );
        } else if ( is_array( $data ) ) {
            $model_data = $data;
        } else if ( is_a( $data, $this->wp_entity ) ) {
            $model_data = $data->to_array();
        } else {
            throw new Exception('does not understand data');
        }

        $post_array_keys = array_keys( $model_data );
        foreach ( $this->fields as $key => $field_declaration ) {
            if ( false === $field_declaration->is_field() ) {
                continue;
            }
            if ( isset( $field_declaration->map_from ) ) {
                $map_from = $field_declaration->map_from;
            } else {
                $map_from = $field_declaration->name;
            }
            if ( in_array( $map_from, $post_array_keys ) ) {
                $this->data[$key] = $model_data[$map_from];
            } else if (in_array( $key, $post_array_keys ) ) {
                $this->data[$key] = $model_data[$key];
            }
        }
    }

    protected function get_wp_entity_from_id( $id ) {
        if ( 'WP_Post' === $this->wp_entity ) {
            return get_post( absint( $id ), ARRAY_A );
        }
        throw new Error( 'Please declare the wp entity an instance of this model is serialized to' );
    }

    public function __get( $field ) {
        if (!isset( $this->fields[$field] ) ) {
            return null;
        }

        $field_declaration = $this->fields[$field];

        $field_name = $field_declaration->name;
        if ( !isset( $this->data[ $field_name ] ) ) {
            if ( $field_declaration->is_meta_field() ) {
                $value = get_post_meta( $this->get_id(), $field_declaration->map_from, true );
                $this->data[ $field_name ] = $value;
            } else if ( $field_declaration->is_derived_field() ) {
                $value = call_user_func( array( $this, $field_declaration->map_from ) );
                $this->data[ $field_name ] = $value;
            } else {
                $this->data[ $field_name ] = null;
            }
        }

        return $this->prepare_value( $field_declaration );
    }

    public function get_id() {
        return $this->id;
    }

    protected function field() {
        return new Sensei_Domain_Models_Field_Builder();
    }

    protected function meta_field() {
        return $this->field()->of_type( Sensei_Domain_Models_Field::META );
    }

    protected function derived_field() {
        return $this->field()->of_type( Sensei_Domain_Models_Field::DERIVED );
    }

    protected function declare_model_fields() {
        return array(
            $this->field()
                ->with_name( 'id' )
                ->get_from( 'ID' )
                ->with_before_return( 'cast_absint' )
                ->build(),
            $this->field()
                ->with_name( 'title' )
                ->get_from( 'post_title' )
                ->build(),
            $this->field()
                ->with_name( 'author' )
                ->get_from( 'post_author' )
                ->with_before_return( 'cast_absint' )
                ->build(),
            $this->field()
                ->with_name( 'content' )
                ->get_from( 'post_content' )
                ->build(),
            $this->field()
                ->with_name( 'excerpt' )
                ->get_from( 'post_excerpt' )
                ->build(),

            $this->derived_field()
                ->with_name( 'modules' )
                ->get_from( 'course_module_ids' )
                ->build(),
            $this->derived_field()
                ->with_name( 'lessons' )
                ->get_from( 'course_lessons' )
                ->build(),

            $this->meta_field()
                ->with_name( 'prerequisite' )
                ->get_from( '_course_prerequisite' )
                ->with_before_return( 'cast_absint' )
                ->build(),
            $this->meta_field()
                ->with_name( 'featured' )
                ->get_from( '_course_featured' )
                ->with_before_return( 'cast_bool' )
                ->build(),
            $this->meta_field()
                ->with_name( 'video_embed' )
                ->get_from( '_course_video_embed' )
                ->build(),
            $this->meta_field()
                ->with_name( 'woocommerce_product' )
                ->get_from( '_course_woocommerce_product' )
                ->with_before_return( 'cast_absint' )
                ->build(),
            $this->meta_field()
                ->with_name( 'lesson_order' )
                ->get_from( '_lesson_order' )
                ->build()
        );
    }

    protected function course_module_ids() {
        $modules = Sensei()->modules->get_course_modules( absint( $this->id ) );
        return wp_list_pluck( $modules, 'term_id' );
    }

    protected function module_order() {
        return Sensei()->modules->get_course_module_order( absint( $this->id ) );
    }

    public static function find_one_by_id( $course_id ) {
        $course_id = absint( $course_id );
        $course = get_post( $course_id );
        if (!empty( $course ) && $course->post_type === self::$post_type ) {
            return new self( $course );
        }
        return null;
    }

    public function get_json_field_mappings() {
        return $this->json_fields;
    }

    public static function all() {
        $query = new WP_Query( array(
            'post_type' => self::$post_type
        ) );
        $results = array();
        foreach ( $query->get_posts() as $course_post ) {
            $course = new self( $course_post );
            $results[] = $course;
        }
        return new Sensei_Domain_Models_Model_Collection( $results );
    }

    /**
     * @param $request
     * @return Sensei_Domain_Models_Course
     */
    public static function new_from_request( $request ) {
        $id = isset( $request['id'] ) ? absint( $request['id'] ) : null;
        $title = $request['title'];

        return new self( array(
            'id' => $id,
            'title' => $title ) );
    }

    /**
     * validate object
     * @return bool|WP_Error
     */
    public function validate() {
        $validation_errors = array();
        if ( empty( $this->title ) ) {
            $validation_errors[] = new WP_Error(
                'empty_title',
                __( 'title cannot be empty', 'woothemes-sensei' )
            );
        }
        if ( count( $validation_errors ) > 0 ) {
            return $this->validation_error( $validation_errors );
        }
        return true;
    }

    /**
     * @param $error_data array
     * @return WP_Error
     */
    private function validation_error( $error_data ) {
        return new WP_Error( 'validation-error',  __('Validation Error', 'woothemes-sensei'), $error_data );
    }

    /**
     * @param $data
     * @param $field_declaration Sensei_Domain_Models_Field
     * @return mixed|null
     */
    private function prepare_value( $field_declaration ) {
        $value = $this->data[ $field_declaration->name ];

        if ( isset( $field_declaration->before_return ) && !empty( $field_declaration->before_return ) ) {
            return call_user_func_array( array( $this, $field_declaration->before_return ), array( $value ) );
        }

        return $value;
    }

    protected function cast_bool( $value ) {
        return (bool)$value;
    }

    protected function cast_absint( $value ) {
        return absint( $value );
    }

    /**
     * @param $declared_fields array
     * @return array
     */
    private function group_declared_fields_by_type( $declared_fields ) {
        $fields = array(
        );
        foreach ( $declared_fields as $field ) {
            $fields[$field->name] = $field;
        }
        return $fields;
    }
}
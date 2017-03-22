<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Course
 * @package Domain_Models
 */
class Sensei_Domain_Models_Course {

    private static $field_declarations_by_model = array();

    /**
     * @var array
     */
    private $data;

    /**
     * @var array|int|null|WP_Comment|WP_Post|WP_User
     */
    private $raw_data;

    /**
     * @var array the model fields Sensei_Domain_Models_Field
     */
    private $fields;


    /**
     * Sensei_Domain_Models_Course constructor.
     * @param array|int|WP_Post|WP_Comment|WP_User $data the data object. either an int id, a wp entity
     * @since 1.9.13
     */
    function __construct( $data = array() ) {
        $this->fields = self::get_field_declarations();
        $this->data = array();

        if ( is_array( $data ) ) {
            $model_data = $data;
        } else {
            $model_data = $this->get_data_array_from_entity( $data );
        }

        $this->raw_data = $model_data;

        $post_array_keys = array_keys( $model_data );
        foreach ( $this->fields as $key => $field_declaration ) {
            // eager load anything that is not a meta_field or a derived_field
            if ( false === $field_declaration->is_field() ) {
                continue;
            }
            $this->add_data_for_key( $field_declaration, $post_array_keys, $model_data, $key);
        }
    }

    protected function get_data_array_from_entity( $entity ) {
        if ( is_numeric( $entity  ) ) {
            return get_post( absint( $entity ), ARRAY_A );
        } else if ( is_a( $entity, 'WP_Post' ) ) {
            return $entity->to_array();
        } else {
            throw new Sensei_Domain_Models_Exception('does not understand entity');
        }
    }

    /**
     * @param string $field_name
     * @return mixed|null
     */
    public function __get( $field_name ) {
        return $this->get_value_for( $field_name );
    }

    public function get_value_for( $field_name ) {
        if ( !isset( $this->fields[$field_name] ) ) {
            return null;
        }

        $field_declaration = $this->fields[$field_name];

        $this->load_field_value_if_missing( $field_declaration );

        return $this->prepare_value( $field_declaration );
    }

    /**
     * @param Sensei_Domain_Models_Field_Declaration $field_declaration
     */
    protected function load_field_value_if_missing( $field_declaration ) {
        $field_name = $field_declaration->name;
        if ( !isset( $this->data[ $field_name ] ) ) {
            if ( $field_declaration->is_meta_field() ) {
                $map_from = $field_declaration->get_name_to_map_from();
                $value = get_post_meta( $this->get_id(), $map_from, true );
                $this->data[ $field_name ] = $value;
            } else if ( $field_declaration->is_derived_field() ) {
                $map_from = $field_declaration->get_name_to_map_from();
                $value = call_user_func( array( $this, $map_from ) );
                $this->data[ $field_name ] = $value;
            } else {
                // load the default value for the field
                $this->data[ $field_name ] = $field_declaration->get_default_value();
            }
        }
    }

    public function get_id() {
        return $this->id;
    }

    protected static function field() {
        return new Sensei_Domain_Models_Field_Declaration_Builder();
    }

    protected static function meta_field() {
        return self::field()->of_type( Sensei_Domain_Models_Field_Declaration::META );
    }

    protected static function derived_field() {
        return self::field()->of_type( Sensei_Domain_Models_Field_Declaration::DERIVED );
    }

    protected static function declare_fields() {
        return array(
            self::field()
                ->with_name( 'id' )
                ->map_from( 'ID' )
                ->with_value_type('int')
                ->with_before_return( 'cast_absint' ),
            self::field()
                ->with_name( 'title' )
                ->map_from( 'post_title' )
                ->with_value_type('string')
                ->required( true ),
            self::field()
                ->with_name( 'author' )
                ->map_from( 'post_author' )
                ->with_value_type('int')
                ->with_before_return( 'cast_absint' ),
            self::field()
                ->with_name( 'content' )
                ->with_value_type('string')
                ->map_from( 'post_content' ),
            self::field()
                ->with_name( 'excerpt' )
                ->with_value_type('string')
                ->map_from( 'post_excerpt' ),
            self::field()
                ->with_name( 'type' )
                ->with_value_type('string')
                ->with_default_value( 'course' )
                ->map_from( 'post_type' ),
            self::field()
                ->with_name( 'status' )
                ->with_value_type('string')
                ->map_from( 'post_status' ),

            self::derived_field()
                ->with_name( 'modules' )
                ->map_from( 'course_module_ids' )
                ->with_json_name( 'module_ids' ),
            self::derived_field()
                ->with_name( 'module_order' )
                ->map_from( 'module_order' ),
            self::derived_field()
                ->with_name( 'lessons' )
                ->map_from( 'course_lessons' )
                ->not_visible(),

            self::meta_field()
                ->with_name( 'prerequisite' )
                ->map_from( '_course_prerequisite' )
                ->with_value_type('int')
                ->with_before_return( 'cast_absint' ),
            self::meta_field()
                ->with_name( 'featured' )
                ->map_from( '_course_featured' )
                ->with_value_type('boolean')
                ->with_before_return( 'cast_bool' )
                ->with_json_name( 'is_featured' ),
            self::meta_field()
                ->with_name( 'video_embed' )
                ->map_from( '_course_video_embed' ),
            self::meta_field()
                ->with_name( 'woocommerce_product' )
                ->map_from( '_course_woocommerce_product' )
                ->with_value_type('int')
                ->with_before_return( 'cast_absint' ),
            self::meta_field()
                ->with_name( 'lesson_order' )
                ->map_from( '_lesson_order' )
        );
    }

    protected function course_module_ids() {
        $modules = Sensei()->modules->get_course_modules( absint( $this->id ) );
        return array_map( 'absint', wp_list_pluck( $modules, 'term_id' ) );
    }

    /**
     * Get module order callable
     * @return array
     */
    protected function module_order() {
        $modules = Sensei()->modules->get_course_module_order( absint( $this->id ) );
        return ( empty( $modules ) ) ? array() : array_map( 'absint', $modules );
    }

    public static function find_one_by_id( $course_id ) {
        $course_id = absint( $course_id );
        $course = get_post( $course_id );
        if (!empty( $course ) && $course->post_type === 'course' ) {
            return self::create_object( $course );
        }
        return null;
    }

    public function get_json_field_mappings() {
        $mappings = array();
        foreach ( self::get_field_declarations() as $field_declaration ) {
            if ( !$field_declaration->suppports_output_type( 'json' ) ) {
                continue;
            }
            $mappings[$field_declaration->json_name] = $field_declaration->name;
        }
        return $mappings;
    }

    public static function all() {
        $results = array();
        foreach ( self::get_entities() as $entity ) {
            $results[] = self::create_object( $entity );
        }
        return new Sensei_Domain_Models_Model_Collection( $results );
    }

    protected static function create_object( $entity ) {
        $klass = __CLASS__;
        return new $klass( $entity );
    }

    protected static function get_entities() {
        $query = new WP_Query( array(
            'post_type' => 'course'
        ) );
        return $query->get_posts();
    }

    /**
     * @param $request
     * @return Sensei_Domain_Models_Course
     */
    public static function new_from_request( $request ) {
        $fields = self::get_field_declarations();
        $field_data = array();
        foreach ( $fields as $field ) {
            if ( isset( $request[$field->name] ) ) {
                $field_data[ $field->name ] = $request[$field->name];
            } else {
                $field_data[ $field->name ] = $field->get_default_value();
            }
        }

        return self::create_object( $field_data );
    }

    public function upsert() {
        $fields = $this->map_field_types_for_upserting( Sensei_Domain_Models_Field_Declaration::FIELD );
        $meta_fields = $this->map_field_types_for_upserting( Sensei_Domain_Models_Field_Declaration::META );
        return $this->save_entity( $fields, $meta_fields );
    }
    
    private function save_entity( $fields, $meta_fields = array() ) {
//        $fields['meta_input'] = $meta_fields;
        $success = wp_insert_post( $fields, true );
        if ( is_wp_error( $success ) ) {
            //todo: something wrong
            return $success;
        }
        return absint( $success );
    }

    private function map_field_types_for_upserting( $field_type ) {
        $field_values_to_insert = array();
        foreach ( self::get_field_declarations( $field_type ) as $field_declaration ) {
            $what_to_map_to = $field_declaration->get_name_to_map_from();
            $field_values_to_insert[$what_to_map_to] = $this->get_value_for( $field_declaration->name );
        }
        return $field_values_to_insert;
    }

    /**
     * validate object
     * @return bool|WP_Error
     */
    public function validate() {
        $validation_errors = array();
        $title = $this->title;
        if ( empty( $title ) ) {
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
        return new WP_Error( 'validation-error',  __( 'Validation Error', 'woothemes-sensei' ), $error_data );
    }

    /**
     * @param $data
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
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
     * @param $declared_field_builders array of Sensei_Domain_Models_Field_Builder
     * @return array
     */
    protected static function initialize_field_map( $declared_field_builders ) {
        $fields = array(
        );
        foreach ( $declared_field_builders as $field_builder ) {
            $field = $field_builder->build();
            $fields[$field->name] = $field;
        }
        return $fields;
    }

    protected static function get_field_declarations( $filter_by_type=null ) {
        $klass = __CLASS__;
        if ( !isset(self::$field_declarations_by_model[$klass]) ||
             null === self::$field_declarations_by_model[$klass] ) {
            // lazy-load model declarations when the first model if this type is constructed
            $fields = call_user_func( array( $klass, 'declare_fields' ) );
            self::$field_declarations_by_model[$klass] = call_user_func( array( $klass, 'initialize_field_map' ), $fields );
        }
        if ( null === $filter_by_type ) {
            return self::$field_declarations_by_model[$klass];
        }
        $filtered = array();
        foreach (self::$field_declarations_by_model[$klass] as $field_declaration ) {
            if ( $field_declaration->type === $filter_by_type ) {
                $filtered[] = $field_declaration;
            }
        }
        return $filtered;
    }

    /**
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
     * @param $post_array_keys array
     * @param $model_data array
     * @param $key string
     */
    private function add_data_for_key( $field_declaration, $post_array_keys, $model_data, $key )
    {
        $map_from = $field_declaration->get_name_to_map_from();
        if (in_array($map_from, $post_array_keys)) {
            $this->data[$key] = $model_data[$map_from];
        } else if (in_array($key, $post_array_keys)) {
            $this->data[$key] = $model_data[$key];
        }
    }
}
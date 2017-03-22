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
     * @var array the model fields Sensei_Domain_Models_Field
     */
    private $fields;


    /**
     * Sensei_Domain_Models_Course constructor.
     * @param array|int|WP_Post|WP_Comment|WP_User $data the data object. either an int id, a wp entity
     * @since 1.9.13
     */
    function __construct( $data = array() ) {
        $this->fields = $this->initialize_model_fields();
        $this->data = array();

        if ( is_array( $data ) ) {
            $model_data = $data;
        } else {
            $model_data = $this->get_data_array_from_entity( $data );
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
        if ( !isset( $this->fields[$field_name] ) ) {
            return null;
        }

        $field_declaration = $this->fields[$field_name];

        $this->load_field_value_if_missing( $field_declaration );

        return $this->prepare_value( $field_declaration );
    }

    /**
     * @param Sensei_Domain_Models_Field $field_declaration
     */
    protected function load_field_value_if_missing($field_declaration ) {
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

    protected function declare_fields() {
        return array(
            $this->field()
                ->with_name( 'id' )
                ->map_from( 'ID' )
                ->with_before_return( 'cast_absint' ),
            $this->field()
                ->with_name( 'title' )
                ->map_from( 'post_title' )
                ->required( true ),
            $this->field()
                ->with_name( 'author' )
                ->map_from( 'post_author' )
                ->with_before_return( 'cast_absint' ),
            $this->field()
                ->with_name( 'content' )
                ->map_from( 'post_content' ),
            $this->field()
                ->with_name( 'excerpt' )
                ->map_from( 'post_excerpt' ),

            $this->derived_field()
                ->with_name( 'modules' )
                ->map_from( 'course_module_ids' ),
            $this->derived_field()
                ->with_name( 'module_order' )
                ->map_from( 'module_order' ),
            $this->derived_field()
                ->with_name( 'lessons' )
                ->map_from( 'course_lessons' ),

            $this->meta_field()
                ->with_name( 'prerequisite' )
                ->map_from( '_course_prerequisite' )
                ->with_before_return( 'cast_absint' ),
            $this->meta_field()
                ->with_name( 'featured' )
                ->map_from( '_course_featured' )
                ->with_before_return( 'cast_bool' ),
            $this->meta_field()
                ->with_name( 'video_embed' )
                ->map_from( '_course_video_embed' ),
            $this->meta_field()
                ->with_name( 'woocommerce_product' )
                ->map_from( '_course_woocommerce_product' )
                ->with_before_return( 'cast_absint' ),
            $this->meta_field()
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
            return new self( $course );
        }
        return null;
    }

    public function get_json_field_mappings() {
        return array(
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
    }

    public static function all() {
        $query = new WP_Query( array(
            'post_type' => 'course'
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
        $title = isset( $request['title'] ) ? esc_html( $request['title'] ) : '';

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
     * @param $declared_field_builders array
     * @return array
     */
    private function group_declared_fields_by_type($declared_field_builders ) {
        $fields = array(
        );
        foreach ( $declared_field_builders as $field_builder ) {
            $field = $field_builder->build();
            $fields[$field->name] = $field;
        }
        return $fields;
    }

    private function initialize_model_fields()
    {
        $self_class = get_class($this);
        if (!isset(self::$field_declarations_by_model[$self_class])) {
            // lazy-load model declarations when the first model if this type is constructed
            self::$field_declarations_by_model[$self_class] = $this->group_declared_fields_by_type($this->declare_fields());
        }
        return self::$field_declarations_by_model[$self_class];
    }
}
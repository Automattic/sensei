<?php

/**
 * Class Sensei_Domain_Models_Model_Abstract
 * @package Domain_Models
 * @since 1.9.13
 */
abstract class Sensei_Domain_Models_Model_Abstract {

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array|int|null|WP_Comment|WP_Post|WP_User
     */
    protected $raw_data;

    /**
     * @var array the model fields Sensei_Domain_Models_Field
     */
    protected $fields;

    /**
     * Sensei_Domain_Models_Course constructor.
     * @param array|int|WP_Post|WP_Comment|WP_User $data the data object. either an int id, a wp entity
     * @since 1.9.13
     */
    function __construct( $data = array() ) {
        $this->fields = self::get_field_declarations( get_class( $this ) );
        $this->data = array();

        if ( is_array( $data ) ) {
            $model_data = $data;
        } else {
            $model_data = $this->get_data_array_from_entity( $data );
        }

        $this->raw_data = $model_data;

        $post_array_keys = array_keys( $model_data );
        foreach ( $this->fields as $key => $field_declaration ) {
            // eager load anything that is not a meta or derived field
            if ( false === $field_declaration->is_field() ) {
                continue;
            }
            $this->add_data_for_key( $field_declaration, $post_array_keys, $model_data, $key);
        }
    }

    protected function get_data_array_from_entity( $entity ) {
        if ( is_numeric( $entity  ) ) {
            $data_store = $this->get_data_store();
            return $data_store->get_entity( $entity );
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
     * @param $field
     * @param $value
     * @return void
     */
    public function set_value( $field, $value ) {
        if (!isset( $this->fields[$field] ) ) {
            return;
        }

        $field_declaration = $this->fields[$field];
        $val = $field_declaration->cast_value( $value );
        $this->data[$field_declaration->name] = $val;
    }

    /**
     * @param $other WP_REST_Request
     * @return Sensei_Domain_Models_Model_Abstract
     */
    public function merge_updates_from_request( $request, $updating = false ) {
        $fields = self::get_field_declarations( get_class( $this ) );
        $field_data = array();
        foreach ( $fields as $field ) {
            if ( $field->is_derived_field() ) {
                continue;
            }
            if ( isset( $request[$field->name] ) && !( $updating && $field->primary ) ) {
                $field_data[ $field->name ] = $request[$field->name];
                $this->set_value( $field->name, $request[$field->name] );
            }
        }
        return $this;
    }


    /**
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
     * @param $post_array_keys array
     * @param $model_data array
     * @param $key string
     */
    protected function add_data_for_key( $field_declaration, $post_array_keys, $model_data, $key ) {
        $map_from = $field_declaration->get_name_to_map_from();
        if (in_array($map_from, $post_array_keys)) {
            $this->set_value( $key, $model_data[$map_from] );
        } else if (in_array($key, $post_array_keys)) {
            $this->set_value( $key, $model_data[$key] );
        } else {
            $this->set_value( $key, $field_declaration->get_default_value() );
        }
    }

    /**
     * @param Sensei_Domain_Models_Field_Declaration $field_declaration
     */
    protected function load_field_value_if_missing( $field_declaration ) {
        $field_name = $field_declaration->name;
        if ( !isset( $this->data[ $field_name ] ) ) {
            if ( $field_declaration->is_meta_field() ) {
                $value = $this->get_data_store()->get_meta_field_value( $this, $field_declaration );
                $this->set_value( $field_name, $value );
            } else if ( $field_declaration->is_derived_field() ) {
                $map_from = $field_declaration->get_name_to_map_from();
                $value = call_user_func( array( $this, $map_from ) );
                $this->set_value( $field_name, $value );
            } else {
                // load the default value for the field
                $this->set_value( $field_name, $field_declaration->get_default_value() );
            }
        }
    }

    public function upsert() {
        $fields = $this->map_field_types_for_upserting( Sensei_Domain_Models_Field_Declaration::FIELD );
        $meta_fields = $this->map_field_types_for_upserting( Sensei_Domain_Models_Field_Declaration::META );
        return $this->get_data_store()->upsert( $this, $fields, $meta_fields );
    }

    public function delete() {
        return $this->get_data_store()->delete( $this );
    }

    public function get_data_transfer_object_field_mappings() {
        $mappings = array();
        foreach ( self::get_field_declarations( get_class( $this ) ) as $field_declaration ) {
            if ( !$field_declaration->suppports_output_type( 'json' ) ) {
                continue;
            }
            $mappings[$field_declaration->json_name] = $field_declaration->name;
        }
        return $mappings;
    }

    private function map_field_types_for_upserting( $field_type ) {
        $field_values_to_insert = array();
        foreach ( self::get_field_declarations( get_class( $this ), $field_type ) as $field_declaration ) {
            $what_to_map_to = $field_declaration->get_name_to_map_from();
            $field_values_to_insert[$what_to_map_to] = $this->get_value_for( $field_declaration->name );
        }
        return $field_values_to_insert;
    }

    /**
     * @throws Sensei_Domain_Models_Exception
     * @return array
     */
    public static function declare_fields() {
        throw new Sensei_Domain_Models_Exception('override me ' . __FUNCTION__ );
    }

    /**
     * @return int
     */
    public function get_id() {
        throw new Sensei_Domain_Models_Exception('override me ' . __FUNCTION__ );
    }
    
    /**
     * validates this object instance
     * @throws Sensei_Domain_Models_Exception
     * @return bool|WP_Error
     */
    public function validate() {
        $validation_errors = array();

        foreach ( $this->fields as $key => $field_declaration ) {
            $is_valid = $this->run_field_validations( $field_declaration );
            if ( is_wp_error( $is_valid ) ) {
                $validation_errors[] = $is_valid->get_error_data();
            }
        }
        if ( count( $validation_errors ) > 0 ) {
            return $this->validation_error( $validation_errors );
        }
        return true;
    }

    /**
     * @param $field_declaration Sensei_Domain_Models_Field_Declaration
     * @return bool|WP_Error
     */
    protected function run_field_validations( $field_declaration ) {
        if ( $field_declaration->is_derived_field() ) {
            return true;
        }
        $value = $this->get_value_for( $field_declaration->name );
        if ( $field_declaration->required && empty( $value ) ) {
            return new WP_Error(
                'required-field-empty',
                sprintf( __( '%s cannot be empty', 'woothemes-sensei' ), $field_declaration->name )
            );
        } else if ( !$field_declaration->required && ! empty( $value ) ) {
            foreach ( $field_declaration->validations as $method_name ) {
                $result = call_user_func( array($this, $method_name ), $value );
                if ( is_wp_error( $result ) ) {
                    $result->add_data(array(
                        'reason' => $result->get_error_messages(),
                        'field' => $field_declaration->name,
                        'value' => $value ) );
                    return $result;
                }
            }
        }
        return true;
    }

    /**
     * @param $declared_field_builders array of Sensei_Domain_Models_Field_Builder
     * @return array
     */
    public static function initialize_field_map( $declared_field_builders ) {
        $fields = array(
        );
        foreach ( $declared_field_builders as $field_builder ) {
            $field = $field_builder->build();
            $fields[$field->name] = $field;
        }
        return $fields;
    }

    public static function get_field_declarations( $klass, $filter_by_type=null ) {
        return Sensei_Domain_Models_Registry::get_instance()
            ->get_field_declarations( $klass, $filter_by_type );
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

    protected function as_bool($value ) {
        return (bool)$value;
    }

    protected function as_uint($value ) {
        return absint( $value );
    }

    protected function as_nullable_uint( $value ) {
        return ( empty( $value ) && !is_numeric( $value ) ) ? null : $this->as_uint( $value );
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

    /**
     * @return Sensei_Domain_Models_Data_Store
     * @throws Sensei_Domain_Models_Exception
     */
    protected function get_data_store()
    {
        return Sensei_Domain_Models_Registry::get_instance()->get_data_store_for_domain_model(get_class($this));
    }
}
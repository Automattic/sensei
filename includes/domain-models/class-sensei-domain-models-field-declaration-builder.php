<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Field_Builder
 * Builds a Sensei_Domain_Models_Field
 * @package Domain_Models
 * @since 1.9.13
 */
class Sensei_Domain_Models_Field_Declaration_Builder {
    function __construct() {
        $this->args = array(
            'name'              => '',
            'type'              => Sensei_Domain_Models_Field_Declaration::FIELD,
            'required'          => false,
            'map_from'          => null,
            'before_return'     => null,
            'value_type'        => 'any',
            'default_value'     => null,
            'json_name'         => null,
            'supported_outputs' => array( 'json' ),
            'description'       => null,
            'validations'       => array(),
        );
    }
    public function build() {
        return new Sensei_Domain_Models_Field_Declaration( $this->args );
    }

    public function with_default_value( $default_value ) {
        return $this->set( 'default_value', $default_value );
    }

    public function with_name( $name ) {
        return $this->set( 'name', $name );
    }

    public function of_type( $type ) {
        return $this->set( 'type', $type );
    }

    public function map_from( $mapped_from ) {
        return $this->set( 'map_from', $mapped_from );
    }

    public function with_before_return( $before_return ) {
        return $this->set( 'before_return', $before_return );
    }

    public function required( $required ) {
        return $this->set( 'required', $required );

    }

    public function with_supported_outputs( $supported_outputs = array() ) {
        return $this->set( 'supported_outputs', (array)$supported_outputs );
    }

    public function not_visible() {
        return $this->with_supported_outputs( array() );
    }

    public function with_value_type( $value_type ) {
        return $this->set( 'value_type', $value_type );
    }

    public function with_json_name( $json_name ) {
        return $this->set( 'json_name', $json_name );
    }

    public function with_description( $description ) {
        return $this->set( 'description', $description );
    }

    public function with_validations( $validations ) {
        return $this->set( 'validations', is_array( $validations ) ? $validations : array( $validations ) );
    }

    private function set( $name, $value ) {
        $this->args[$name] = $value;
        return $this;
    }
}
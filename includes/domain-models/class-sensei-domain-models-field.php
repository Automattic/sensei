<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

class Sensei_Domain_Models_Field {

    const TYPE_FIELD = 'field';
    const META = 'meta_field';
    const DERIVED = 'derived_field';
    public $before_return;
    public $map_from;
    public $type;
    public $name;

    private $accepted_field_types = array(
        self::TYPE_FIELD,
        self::META,
        self::DERIVED
    );

    public function __construct( $args ) {
        if ( !isset( $args['name'] ) ) {
            throw new Exception( 'every field should have a name' );
        }
        if ( !isset( $args['type'] ) || !in_array( $args['type'], $this->accepted_field_types ) ) {
            throw new Exception( 'every field should have a type (one of ' . implode( ',', $this->accepted_field_types ) . ')' );
        }
        $this->name = $args['name'];
        $this->type = $args['type'];
        $this->map_from = isset( $args['map_from'] ) ? $args['map_from'] : null;
        $this->before_return = isset( $args['before_return'] ) ? $args['before_return'] : null;

    }

    public function is_meta_field() {
        return $this->type === self::META;
    }

    public function is_derived_field() {
        return $this->type === self::DERIVED;
    }

    public function is_field() {
        return $this->type === self::TYPE_FIELD;
    }
}

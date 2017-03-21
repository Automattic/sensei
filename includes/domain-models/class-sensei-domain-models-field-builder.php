<?php

class Sensei_Domain_Models_Field_Builder {
    function __construct() {
        $this->args = array(
            'name' => '',
            'type' => Sensei_Domain_Models_Field::TYPE_FIELD,
        );
    }
    public function build() {
        return new Sensei_Domain_Models_Field( $this->args );
    }

    public function with_name( $name ) {
        $this->args['name'] = $name;
        return $this;
    }

    public function of_type( $type ) {
        $this->args['type'] = $type;
        return $this;
    }

    public function get_from( $mapped_from ) {
        $this->args['map_from'] = $mapped_from;
        return $this;
    }

    public function with_before_return( $before_return ) {
        $this->args['before_return'] = $before_return;
        return $this;
    }
}
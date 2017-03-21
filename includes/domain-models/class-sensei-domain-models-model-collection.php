<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Model_Collection
 * represents a collection of Sensei_Domain_Models
 * @package Domain_Models
 */
class Sensei_Domain_Models_Model_Collection {
    public function __construct( $models = array() ) {
        $this->models = $models;
    }

    public function get_items() {
        return $this->models;
    }
}
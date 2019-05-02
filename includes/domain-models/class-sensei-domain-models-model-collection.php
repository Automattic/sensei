<?php
/**
 * Domain Models Collection
 *
 * @package Sensei\Domain Models\Collection
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Model_Collection
 * represents a collection of Sensei_Domain_Models
 *
 * @package Sensei\Domain Models\Collection
 */
class Sensei_Domain_Models_Model_Collection {
	/**
	 * Constructor
	 *
	 * @param array $models Domain models.
	 */
	public function __construct( $models = array() ) {
		$this->models = $models;
	}

	/**
	 * Gets the collection of domain models.
	 *
	 * @return Sensei_Domain_Models_Model_Collection Domain model collection.
	 */
	public function get_items() {
		return $this->models;
	}
}

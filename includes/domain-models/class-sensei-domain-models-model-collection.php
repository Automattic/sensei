<?php
// phpcs:ignoreFile
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
 * @deprecated 3.11.0
 *
 * @package Sensei\Domain Models\Collection
 */
class Sensei_Domain_Models_Model_Collection {
	/**
	 * Constructor
	 *
	 * @deprecated 3.11.0
	 *
	 * @param array $models Domain models.
	 */
	public function __construct( $models = array() ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$this->models = $models;
	}

	/**
	 * Gets the collection of domain models.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return Sensei_Domain_Models_Model_Collection Domain model collection.
	 */
	public function get_items() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->models;
	}
}

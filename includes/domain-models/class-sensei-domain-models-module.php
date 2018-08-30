<?php
/**
 * Module Domain Model
 *
 * @package Sensei\Domain Models\Model\Module
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class Sensei_Domain_Models_Module
 *
 * @package Sensei\Domain Models\Model\Module
 */
class Sensei_Domain_Models_Module extends Sensei_Domain_Models_Model_Abstract {
	/**
	 * Declares module fields.
	 *
	 * @return array Fields
	 */
	public static function declare_fields() {
		return array(
			self::field()
				->with_name( 'id' )
				->map_from( 'term_id' )
				->with_value_type( 'int' )
				->with_before_return( 'as_uint' ),
			self::field()
				->with_name( 'name' )
				->with_value_type( 'string' ),
			self::field()
				->with_name( 'slug' )
				->with_value_type( 'string' ),
			self::field()
				->with_name( 'description' )
				->with_value_type( 'string' ),
			self::field()
				->with_name( 'taxonomy' )
				->with_value_type( 'string' ),
		);
	}

	/**
	 * Gets the module ID.
	 *
	 * @return int Module ID
	 */
	public function get_id() {
		return $this->id;
	}
}

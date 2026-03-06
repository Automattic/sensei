<?php
/**
 * File containing the Grading_Queries_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Grading;

use Sensei\Internal\Services\Progress_Storage_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Grading_Queries_Factory.
 *
 * Factory that creates the appropriate grading queries implementation based on HPPS settings.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Grading_Queries_Factory {

	/**
	 * Create a grading queries instance.
	 *
	 * @since $$next-version$$
	 *
	 * @return Grading_Queries_Interface
	 */
	public function create(): Grading_Queries_Interface {
		global $wpdb;

		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Grading_Queries( $wpdb );
		}

		return new Comments_Based_Grading_Queries( $wpdb );
	}
}

<?php
/**
 * File containing the Progress_Query_Service_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Progress_Query_Service_Factory.
 *
 * Factory that returns the correct Progress_Query_Service_Interface implementation
 * based on the current progress storage settings.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Progress_Query_Service_Factory {

	/**
	 * Create a Progress_Query_Service_Interface instance.
	 *
	 * Returns a tables-based implementation when HPPS is enabled and the tables
	 * repository is active, otherwise returns a comments-based implementation.
	 *
	 * @since $$next-version$$
	 *
	 * @return Progress_Query_Service_Interface The progress query service.
	 */
	public function create(): Progress_Query_Service_Interface {
		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Progress_Query_Service( $GLOBALS['wpdb'] );
		}

		return new Comments_Based_Progress_Query_Service( $GLOBALS['wpdb'] );
	}
}

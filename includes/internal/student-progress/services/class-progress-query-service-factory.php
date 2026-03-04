<?php
/**
 * File containing the Progress_Query_Service_Factory class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Student_Progress\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Progress_Query_Service_Factory.
 *
 * Creates the appropriate Progress_Query_Service implementation based on
 * the current HPPS storage settings.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Progress_Query_Service_Factory {

	/**
	 * Is tables based progress feature flag enabled.
	 *
	 * @var bool
	 */
	private $tables_enabled;

	/**
	 * Read from tables.
	 *
	 * @var bool
	 */
	private $read_tables;

	/**
	 * Progress_Query_Service_Factory constructor.
	 *
	 * @param bool $tables_enabled Is tables based progress feature flag enabled.
	 * @param bool $read_tables    Read from tables.
	 */
	public function __construct( bool $tables_enabled, bool $read_tables ) {
		$this->tables_enabled = $tables_enabled;
		$this->read_tables    = $read_tables;
	}

	/**
	 * Create a progress query service.
	 *
	 * @internal
	 *
	 * @return Progress_Query_Service_Interface
	 */
	public function create(): Progress_Query_Service_Interface {
		if ( $this->tables_enabled && $this->read_tables ) {
			global $wpdb;
			return new Tables_Based_Progress_Query_Service( $wpdb );
		}

		return new Comments_Based_Progress_Query_Service();
	}
}

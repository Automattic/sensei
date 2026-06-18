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
 * Factory that returns the correct progress service implementations (clauses and aggregation)
 * based on the current progress storage settings.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Progress_Query_Service_Factory {

	/**
	 * Create a Progress_Clauses_Service_Interface instance.
	 *
	 * Returns a tables-based implementation when HPPS is enabled and the tables
	 * repository is active, otherwise returns a comments-based implementation.
	 *
	 * @since 4.26.0
	 *
	 * @return Progress_Clauses_Service_Interface The progress clauses service.
	 */
	public function create_clauses_service(): Progress_Clauses_Service_Interface {
		global $wpdb;

		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Progress_Clauses_Service( $wpdb );
		}

		return new Comments_Based_Progress_Clauses_Service( $wpdb );
	}

	/**
	 * Create a Grading_Listing_Service_Interface instance.
	 *
	 * @since 4.26.0
	 *
	 * @return Grading_Listing_Service_Interface The grading listing service.
	 */
	public function create_grading_listing_service(): Grading_Listing_Service_Interface {
		global $wpdb;

		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Grading_Listing_Service( $wpdb );
		}

		return new Comments_Based_Grading_Listing_Service();
	}

	/**
	 * Create a Grading_Stats_Service_Interface instance.
	 *
	 * Returns a tables-based implementation when HPPS is enabled and the tables
	 * repository is active, otherwise returns a comments-based implementation.
	 *
	 * @since 4.26.0
	 *
	 * @return Grading_Stats_Service_Interface The grading stats service.
	 */
	public function create_grading_stats_service(): Grading_Stats_Service_Interface {
		global $wpdb;

		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Grading_Stats_Service( $wpdb );
		}

		return new Comments_Based_Grading_Stats_Service( $wpdb );
	}

	/**
	 * Create a Reports_Listing_Service_Interface instance.
	 *
	 * @since 4.26.0
	 *
	 * @return Reports_Listing_Service_Interface The reports listing service.
	 */
	public function create_reports_listing_service(): Reports_Listing_Service_Interface {
		global $wpdb;

		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Reports_Listing_Service( $wpdb );
		}

		return new Comments_Based_Reports_Listing_Service();
	}

	/**
	 * Create a Progress_Aggregation_Service_Interface instance.
	 *
	 * Returns a tables-based implementation when HPPS is enabled and the tables
	 * repository is active, otherwise returns a comments-based implementation.
	 *
	 * @since 4.26.0
	 *
	 * @return Progress_Aggregation_Service_Interface The progress aggregation service.
	 */
	public function create_aggregation_service(): Progress_Aggregation_Service_Interface {
		global $wpdb;

		if ( Progress_Storage_Settings::is_hpps_enabled() && Progress_Storage_Settings::is_tables_repository() ) {
			return new Tables_Based_Progress_Aggregation_Service( $wpdb );
		}

		return new Comments_Based_Progress_Aggregation_Service( $wpdb );
	}
}

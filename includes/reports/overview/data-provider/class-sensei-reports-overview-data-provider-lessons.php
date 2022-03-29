<?php
/**
 * File containing the Sensei_Reports_Overview_Data_Provider_Lessons class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sensei_Reports_Overview_Data_Provider_Courses
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_Data_Provider_Lessons implements Sensei_Reports_Overview_Data_Provider_Interface {
	/**
	 * Get the data for the overview report.
	 *
	 * @param array $filters Filters to apply to the data.
	 *
	 * @return array
	 */
	public function get_items( array $filters ): array {
		// TODO: Implement get_items() method.
		return [];
	}

	/**
	 * Get the total number of items found for the last query.
	 *
	 * @return int
	 */
	public function get_last_total_items(): int {
		// TODO: Implement get_last_total_items() method.
		return 0;
	}
}

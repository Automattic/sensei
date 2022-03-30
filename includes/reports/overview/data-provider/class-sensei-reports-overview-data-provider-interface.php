<?php
/**
 * File containing the interface Sensei_Reports_Overview_DataProvider_Interface.
 *
 * @package sensei
 */

/**
 * Interface Sensei_Reports_Overview_Data_Provider_Interface.
 *
 * @since 4.3.0
 */
interface Sensei_Reports_Overview_Data_Provider_Interface {
	/**
	 * Get the data for the overview report.
	 *
	 * @param array $filters Filters to apply to the data.
	 *
	 * @return array
	 */
	public function get_items( array $filters ): array;

	/**
	 * Get the total number of items found for the last query.
	 *
	 * @return int
	 */
	public function get_last_total_items(): int;

}

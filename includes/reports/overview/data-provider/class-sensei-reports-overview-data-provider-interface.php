<?php

interface Sensei_Reports_Overview_DataProvider_Interface {
	/**
	 * Get the data for the overview report.
	 *
	 * @param array $filters
	 *
	 * @return array
	 */
	public function get_items( array $filters ): array;

	public function get_last_total_items(): int;

}

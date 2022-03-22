<?php

interface Sensei_Reports_Overview_DataProvider_Interface {
	/**
	 * Get the data for the overview report.
	 *
	 * @param array $args
	 * @param string|null $date_from
	 * @param string|null $date_to
	 *
	 * @return array
	 */
	public function get_items( array $args, $date_from = null, $date_to = null ): array;

	public function get_last_total_items(): int;

}

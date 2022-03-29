<?php
/**
 * File containing the Sensei_Reports_Overview_List_Table_Students class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Students overview list table class.
 *
 * @since 4.3.0
 */
class Sensei_Reports_Overview_List_Table_Students extends Sensei_Reports_Overview_List_Table_Abstract {
	/**
	 * Return additional filters for current report.
	 *
	 * @return array
	 */
	protected function get_additional_filters(): array {
		// TODO: Implement get_additional_filters() method.
		return [];
	}
}

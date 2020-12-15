<?php
/**
 * File containing the class Sensei_Progress_Course.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for course progress record.
 */
class Sensei_Progress_Course extends Sensei_Progress {

	/**
	 * Get the record type (course, lesson).
	 *
	 * @return string
	 */
	public function get_record_type() {
		return 'course';
	}
}

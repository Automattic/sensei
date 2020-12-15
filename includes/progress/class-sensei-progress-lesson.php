<?php
/**
 * File containing the class Sensei_Progress_Lesson.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for lesson progress record.
 */
class Sensei_Progress_Lesson extends Sensei_Progress {

	/**
	 * Get the record type (course, lesson).
	 *
	 * @return string
	 */
	public function get_record_type() {
		return 'lesson';
	}
}

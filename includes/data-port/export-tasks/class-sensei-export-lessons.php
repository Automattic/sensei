<?php
/**
 * File containing the Sensei_Export_Lessons class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei_Data_Port_Lesson_Schema as Schema;

/**
 * Export content to a CSV file for the given type.
 */
class Sensei_Export_Lessons
	extends Sensei_Export_Task {

	/**
	 * Content type of the task.
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'lesson';
	}

	/**
	 * Collect exported fields of the lesson.
	 *
	 * @param WP_Post $post The lesson.
	 *
	 * @return string[]
	 */
	protected function get_post_fields( $post ) {
		return '';
	}

	/**
	 * Schema for the content type.
	 *
	 * @return Sensei_Data_Port_Schema
	 */
	protected function get_type_schema() {
		return new Sensei_Data_Port_Lesson_Schema();
	}

}

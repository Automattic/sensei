<?php
/**
 * File containing the Sensei_Import_Questions class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the import task for questions.
 */
class Sensei_Import_Questions
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	const COLUMN_QUESTION        = 'question';
	const COLUMN_ANSWER          = 'answer';
	const COLUMN_ID              = 'id';
	const COLUMN_SLUG            = 'slug';
	const COLUMN_DESCRIPTION     = 'description';
	const COLUMN_STATUS          = 'status';
	const COLUMN_TYPE            = 'type';
	const COLUMN_GRADE           = 'grade';
	const COLUMN_RANDOMISE       = 'randomise';
	const COLUMN_MEDIA           = 'media';
	const COLUMN_CATEGORIES      = 'categories';
	const COLUMN_FEEDBACK        = 'feedback';
	const COLUMN_TEXT_BEFORE_GAP = 'text before gap';
	const COLUMN_GAP             = 'gap';
	const COLUMN_TEXT_AFTER_GAP  = 'text after gap';
	const COLUMN_UPLOAD_NOTES    = 'upload notes';
	const COLUMN_TEACHER_NOTES   = 'teacher notes';

	/**
	 * Run this task.
	 */
	public function run() {
		// @todo Implement.
	}

	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		// @todo Implement.

		return false;
	}

	/**
	 * Returns the completion ratio of this task. The ration has the following format:
	 *
	 * {
	 *
	 *     @type integer $completed  Number of completed actions.
	 *     @type integer $total      Number of total actions.
	 * }
	 *
	 * @return array
	 */
	public function get_completion_ratio() {
		// @todo Implement.

		return [
			'completed' => 0,
			'total'     => 0,
		];
	}

	/**
	 * Performs any required cleanup of the task.
	 */
	public function clean_up() {
		// @todo Implement.
	}

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_source_file( $file_path ) {
		$required_columns = [
			self::COLUMN_QUESTION,
			self::COLUMN_ANSWER,
		];

		$optional_columns = [
			self::COLUMN_ID,
			self::COLUMN_SLUG,
			self::COLUMN_DESCRIPTION,
			self::COLUMN_STATUS,
			self::COLUMN_TYPE,
			self::COLUMN_GRADE,
			self::COLUMN_RANDOMISE,
			self::COLUMN_MEDIA,
			self::COLUMN_CATEGORIES,
			self::COLUMN_FEEDBACK,
			self::COLUMN_TEXT_BEFORE_GAP,
			self::COLUMN_GAP,
			self::COLUMN_TEXT_AFTER_GAP,
			self::COLUMN_UPLOAD_NOTES,
			self::COLUMN_TEACHER_NOTES,
		];

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_columns, $optional_columns );
	}
}

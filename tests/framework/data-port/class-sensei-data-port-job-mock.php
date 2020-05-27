<?php
/**
 * This file contains the Sensei_Data_Port_Task_Mock class.
 *
 * @package sensei
 */


class Sensei_Data_Port_Job_Mock extends Sensei_Data_Port_Job {

	private $tasks;

	private static $restore_mock;

	public function __construct( $job_id, $args = [], $json = '' ) {
		parent::__construct( $job_id, $args, $json );

		$this->tasks = $args;
	}

	public function get_tasks() {
		return $this->tasks;
	}

	public function log( $title, $message, $type, $id ) {
		$this->add_log_entry( $title, $message, $type, $id );
	}

	public static function get( $job_id ) {
		return self::$restore_mock;
	}

	public static function set_restore_mock( $mock ) {
		self::$restore_mock = $mock;
	}

	public static function get_file_config() {
		$files = [];

		$csv_mime_types = [
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		];

		$files['questions'] = [
			'validator'  => function( $file ) {
				return true;
			},
			'mime_types' => $csv_mime_types,
		];

		return $files;
	}

	public function is_ready() {
		return true;
	}
}

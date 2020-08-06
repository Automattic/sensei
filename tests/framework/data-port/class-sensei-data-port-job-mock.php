<?php
/**
 * This file contains the Sensei_Data_Port_Task_Mock class.
 *
 * @package sensei
 */


class Sensei_Data_Port_Job_Mock extends Sensei_Data_Port_Job {

	private $tasks;

	private static $restore_mock;

	public function __construct( $job_id, $json = '' ) {
		parent::__construct( $job_id, $json );

		$this->tasks = [];
	}

	public static function create_with_tasks( $job_id, $tasks = [], $user_id = 0 ) {
		$job = static::create( $job_id, $user_id );
		$job->set_tasks( $tasks );

		return $job;
	}

	private function set_tasks( $tasks ) {
		$this->tasks = $tasks;
	}

	public function get_tasks() {
		return $this->tasks;
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

	protected function get_log_type_order() {
		return [ Sensei_Import_Course_Model::MODEL_KEY, Sensei_Import_Lesson_Model::MODEL_KEY, Sensei_Import_Question_Model::MODEL_KEY ];
	}
}

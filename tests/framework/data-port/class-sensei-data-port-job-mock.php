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
}

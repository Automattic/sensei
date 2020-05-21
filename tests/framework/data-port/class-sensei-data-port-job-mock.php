<?php
/**
 * This file contains the Sensei_Data_Port_Task_Mock class.
 *
 * @package sensei
 */


class Sensei_Data_Port_Job_Mock extends Sensei_Data_Port_Job {

	private static $tasks;

	public static function create( $tasks ) {
		self::$tasks = $tasks;

		return new self( 'test-job' );
	}

	public function get_name() {
		return 'test-job';
	}

	public function get_tasks() {
		return self::$tasks;
	}

	public function log( $title, $message, $type, $id ) {
		$this->add_log_entry( $title, $message, $type, $id );
	}

}

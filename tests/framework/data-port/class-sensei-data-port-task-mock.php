<?php
/**
 * This file contains the Sensei_Data_Port_Task_Mock class.
 *
 * @package sensei
 */


class Sensei_Data_Port_Task_Mock implements Sensei_Data_Port_Task_Interface {

	private $is_complete;

	private $completed_cycles;

	private $total_cycles;

	public function __construct( $is_complete, $completed_cycles, $total_cycles ) {
		$this->is_complete      = $is_complete;
		$this->completed_cycles = $completed_cycles;
		$this->total_cycles     = $total_cycles;
	}

	public function run() {}

	public function is_completed() {
		return $this->is_complete;
	}

	public function get_completion_ratio() {
		return [
			'completed' => $this->completed_cycles,
			'total'     => $this->total_cycles,
		];
	}

	public function save_state() {}
}

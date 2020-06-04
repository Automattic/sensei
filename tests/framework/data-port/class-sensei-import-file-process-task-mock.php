<?php
/**
 * This file contains the Sensei_Import_File_Process_Task_Mock class.
 *
 * @package sensei
 */

class Sensei_Import_File_Process_Task_Mock extends Sensei_Import_File_Process_Task {

	public function get_task_key() {
		return 'mock-key';
	}

	protected function process_line( $line_number, $line ) {}


	public function clean_up() {}

	public static function validate_source_file( $file_path ) {}
}

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

	public function get_model( $line ) {
		return Sensei_Import_Model_Mock::from_source_array( $line, new Sensei_Data_Port_Schema_Mock() );
	}

	public function clean_up() {}

	public static function validate_source_file( $file_path ) {}
}

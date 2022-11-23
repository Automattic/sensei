<?php
/**
 * This file contains the Sensei_Import_Model_Mock class.
 *
 * @package sensei
 */

class Sensei_Import_Model_Mock extends Sensei_Import_Model {
	const MODEL_KEY = 'mock-model';

	public function get_model_key() {
		return self::MODEL_KEY;
	}

	protected function get_existing_post_id() {
		return null;
	}

	public function sync_post() {
		return true;
	}

	public function get_error_data( $data = [] ) {
		return $data;
	}

}

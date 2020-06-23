<?php
/**
 * This file contains the Sensei_Import_Model_Mock class.
 *
 * @package sensei
 */

class Sensei_Import_Model_Mock extends Sensei_Import_Model {
	protected function get_existing_post_id() {
		return null;
	}

	public function sync_post() {
		return true;
	}

	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function set_post_id( $id ) {
		parent::set_post_id( $id );
	}

	public function get_error_data( $data = [] ) {
		return $data;
	}

}

<?php
/**
 * This file contains the Sensei_Data_Port_Model_Mock class.
 *
 * @package sensei
 */

class Sensei_Data_Port_Model_Mock extends Sensei_Data_Port_Model {
	protected function get_existing_post_id() {
		return null;
	}

	public function sync_post() {
		return true;
	}

	public static function get_schema() {
		return [
			'test-string-allow-html' => [
				'type'       => 'string',
				'required'   => true,
				'allow_html' => true,
			],
			'test-string-no-html'    => [
				'type'       => 'string',
				'allow_html' => false,
			],
			'favorite_int'           => [
				'type' => 'int',
			],
			'favorite_float'         => [
				'type' => 'float',
			],
			'email'                  => [
				'type'     => 'email',
				'required' => true,
			],
			'slug'                   => [
				'type' => 'slug',
			],
			'type'                   => [
				'type'     => 'string',
				'pattern'  => '/cool|neat|awesome/',
				'required' => true,
			],
		];
	}
}

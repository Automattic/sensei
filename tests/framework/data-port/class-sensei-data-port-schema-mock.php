<?php
/**
 * This file contains the Sensei_Data_Port_Schema_Mock class.
 *
 * @package sensei
 */

class Sensei_Data_Port_Schema_Mock extends Sensei_Data_Port_Schema {

	public function get_schema() {
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
				'type'    => 'int',
				'default' => 0,
			],
			'favorite_float'         => [
				'type' => 'float',
			],
			'email'                  => [
				'type'     => 'email',
				'required' => true,
			],
			'slug'                   => [
				'type'    => 'slug',
				'default' => 'neat-slug',
			],
			'type'                   => [
				'type'     => 'string',
				'pattern'  => '/cool|neat|awesome/',
				'required' => true,
			],
		];
	}

	public function get_post_type() {
		return 'mock_post_type';
	}

	public function get_column_title() {
		return 'mock_title';
	}
}

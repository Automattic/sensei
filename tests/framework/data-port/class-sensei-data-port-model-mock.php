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

	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function set_post_id( $id ) {
		parent::set_post_id( $id );
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
}

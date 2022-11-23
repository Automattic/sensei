<?php
/**
 * File containing the Sensei_Data_Port_Course_Schema class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the expected data for a single course.
 */
class Sensei_Data_Port_Course_Schema extends Sensei_Data_Port_Schema {
	const POST_TYPE = 'course';

	const COLUMN_TITLE            = 'course';
	const COLUMN_DESCRIPTION      = 'description';
	const COLUMN_EXCERPT          = 'excerpt';
	const COLUMN_TEACHER_USERNAME = 'teacher username';
	const COLUMN_TEACHER_EMAIL    = 'teacher email';
	const COLUMN_LESSONS          = 'lessons';
	const COLUMN_MODULES          = 'modules';
	const COLUMN_PREREQUISITE     = 'prerequisite';
	const COLUMN_FEATURED         = 'featured';
	const COLUMN_CATEGORIES       = 'categories';
	const COLUMN_IMAGE            = 'image';
	const COLUMN_VIDEO            = 'video';
	const COLUMN_NOTIFICATIONS    = 'disable notifications';


	/**
	 * Implementation of get_schema as documented in superclass.
	 */
	public function get_schema() {
		return [
			self::COLUMN_ID               => [
				'type' => 'string',
			],
			self::COLUMN_TITLE            => [
				'type'       => 'string',
				'required'   => true,
				'allow_html' => true,
			],
			self::COLUMN_SLUG             => [
				'type' => 'slug',
			],
			self::COLUMN_DESCRIPTION      => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_EXCERPT          => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_TEACHER_USERNAME => [
				'type' => 'username',
			],
			self::COLUMN_TEACHER_EMAIL    => [
				'type' => 'email',
			],
			self::COLUMN_LESSONS          => [
				'type' => 'string',
			],
			self::COLUMN_MODULES          => [
				'type' => 'string',
			],
			self::COLUMN_PREREQUISITE     => [
				'type' => 'string',
			],
			self::COLUMN_FEATURED         => [
				'type'    => 'bool',
				'default' => false,
			],
			self::COLUMN_CATEGORIES       => [
				'type' => 'string',
			],
			self::COLUMN_IMAGE            => [
				'type'       => 'url-or-file',
				'mime_types' => $this->get_allowed_mime_types( 'image' ),
			],
			self::COLUMN_VIDEO            => [
				'type' => 'video',
			],
			self::COLUMN_NOTIFICATIONS    => [
				'type'    => 'bool',
				'default' => false,
			],
		];
	}

	/**
	 * Get course post type.
	 *
	 * @return string
	 */
	public function get_post_type() {
		return self::POST_TYPE;
	}

	/**
	 * Get the column name for the title.
	 *
	 * @return string
	 */
	public function get_column_title() {
		return self::COLUMN_TITLE;
	}
}

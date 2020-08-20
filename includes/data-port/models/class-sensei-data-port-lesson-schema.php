<?php
/**
 * File containing the Sensei_Data_Port_Lesson_Schema class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the expected data to port to/from.
 */
class Sensei_Data_Port_Lesson_Schema extends Sensei_Data_Port_Schema {
	const POST_TYPE = 'lesson';

	const COLUMN_ID             = 'id';
	const COLUMN_TITLE          = 'lesson';
	const COLUMN_SLUG           = 'slug';
	const COLUMN_DESCRIPTION    = 'description';
	const COLUMN_EXCERPT        = 'excerpt';
	const COLUMN_STATUS         = 'status';
	const COLUMN_MODULE         = 'module';
	const COLUMN_PREREQUISITE   = 'prerequisite';
	const COLUMN_PREVIEW        = 'preview';
	const COLUMN_TAGS           = 'tags';
	const COLUMN_IMAGE          = 'image';
	const COLUMN_LENGTH         = 'length';
	const COLUMN_COMPLEXITY     = 'complexity';
	const COLUMN_VIDEO          = 'video';
	const COLUMN_PASS_REQUIRED  = 'pass required';
	const COLUMN_PASSMARK       = 'passmark';
	const COLUMN_NUM_QUESTIONS  = 'number of questions';
	const COLUMN_RANDOMIZE      = 'random question order';
	const COLUMN_AUTO_GRADE     = 'auto-grade';
	const COLUMN_QUIZ_RESET     = 'quiz reset';
	const COLUMN_ALLOW_COMMENTS = 'allow comments';
	const COLUMN_QUESTIONS      = 'questions';

	/**
	 * Implementation of get_schema as documented in superclass.
	 */
	public function get_schema() {
		return [
			self::COLUMN_ID             => [
				'type' => 'string',
			],
			self::COLUMN_TITLE          => [
				'type'       => 'string',
				'required'   => true,
				'allow_html' => true,
			],
			self::COLUMN_SLUG           => [
				'type' => 'slug',
			],
			self::COLUMN_DESCRIPTION    => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_EXCERPT        => [
				'type'       => 'string',
				'allow_html' => true,
			],
			self::COLUMN_STATUS         => [
				'type'    => 'string',
				'default' => 'draft',
				'pattern' => '/^(publish|pending|draft|)$/',
			],
			self::COLUMN_MODULE         => [
				'type' => 'string',
			],
			self::COLUMN_PREREQUISITE   => [
				'type' => 'string',
			],
			self::COLUMN_PREVIEW        => [
				'type'    => 'bool',
				'default' => false,
			],
			self::COLUMN_TAGS           => [
				'type' => 'string',
			],
			self::COLUMN_IMAGE          => [
				'type'       => 'url-or-file',
				'mime_types' => $this->get_allowed_mime_types( 'image' ),
			],
			self::COLUMN_LENGTH         => [
				'type' => 'int',
			],
			self::COLUMN_COMPLEXITY     => [
				'type'    => 'string',
				'pattern' => '/^(easy|std|hard|)$/',
			],
			self::COLUMN_VIDEO          => [
				'type' => 'video',
			],
			self::COLUMN_PASS_REQUIRED  => [
				'type'    => 'bool',
				'default' => false,
			],
			self::COLUMN_PASSMARK       => [
				'type' => 'float',
			],
			self::COLUMN_NUM_QUESTIONS  => [
				'type' => 'int',
			],
			self::COLUMN_RANDOMIZE      => [
				'type'    => 'bool',
				'default' => false,
			],
			self::COLUMN_AUTO_GRADE     => [
				'type'    => 'bool',
				'default' => true,
			],
			self::COLUMN_QUIZ_RESET     => [
				'type'    => 'bool',
				'default' => false,
			],
			self::COLUMN_ALLOW_COMMENTS => [
				'type'    => 'bool',
				'default' => true,
			],
			self::COLUMN_QUESTIONS      => [
				'type' => 'string',
			],
		];
	}

	/**
	 * Get lesson post type.
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

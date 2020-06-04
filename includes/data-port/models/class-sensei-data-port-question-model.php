<?php
/**
 * File containing the Sensei_Data_Port_Model class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the expected data to port to/from and handles the port.
 */
class Sensei_Data_Port_Question_Model extends Sensei_Data_Port_Model {
	const POST_TYPE = 'question';

	const COLUMN_QUESTION        = 'question';
	const COLUMN_ANSWER          = 'answer';
	const COLUMN_ID              = 'id';
	const COLUMN_SLUG            = 'slug';
	const COLUMN_DESCRIPTION     = 'description';
	const COLUMN_STATUS          = 'status';
	const COLUMN_TYPE            = 'type';
	const COLUMN_GRADE           = 'grade';
	const COLUMN_RANDOMISE       = 'randomise';
	const COLUMN_MEDIA           = 'media';
	const COLUMN_CATEGORIES      = 'categories';
	const COLUMN_FEEDBACK        = 'feedback';
	const COLUMN_TEXT_BEFORE_GAP = 'text before gap';
	const COLUMN_GAP             = 'gap';
	const COLUMN_TEXT_AFTER_GAP  = 'text after gap';
	const COLUMN_UPLOAD_NOTES    = 'upload notes';
	const COLUMN_TEACHER_NOTES   = 'teacher notes';

	/**
	 * Check to see if the post already exists in the database.
	 *
	 * @return int
	 */
	protected function get_existing_post_id() {
		$post_id = null;
		$data    = $this->get_data();

		if ( ! empty( $data[ self::COLUMN_SLUG ] ) ) {
			$existing_posts = get_posts(
				[
					'post_type'      => self::POST_TYPE,
					'name'           => $data[ self::COLUMN_SLUG ],
					'posts_per_page' => 1,
				]
			);

			if ( ! empty( $existing_posts[0] ) ) {
				return $existing_posts[0]->ID;
			}
		}

		return $post_id;
	}

	/**
	 * Create a new question or update an existing question.
	 *
	 * @return true|WP_Error
	 */
	public function sync_post() {
		// @todo Implement magic.

		return true;
	}

	/**
	 * Get the schema for the data type.
	 *
	 * @return array {
	 *     @type array $$field_name {
	 *          @type string $type       Type of data. Options: string, int, float, bool, slug, ref, email, url.
	 *          @type string $pattern    Regular expression that the value should match (Optional).
	 *          @type mixed  $default    Default value if not set or invalid. Default is `null` (Optional).
	 *          @type bool   $required   True if a non-empty value is required. Default is `false` (Optional).
	 *          @type bool   $allow_html True if HTML should be allowed. Default is `false` (Optional).
	 *     }
	 * }
	 */
	public static function get_schema() {
		return [
			self::COLUMN_QUESTION        => [
				'type'       => 'string',
				'required'   => true,
				'allow_html' => true,
			],
			self::COLUMN_ANSWER          => [
				'type'     => 'string',
				'required' => true,
			],
			self::COLUMN_ID              => [
				'type' => 'string',
			],
			self::COLUMN_SLUG            => [
				'type' => 'slug',
			],
			self::COLUMN_DESCRIPTION     => [
				'type' => 'string',
			],
			self::COLUMN_STATUS          => [
				'type' => 'string',
			],
			self::COLUMN_TYPE            => [
				'type'    => 'string',
				'default' => 'multiple-choice',
				'pattern' => 'boolean|gap\-fill|single\-line|multiple\-line|file\-upload',
			],
			self::COLUMN_GRADE           => [
				'type'    => 'int',
				'default' => 1,
			],
			self::COLUMN_RANDOMISE       => [
				'type'    => 'bool',
				'default' => true,
			],
			self::COLUMN_MEDIA           => [
				'type' => 'url',
			],
			self::COLUMN_CATEGORIES      => [
				'type' => 'string',
			],
			self::COLUMN_FEEDBACK        => [
				'type' => 'string',
			],
			self::COLUMN_TEXT_BEFORE_GAP => [
				'type' => 'string',
			],
			self::COLUMN_GAP             => [
				'type' => 'string',
			],
			self::COLUMN_TEXT_AFTER_GAP  => [
				'type' => 'string',
			],
			self::COLUMN_UPLOAD_NOTES    => [
				'type' => 'string',
			],
			self::COLUMN_TEACHER_NOTES   => [
				'type' => 'string',
			],
		];
	}
}

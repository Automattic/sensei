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
class Sensei_Data_Port_Course_Model extends Sensei_Data_Port_Model {
	const POST_TYPE = 'course';

	const COLUMN_ID               = 'id';
	const COLUMN_COURSE           = 'course';
	const COLUMN_SLUG             = 'slug';
	const COLUMN_DESCRIPTION      = 'description';
	const COLUMN_EXCERPT          = 'excerpt';
	const COLUMN_TEACHER_USERNAME = 'teacher username';
	const COLUMN_TEACHER_EMAIL    = 'teacher email';
	const COLUMN_MODULES          = 'modules';
	const COLUMN_PREREQUISITE     = 'prerequisite';
	const COLUMN_FEATURED         = 'featured';
	const COLUMN_CATEGORIES       = 'categories';
	const COLUMN_IMAGE            = 'image';
	const COLUMN_VIDEO            = 'video';
	const COLUMN_NOTIFICATIONS    = 'notifications';

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
		$post_id = wp_insert_post( $this->get_course_args() );
		error_log('in proasdfadsfadsfds');
		return true;
	}

	private function get_course_args() {
		$course_data = $this->get_data();

		$author = get_current_user_id();

		if ( ! empty( $course_data[ self::COLUMN_TEACHER_USERNAME ] ) ) {
			$author = Sensei_Data_Port_Utilities::create_user( $course_data[ self::COLUMN_TEACHER_USERNAME ], $course_data[ self::COLUMN_TEACHER_EMAIL ] );
		}

		$args = [
			'ID'           => $this->get_post_id(),
			'post_author'  => $author,
			'post_content' => $course_data[ self::COLUMN_DESCRIPTION ],
			'post_title'   => $course_data[ self::COLUMN_COURSE ],
			'post_excerpt' => $course_data[ self::COLUMN_EXCERPT ],
			'post_status'  => 'draft',
			'post_type'    => 'course',
			'post_name'    => $course_data[ self::COLUMN_SLUG ],
			'meta_input'   => $this->get_course_meta(),
		];

		return $args;
	}

	private function get_course_meta(){
		$course_data = $this->get_data();

		return [
			'course_featured'             => $course_data[ self::COLUMN_FEATURED ],
			'course_video_embed'          => $course_data[ self::COLUMN_VIDEO ],
			'_sensei_course_notification' => $course_data[ self::COLUMN_NOTIFICATIONS ],
		];
	}

	/**
	 * Implementation of get_schema as documented in superclass.
	 */
	public static function get_schema() {
		return [
			self::COLUMN_ID               => [
				'type' => 'string',
			],
			self::COLUMN_COURSE           => [
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
				'type' => 'string',
			],
			self::COLUMN_TEACHER_EMAIL    => [
				'type' => 'email',
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
				'type' => 'url',
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
}

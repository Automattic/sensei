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
	 * The default author to be used in courses if none is provided.
	 *
	 * @var int
	 */
	private $default_author;

	/**
	 * Set up item from an array.
	 *
	 * @param array $data            Data to restore item from.
	 * @param int   $default_author  The default author.
	 *
	 * @return Sensei_Data_Port_Model
	 */
	public static function from_source_array( $data, $default_author = 0 ) {
		$course_model                 = parent::from_source_array( $data );
		$course_model->default_author = $default_author;

		return $course_model;
	}


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

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 0 === $post_id ) {
			return new WP_Error(
				'sensei_data_port_creation_failure',
				__( 'Course insertion failed.', 'sensei-lms' )
			);
		}

		$result = $this->add_thumbnail_to_post( self::COLUMN_IMAGE, $post_id );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Retrieve the arguments for wp_insert_post for this course.
	 *
	 * @return array
	 */
	private function get_course_args() {
		$author = $this->default_author;

		$teacher_username = $this->get_value( self::COLUMN_TEACHER_USERNAME );

		if ( ! empty( $teacher_username ) ) {
			$author = Sensei_Data_Port_Utilities::create_user( $teacher_username, $this->get_value( self::COLUMN_TEACHER_EMAIL ) );
		}

		$args = [
			'ID'          => $this->get_post_id(),
			'post_author' => $author,
			'post_status' => 'draft',
			'post_type'   => 'course',
		];

		$value = $this->get_value( self::COLUMN_DESCRIPTION );
		if ( null !== $value ) {
			$args['post_content'] = $value;
		}

		$value = $this->get_value( self::COLUMN_COURSE );
		if ( null !== $value ) {
			$args['post_title'] = $value;
		}

		$value = $this->get_value( self::COLUMN_EXCERPT );
		if ( null !== $value ) {
			$args['post_excerpt'] = $value;
		}

		$value = $this->get_value( self::COLUMN_SLUG );
		if ( null !== $value ) {
			$args['post_name'] = $value;
		}

		$meta = $this->get_course_meta();
		if ( ! empty( $meta ) ) {
			$args['meta_input'] = $meta;
		}

		return $args;
	}

	/**
	 * * Retrieve the meta arguments to be used in wp_insert_post.
	 *
	 * @return array
	 */
	private function get_course_meta() {
		$meta = [];

		$value = $this->get_value( self::COLUMN_FEATURED );
		if ( null !== $value ) {
			$meta['course_featured'] = $value;
		}

		$value = $this->get_value( self::COLUMN_VIDEO );
		if ( null !== $value ) {
			$meta['course_video_embed'] = $value;
		}

		$value = $this->get_value( self::COLUMN_NOTIFICATIONS );
		if ( null !== $value ) {
			$meta['_sensei_course_notification'] = $value;
		}

		return $meta;
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

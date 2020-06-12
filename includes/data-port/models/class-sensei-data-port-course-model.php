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
					'post_status'    => 'any',
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
		$teacher = $this->get_default_author();

		$teacher_username = $this->get_value( self::COLUMN_TEACHER_USERNAME );

		if ( ! empty( $teacher_username ) ) {
			$teacher = Sensei_Data_Port_Utilities::create_user( $teacher_username, $this->get_value( self::COLUMN_TEACHER_EMAIL ) );
		}

		$post_id = wp_insert_post( $this->get_course_args( $teacher ) );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 0 === $post_id ) {
			return new WP_Error(
				'sensei_data_port_creation_failure',
				__( 'Course insertion failed.', 'sensei-lms' )
			);
		}

		$result = $this->set_course_terms( self::COLUMN_MODULES, $post_id, 'module', $teacher );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->set_course_terms( self::COLUMN_CATEGORIES, $post_id, 'course-category' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->add_thumbnail_to_post( self::COLUMN_IMAGE, $post_id );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Retrieve the arguments for wp_insert_post for this course.
	 *
	 * @param int $teacher  The teacher id.
	 *
	 * @return array
	 */
	private function get_course_args( $teacher ) {

		$args = [
			'ID'          => $this->get_post_id(),
			'post_author' => $teacher,
			'post_type'   => 'course',
		];

		if ( empty( $this->get_post_id() ) ) {
			$args['post_status'] = 'draft';
		}

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
	 * Updates the terms of a course. The old terms are overwritten.
	 *
	 * @param string $column_name  The CSV column name which contains the terms.
	 * @param int    $course_id    The course id.
	 * @param string $taxonomy     The taxonomy of the terms.
	 * @param int    $teacher      The teacher id.
	 *
	 * @return array|bool|WP_Error
	 */
	private function set_course_terms( $column_name, $course_id, $taxonomy, $teacher = null ) {
		$new_terms = $this->get_value( $column_name );

		if ( null === $new_terms ) {
			return true;
		}

		if ( '' === $new_terms ) {
			$this->delete_course_terms( $course_id, $taxonomy );
			return true;
		}

		$new_terms = Sensei_Data_Port_Utilities::split_list_safely( $new_terms, true );
		$terms     = [];

		foreach ( $new_terms as $new_term ) {
			$term = Sensei_Data_Port_Utilities::get_term( $new_term, $taxonomy, $teacher );

			if ( false === $term ) {
				return new WP_Error(
					'sensei_data_port_creation_failure',
					// translators: Placeholder is the term which errored.
					sprintf( __( 'Error getting term: %s.', 'sensei-lms' ), $new_term )
				);
			}

			$terms[] = $term;
		}

		$new_term_ids = wp_list_pluck( $terms, 'term_id' );

		$result = wp_set_object_terms( $course_id, $new_term_ids, $taxonomy );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( 'module' === $taxonomy ) {
			$new_module_order = array_map(
				function( $term_id ) {
					return (string) $term_id;
				},
				$new_term_ids
			);

			update_post_meta( $course_id, '_module_order', $new_module_order );
		}

		return true;
	}

	/**
	 * Deletes the terms of a course.
	 *
	 * @param int    $course_id  The course id.
	 * @param string $taxonomy   The taxonomy to delete the terms for.
	 */
	private function delete_course_terms( $course_id, $taxonomy ) {
		wp_delete_object_term_relationships( $course_id, $taxonomy );

		if ( 'module' === $taxonomy ) {
			delete_post_meta( $course_id, '_module_order' );
		}
	}

	/**
	 * Get the data to return with any errors.
	 *
	 * @param array $data Base error data to pass along.
	 *
	 * @return array
	 */
	public function get_error_data( $data = [] ) {
		$entry_id = $this->get_value( self::COLUMN_ID );
		if ( $entry_id ) {
			$data['entry_id'] = $entry_id;
		}

		$entry_title = $this->get_value( self::COLUMN_COURSE );
		if ( $entry_id ) {
			$data['entry_title'] = $entry_title;
		}

		$post_id = $this->get_post_id();
		if ( $post_id ) {
			$data['post_id'] = $post_id;
		}

		return $data;
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
				'type' => 'username',
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

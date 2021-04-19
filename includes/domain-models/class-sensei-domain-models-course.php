<?php
/**
 * Course Domain Model
 *
 * @package Sensei\Domain Models\Model\Course
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Course domain model class.
 *
 * @deprecated 3.11.0
 *
 * @since 1.9.13
 */
class Sensei_Domain_Models_Course extends Sensei_Domain_Models_Model_Abstract {
	/**
	 * Declares course fields.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return array Fields
	 */
	public static function declare_fields() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return array(
			self::field()
				->with_name( 'id' )
				->map_from( 'ID' )
				->with_value_type( 'integer' )
				->with_description( __( 'Unique identifier for the object.', 'sensei-lms' ) )
				->with_before_return( 'as_uint' ),
			self::field()
				->with_name( 'title' )
				->map_from( 'post_title' )
				->with_value_type( 'string' )
				->with_description( __( 'The course title.', 'sensei-lms' ) )
				->required( true ),
			self::field()
				->with_name( 'author' )
				->map_from( 'post_author' )
				->with_value_type( 'integer' )
				->with_validations( 'validate_author' )
				->with_description( __( 'The author identifier.', 'sensei-lms' ) )
				->with_default_value( get_current_user_id() )
				->with_before_return( 'as_uint' ),
			self::field()
				->with_name( 'content' )
				->with_value_type( 'string' )
				->with_description( __( 'The course content.', 'sensei-lms' ) )
				->map_from( 'post_content' ),
			self::field()
				->with_name( 'excerpt' )
				->with_value_type( 'string' )
				->with_description( __( 'The course excerpt.', 'sensei-lms' ) )
				->map_from( 'post_excerpt' ),
			self::field()
				->with_name( 'type' )
				->with_value_type( 'string' )
				->with_default_value( 'course' )
				->map_from( 'post_type' ),
			self::field()
				->with_name( 'status' )
				->with_value_type( 'string' )
				->with_validations( 'validate_status' )
				->with_description( __( 'The course status.', 'sensei-lms' ) )
				->map_from( 'post_status' ),

			self::derived_field()
				->with_name( 'modules' )
				->map_from( 'course_module_ids' )
				->with_description( __( 'The course module ids.', 'sensei-lms' ) )
				->with_json_name( 'module_ids' ),
			self::derived_field()
				->with_name( 'module_order' )
				->with_description( __( 'The course module id order.', 'sensei-lms' ) )
				->map_from( 'module_order' ),
			self::derived_field()
				->with_name( 'lessons' )
				->with_description( __( 'The course lessons.', 'sensei-lms' ) )
				->map_from( 'course_lessons' )
				->not_visible(),

			self::meta_field()
				->with_name( 'prerequisite' )
				->map_from( '_course_prerequisite' )
				->with_description( __( 'The course prerequisite.', 'sensei-lms' ) )
				->with_before_return( 'as_nullable_uint' ),
			self::meta_field()
				->with_name( 'featured' )
				->map_from( '_course_featured' )
				->with_description( __( 'Is the course featured.', 'sensei-lms' ) )
				->with_value_type( 'boolean' )
				->with_before_return( 'as_bool' )
				->with_json_name( 'is_featured' ),
			self::meta_field()
				->with_name( 'video_embed' )
				->with_description( __( 'The course video embed html.', 'sensei-lms' ) )
				->map_from( '_course_video_embed' ),
			self::meta_field()
				->with_name( 'woocommerce_product' )
				->map_from( '_course_woocommerce_product' )
				->with_description( __( 'The product associated with this course.', 'sensei-lms' ) )
				->with_json_name( 'woocommerce_product_id' )
				->with_before_return( 'as_nullable_uint' ),
			self::meta_field()
				->with_name( 'lesson_order' )
				->map_from( '_lesson_order' ),
		);
	}

	/**
	 * Gets the course ID.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return int Course ID
	 */
	public function get_id() {
		_deprecated_function( __METHOD__, '3.11.0' );

		return $this->id;
	}

	/**
	 * Gets the module IDs.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return array Module IDs
	 */
	protected function course_module_ids() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$modules = Sensei()->modules->get_course_modules( absint( $this->id ) );
		return array_map( 'absint', wp_list_pluck( $modules, 'term_id' ) );
	}

	/**
	 * Gets module order for a course.
	 *
	 * @deprecated 3.11.0
	 *
	 * @return array Module order or empty array if not set.
	 */
	protected function module_order() {
		_deprecated_function( __METHOD__, '3.11.0' );

		$modules = Sensei()->modules->get_course_module_order( absint( $this->id ) );
		return ( empty( $modules ) ) ? array() : array_map( 'absint', $modules );
	}

	/**
	 * Validates the course author.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param int $author_id Author ID.
	 * @return bool|WP_Error true if the author is valid, WP_Error on failure.
	 */
	protected function validate_author( $author_id ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		$author = $this->get_author( $author_id );
		if ( null === $author ) {
			return new WP_Error( 'invalid-author-id', __( 'Invalid author id', 'sensei-lms' ) );
		}
		// the author should be able to create courses.
		if ( false === user_can( $author, 'create_courses' ) ) {
			return new WP_Error( 'invalid-author-permissions', __( 'Invalid author permissions', 'sensei-lms' ) );
		}
		return true;
	}

	/**
	 * Validates the course status.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param string $status Course status.
	 * @return bool|WP_Error true if the status is valid, WP_Error on failure.
	 */
	protected function validate_status( $status ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		if ( 'publish' === $status ) {
			$author_id = $this->author;
			if ( empty( $author_id ) ) {
				return new WP_Error( 'missing-author-id', __( 'Cannot publish when author is missing', 'sensei-lms' ) );
			}
			$author = $this->get_author( $author_id );
			// the author should be able to publish courses.
			if ( false === user_can( $author, 'publish_courses' ) ) {
				return new WP_Error( 'invalid-status-permissions', __( 'Author Cannot publish courses', 'sensei-lms' ) );
			}
		}

		return true;
	}

	/**
	 * Gets the author.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param int $author_id Author ID.
	 * @return WP_User|false User object on success, false on failure.
	 */
	protected function get_author( $author_id ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return Sensei_Domain_Models_Registry::get_instance()
			->get_data_store( 'users' )
			->get_entity( $author_id );
	}

	/**
	 * Generates a validation error.
	 *
	 * @deprecated 3.11.0
	 *
	 * @param array $error_data List of data for error codes.
	 * @return WP_Error Validation error.
	 */
	protected function validation_error( $error_data ) {
		_deprecated_function( __METHOD__, '3.11.0' );

		return new WP_Error( 'validation-error', __( 'Validation Error', 'sensei-lms' ), $error_data );
	}
}

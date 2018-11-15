<?php
/**
 * Course Data Store
 *
 * @package Sensei\Domain Models\Data Store\Course
 * @since 1.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Course data store class.
 *
 * @since 1.9.13
 */
class Sensei_Domain_Models_Course_Data_Store_Cpt implements Sensei_Domain_Models_Data_Store {
	/**
	 * Deletes a course.
	 *
	 * @param Sensei_Domain_Models_Course $course Course model.
	 * @param array                       $args Course deletion arguments.
	 */
	public function delete( $course, $args = array() ) {
		$id = $course->get_id();

		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( $args['force_delete'] ) {
			wp_delete_post( $course->get_id() );
			$course->set_value( 'id', 0 );
			do_action( 'sensei_delete_course', $id );
		} else {
			wp_trash_post( $course->get_id() );
			$course->set_value( 'status', 'trash' );
			do_action( 'sensei_trash_course', $id );
		}
	}

	/**
	 * Inserts or updates a course.
	 *
	 * @param Sensei_Domain_Models_Course $entity Course model.
	 * @param array                       $fields Elements to update or insert.
	 * @param array                       $meta_fields Field values to update or insert.
	 * @return int|WP_Error Post ID on success. Value 0 or WP_Error on failure.
	 */
	public function upsert( $entity, $fields, $meta_fields = array() ) {
		// $fields['meta_input'] = $meta_fields;
		$success = wp_insert_post( $fields, true );
		if ( is_wp_error( $success ) ) {
			// todo: something wrong.
			return $success;
		}
		return absint( $success );
	}

	/**
	 * Gets all courses.
	 *
	 * @return array List of courses.
	 */
	public function get_entities() {
		$query = new WP_Query(
			array(
				'post_type'   => 'course',
				'post_status' => 'any',
			)
		);
		return $query->get_posts();
	}

	/**
	 * Gets a course.
	 *
	 * @param int|string $course_id Course ID.
	 * @return array|null Course as array on success, null otherwise.
	 */
	public function get_entity( $course_id ) {
		$course = get_post( absint( $course_id ) );
		return ! empty( $course ) && 'course' === $course->post_type ? $course->to_array() : null;
	}

	/**
	 * Gets a meta data field for a course.
	 *
	 * @param Sensei_Domain_Models_Course            $course Course model.
	 * @param Sensei_Domain_Models_Field_Declaration $field_declaration Course field declaration.
	 * @return mixed Value of meta data field.
	 */
	public function get_meta_field_value( $course, $field_declaration ) {
		$map_from = $field_declaration->get_name_to_map_from();
		return get_post_meta( $course->get_id(), $map_from, true );
	}
}

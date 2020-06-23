<?php
/**
 * File containing the Sensei_Import_Course_Model class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for importing the data for a single course.
 */
class Sensei_Import_Course_Model extends Sensei_Import_Model {

	/**
	 * Create a new question or update an existing question.
	 *
	 * @return true|WP_Error
	 */
	public function sync_post() {
		$teacher = $this->get_default_author();

		$teacher_username = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME );

		if ( ! empty( $teacher_username ) ) {
			$teacher = Sensei_Data_Port_Utilities::create_user( $teacher_username, $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL ), 'teacher' );

			if ( is_wp_error( $teacher ) ) {
				return $teacher;
			}
		}

		remove_action( 'transition_post_status', array( Sensei()->teacher, 'notify_admin_teacher_course_creation' ), 10 );
		remove_action( 'save_post', array( Sensei()->course, 'save_course_notification_meta_box' ) );
		remove_action( 'save_post', array( Sensei()->course, 'meta_box_save' ) );

		$post_id = wp_insert_post( $this->get_course_args( $teacher ), true );

		add_action( 'transition_post_status', array( Sensei()->teacher, 'notify_admin_teacher_course_creation' ), 10, 3 );
		add_action( 'save_post', array( Sensei()->course, 'save_course_notification_meta_box' ) );
		add_action( 'save_post', array( Sensei()->course, 'meta_box_save' ) );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 0 === $post_id ) {
			return new WP_Error(
				'sensei_data_port_creation_failure',
				__( 'Course insertion failed.', 'sensei-lms' )
			);
		}

		$this->set_post_id( $post_id );
		$this->store_import_id();

		$result = $this->set_course_terms( Sensei_Data_Port_Course_Schema::COLUMN_MODULES, 'module', $teacher );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->set_course_terms( Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES, 'course-category' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = $this->add_thumbnail_to_post( Sensei_Data_Port_Course_Schema::COLUMN_IMAGE );

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

		if ( $this->is_new() ) {
			$args['post_status'] = 'draft';
		}

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION );
		if ( null !== $value ) {
			$args['post_content'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_TITLE );
		if ( null !== $value ) {
			$args['post_title'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_EXCERPT );
		if ( null !== $value ) {
			$args['post_excerpt'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_SLUG );
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
	 * Retrieve the meta arguments to be used in wp_insert_post.
	 *
	 * @return array
	 */
	private function get_course_meta() {
		$meta = [];

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_FEATURED );
		if ( null !== $value ) {
			$meta['_course_featured'] = true === $value ? 'featured' : '';
		}

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_VIDEO );
		if ( null !== $value ) {
			$meta['_course_video_embed'] = $value;
		}

		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_NOTIFICATIONS );
		if ( null !== $value ) {
			$meta['disable_notification'] = $value;
		}

		return $meta;
	}

	/**
	 * Updates the terms of a course. The old terms are overwritten.
	 *
	 * @param string $column_name  The CSV column name which contains the terms.
	 * @param string $taxonomy     The taxonomy of the terms.
	 * @param int    $teacher      The teacher id.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function set_course_terms( $column_name, $taxonomy, $teacher = null ) {
		$course_id = $this->get_post_id();
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
			$new_module_order = array_map( 'strval', $new_term_ids );
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
}

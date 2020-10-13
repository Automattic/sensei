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
 *
 * @property Sensei_Import_Courses $task Task for importing courses.
 */
class Sensei_Import_Course_Model extends Sensei_Import_Model {
	const MODEL_KEY = 'course';

	/**
	 * Get the model key to identify items in log entries.
	 *
	 * @return string
	 */
	public function get_model_key() {
		return self::MODEL_KEY;
	}

	/**
	 * Create a new question or update an existing question.
	 *
	 * @return true|WP_Error
	 */
	public function sync_post() {
		$teacher = $this->get_default_author();

		$teacher_username = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_USERNAME );
		$teacher_email    = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_TEACHER_EMAIL );

		if ( ! empty( $teacher_username ) ) {
			$teacher_user = Sensei_Data_Port_Utilities::create_user( $teacher_username, $teacher_email, 'teacher' );

			if ( is_wp_error( $teacher_user ) ) {
				return $teacher_user;
			}

			if ( ! empty( $teacher_email ) && $teacher_email !== $teacher_user->user_email ) {
				$this->add_line_warning(
					__( 'The user with the supplied username has a different email. Teacher email will be ignored.', 'sensei-lms' ),
					[
						'code' => 'sensei_data_port_wrong_teacher_email',
					]
				);
			}

			$teacher = $teacher_user->ID;
		} else {
			if ( ! empty( $teacher_email ) ) {
				$this->add_line_warning(
					__( 'Teacher Username is empty. Course teacher is set to the currently logged in user.', 'sensei-lms' ),
					[
						'code' => 'sensei_data_port_no_teacher',
					]
				);
			}
		}

		remove_action( 'transition_post_status', array( Sensei()->teacher, 'notify_admin_teacher_course_creation' ), 10 );
		remove_action( 'save_post', array( Sensei()->course, 'save_course_notification_meta_box' ) );
		remove_action( 'save_post', array( Sensei()->course, 'meta_box_save' ) );

		$post_args = $this->get_course_args( $teacher );
		$post_id   = wp_insert_post( $post_args, true );

		add_action( 'transition_post_status', array( Sensei()->teacher, 'notify_admin_teacher_course_creation' ), 10, 3 );
		add_action( 'save_post', array( Sensei()->course, 'save_course_notification_meta_box' ) );
		add_action( 'save_post', array( Sensei()->course, 'meta_box_save' ) );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( 0 === $post_id ) {
			return new WP_Error(
				'sensei_data_port_creation_failure',
				__( 'Course creation failed.', 'sensei-lms' )
			);
		}

		$this->set_post_id( $post_id );
		$this->store_import_id();

		$result = $this->add_thumbnail_to_post( Sensei_Data_Port_Course_Schema::COLUMN_IMAGE );
		if ( $result instanceof WP_Error ) {
			$this->add_line_warning(
				$result->get_error_message(),
				[
					'code' => $result->get_error_code(),
				]
			);
		}

		$prerequisite = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_PREREQUISITE );
		if ( $prerequisite ) {
			$this->task->add_prerequisite_task( $post_id, $prerequisite, $this->line_number, $post_args['post_title'] );
		} elseif ( '' === $prerequisite ) {
			delete_post_meta( $post_id, '_course_prerequisite' );
		}

		$this->set_course_terms( Sensei_Data_Port_Course_Schema::COLUMN_MODULES, 'module', $teacher );
		$this->set_course_terms( Sensei_Data_Port_Course_Schema::COLUMN_CATEGORIES, 'course-category' );

		// We need to set the post content after modules have been created in order to map module ids properly.
		$value = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_DESCRIPTION );
		if ( null !== $value ) {
			$migrator = new Sensei_Import_Block_Migrator( $this->get_post_id(), $this->task, $this );

			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => $migrator->migrate( $value ),
				]
			);
		}

		$course_lessons = $this->get_value( Sensei_Data_Port_Course_Schema::COLUMN_LESSONS );
		if ( null !== $course_lessons ) {
			/**
			 * Associations task object for this job.
			 *
			 * @var Sensei_Import_Associations $associations_task
			 */
			$associations_task = $this->task->get_job()->get_associations_task();
			$associations_task->add_course_lessons(
				$this->get_post_id(),
				$course_lessons,
				$this->line_number,
				$this->get_value( $this->schema->get_column_title() )
			);
		}

		return true;
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
	 */
	private function set_course_terms( $column_name, $taxonomy, $teacher = null ) {
		$course_id = $this->get_post_id();
		$new_terms = $this->get_value( $column_name );

		if ( null === $new_terms ) {
			return;
		}

		if ( '' === $new_terms ) {
			$this->delete_course_terms( $course_id, $taxonomy );
			return;
		}

		$new_terms    = Sensei_Data_Port_Utilities::split_list_safely( $new_terms, true );
		$terms        = [];
		$failed_terms = [];

		foreach ( $new_terms as $new_term ) {
			$term = Sensei_Data_Port_Utilities::get_term( $new_term, $taxonomy, $teacher );

			if ( false === $term ) {
				$failed_terms[] = $new_term;
				continue;
			}

			$terms[] = $term;
		}

		if ( ! empty( $failed_terms ) ) {
			$this->add_line_warning(
				sprintf(
					// translators: Placeholder is comma separated list of terms that failed to save.
					__( 'The following terms failed to save: %s', 'sensei-lms' ),
					implode( ', ', $failed_terms )
				),
				[
					'code' => 'sensei_data_port_course_terms_failed_to_save',
				]
			);
		}

		$new_term_ids = wp_list_pluck( $terms, 'term_id' );
		$result       = wp_set_object_terms( $course_id, $new_term_ids, $taxonomy );

		if ( is_wp_error( $result ) ) {
			$this->add_line_warning(
				$result->get_error_message(),
				[
					'code' => $result->get_error_code(),
				]
			);
		} elseif ( 'module' === $taxonomy ) {
			$new_module_order = array_map( 'strval', $new_term_ids );
			update_post_meta( $course_id, '_module_order', $new_module_order );
		}
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

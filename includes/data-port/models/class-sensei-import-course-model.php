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
			$args['post_content'] =

			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => $this->migrate_post_contnet( $value ),
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

	/**
	 * Migrates the imported post content to use the ids of the newly created lessons and modules.
	 *
	 * @param string $post_content The post content.
	 *
	 * @return string The migrated post content.
	 */
	private function migrate_post_contnet( $post_content ) {
		if ( ! has_block( 'sensei-lms/course-outline', $post_content ) ) {
			return $post_content;
		}

		$blocks = parse_blocks( $post_content );

		$i = 0;
		foreach ( $blocks as $block ) {
			if ( 'sensei-lms/course-outline' === $block['blockName'] ) {
				$mapped_block = $this->map_outline_block_ids( $block );
				break;
			}
			$i++;
		}
		$blocks[ $i ] = $mapped_block;

		return serialize_blocks( $blocks );
	}

	/**
	 * Maps the ids of an outlined block to use the newly created values.
	 *
	 * @param array $outline_block The outline block.
	 *
	 * @return array The mapped block.
	 */
	private function map_outline_block_ids( $outline_block ) {
		if ( empty( $outline_block['innerBlocks'] ) ) {
			return $outline_block;
		}

		$mapped_inner_blocks = [];
		foreach ( $outline_block['innerBlocks'] as $inner_block ) {
			if ( 'sensei-lms/course-outline-module' === $inner_block['blockName'] ) {
				$mapped_block = $this->map_module_block_id( $inner_block );
			} elseif ( 'sensei-lms/course-outline-lesson' === $inner_block['blockName'] ) {
				$mapped_block = $this->map_lesson_block_id( $inner_block );
			} else {
				$mapped_block = $inner_block;
			}

			if ( false !== $mapped_block ) {
				$mapped_inner_blocks[] = $mapped_block;
			}
		}

		$outline_block['innerBlocks'] = $mapped_inner_blocks;

		return $outline_block;
	}

	/**
	 * Map the ids of a lesson block.
	 *
	 * @param array $lesson_block The lesson block.
	 *
	 * @return bool|array The lesson block or false if the id couldn't be mapped.
	 */
	private function map_lesson_block_id( $lesson_block ) {
		if ( empty( $lesson_block['attrs']['id'] ) ) {
			return false;
		}

		// We first check for the lesson id to be a lesson which was imported during the import process. If that fails
		// we check if the lesson already exists in the database. This could happen in case of a course update.
		$lesson_id = $this->task->get_job()->translate_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, 'id:' . $lesson_block['attrs']['id'] );
		if ( null === $lesson_id && null === $this->task->get_job()->translate_import_id( Sensei_Data_Port_Lesson_Schema::POST_TYPE, $lesson_block['attrs']['id'] ) ) {
			$this->add_line_warning(
				// translators: The %s is the lesson id.
				sprintf( __( 'Lesson with id %s which is referenced in course outline block not found.', 'sensei-lms' ), $lesson_block['attrs']['id'] ),
				[
					'code' => 'sensei_data_port_course_lesson_not_found',
				]
			);

			return false;
		}

		$lesson_block['attrs']['id'] = $lesson_id;

		return $lesson_block;
	}

	/**
	 * Map the ids of a module block.
	 *
	 * @param array $module_block The module block.
	 *
	 * @return bool|array The mapped module block or false if the block couldn't be mapped.
	 */
	private function map_module_block_id( $module_block ) {
		if ( empty( $module_block['attrs']['title'] ) ) {
			$this->add_line_warning(
				__( 'No title for module found.', 'sensei-lms' ),
				[
					'code' => 'sensei_data_port_module_title_not_found',
				]
			);

			return false;
		}

		$term = Sensei_Data_Port_Utilities::get_module_for_course( $module_block['attrs']['title'], $this->get_post_id() );

		if ( is_wp_error( $term ) ) {
			$this->add_line_warning( $term->get_error_message(), [ 'code' => $term->get_error_code() ] );

			return false;
		}

		$module_inner_blocks = [];

		foreach ( $module_block['innerBlocks'] as $inner_block ) {
			if ( 'sensei-lms/course-outline-lesson' === $inner_block['blockName'] ) {
				$mapped_lesson_block = $this->map_lesson_block_id( $inner_block );

				if ( false !== $mapped_lesson_block ) {
					$module_inner_blocks[] = $mapped_lesson_block;
				}
			} else {
				$module_inner_blocks[] = $inner_block;
			}
		}

		$module_block['attrs']['id'] = $term->term_id;
		$module_block['innerBlocks'] = $module_inner_blocks;

		return $module_block;
	}
}

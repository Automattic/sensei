<?php
/**
 * File containing the Sensei_Import_Courses class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles the import task for courses.
 */
class Sensei_Import_Courses extends Sensei_Import_File_Process_Task {
	use Sensei_Import_Prerequisite_Trait;

	/**
	 * Return a unique key for the task.
	 *
	 * @return string
	 */
	public function get_task_key() {
		return 'courses';
	}

	/**
	 * Get the model which handles this task.
	 *
	 * @param int   $line_number Line number for model.
	 * @param array $data        An associated array with the CSV line.
	 *
	 * @return Sensei_Import_Course_Model
	 */
	public function get_model( $line_number, $data ) {
		return Sensei_Import_Course_Model::from_source_array( $line_number, $data, new Sensei_Data_Port_Course_Schema(), $this );
	}

	/**
	 * Get the model key for this task.
	 *
	 * @return string
	 */
	public function get_model_key() {
		return Sensei_Import_Course_Model::MODEL_KEY;
	}

	/**
	 * Validate an uploaded source file before saving it.
	 *
	 * @param string $file_path File path of the file to validate.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_source_file( $file_path ) {
		$schema          = new Sensei_Data_Port_Course_Schema();
		$required_fields = $schema->get_required_fields();
		$optional_fields = $schema->get_optional_fields();

		return Sensei_Import_CSV_Reader::validate_csv_file( $file_path, $required_fields, $optional_fields );
	}

	/**
	 * Handle matching a prerequisite to a post.
	 *
	 * Note: Used by dynamic callback in `Sensei_Import_File_Process_Task::run_post_process_tasks`.
	 *
	 * @param array $task Prerequisite task arguments.
	 */
	protected function handle_prerequisite( $task ) {
		self::handle_prerequisite_helper(
			$task,
			'_course_prerequisite',
			Sensei_Data_Port_Course_Schema::POST_TYPE,
			Sensei_Import_Course_Model::MODEL_KEY
		);
	}

	/**
	 * Handle the post processing task of setting lesson modules.
	 *
	 * @access private
	 *
	 * @param array $task Task arguments.
	 */
	public function handle_lesson_module( $task ) {
		list( $lesson_id, $module_ref, $line_number, $post_title ) = $task;

		$lesson_id = (int) $lesson_id;
		$model_key = Sensei_Import_Lesson_Model::MODEL_KEY;

		$add_warning_helper = function( $message, $code ) use ( $model_key, $line_number, $lesson_id, $post_title ) {
			$error_data = [
				'line'        => $line_number,
				'type'        => $model_key,
				'post_id'     => $lesson_id,
				'entry_title' => $post_title,
				'code'        => $code,
			];

			$this->get_job()->add_line_warning(
				$model_key,
				$line_number,
				$message,
				$error_data
			);
		};

		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		if ( ! $course_id ) {
			$add_warning_helper(
				// translators: Placeholder is reference to module.
				sprintf( __( 'Unable to set the lesson module to "%s" because it does not have a course associated with it.', 'sensei-lms' ), $module_ref ),
				'sensei_data_port_lesson_module_no_course'
			);

			return;
		}

		$term = $this->get_lesson_module( $module_ref, $course_id );
		if ( is_wp_error( $term ) ) {
			$add_warning_helper( $term->get_error_message(), $term->get_error_code() );

			return;
		}

		$result = wp_set_object_terms( $lesson_id, [ $term->term_id ], 'module' );
		if ( is_wp_error( $result ) ) {
			$add_warning_helper( $result->get_error_message(), $result->get_error_code() );
		}
	}

	/**
	 * Helper method which gets a module by name and checks if the module can be applied to the lesson.
	 *
	 * @param string $module_name  The module name.
	 * @param int    $course_id    Course ID.
	 *
	 * @return WP_Error|WP_Term  WP_Error when the module can't be applied to the lesson, WP_Term otherwise.
	 */
	private function get_lesson_module( $module_name, $course_id ) {
		$module = get_term_by( 'name', $module_name, 'module' );

		if ( ! $module ) {
			return new WP_Error(
				'sensei_data_port_module_not_found',
				// translators: Placeholder is the term which errored.
				sprintf( __( 'Module does not exist: %s.', 'sensei-lms' ), $module_name )
			);
		}

		$course_modules = wp_list_pluck( wp_get_post_terms( $course_id, 'module' ), 'term_id' );

		if ( ! in_array( $module->term_id, $course_modules, true ) ) {
			return new WP_Error(
				'sensei_data_port_module_not_part_of_course',
				// translators: First placeholder is the term which errored, second is the course id.
				sprintf( __( 'Module %1$s is not part of course %2$s.', 'sensei-lms' ), $module_name, $course_id )
			);
		}

		return $module;
	}
}

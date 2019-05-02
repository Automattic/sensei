<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Sensei Lesson Modules Class
 *
 * Sensei Functionality for managing Modules on Lessons
 *
 * @package Content
 * @author Automattic
 *
 * @since 1.9.18
 */
class Sensei_Core_Lesson_Modules {

	private $lesson_id;

	public function __construct( $lesson_id ) {
		$parent_id = wp_is_post_revision( $lesson_id );

		// Ensure we are working with the Lesson post, not a revision.
		if ( $parent_id ) {
			$this->lesson_id = $parent_id;
		} else {
			$this->lesson_id = $lesson_id;
		}
	}

	/**
	 * Set the module on the lesson in the DB.
	 *
	 * If the module is not associated with the course that the lesson belongs
	 * to, the lesson's module will instead be unset. The third argument may be
	 * used to change which course to check against. This is useful when the
	 * course and module are being updated at the same time.
	 *
	 * @since 1.9.18
	 * @param integer|string $module_id ID of the new module
	 * @param integer|string $course_id (Optional) ID of the course to check against
	 */
	public function set_module( $module_id, $course_id = null ) {

		// Convert IDs to integers
		if ( $module_id || ! empty( $module_id ) ) {
			$module_id = intval( $module_id );
		}

		if ( $course_id ) {
			$course_id = intval( $course_id );
		} else {
			$course_id = get_post_meta( $this->lesson_id, '_lesson_course', true );
		}

		// Does the incoming module belong to the course?
		$module_exists_in_course = has_term( $module_id, $this->modules_taxonomy(), $course_id );

		// Check if the lesson is already assigned to a module.
		// Modules and lessons have 1 -> 1 relationship.
		// We delete existing module term relationships for this lesson if no
		// module is selected, or if the selected module does not exist in the
		// given course.
		if ( ! $module_id || empty( $module_id ) || ! $module_exists_in_course ) {
			wp_delete_object_term_relationships( $this->lesson_id, $this->modules_taxonomy() );
			return;
		}

		// Assign lesson to selected module
		wp_set_object_terms( $this->lesson_id, $module_id, $this->modules_taxonomy(), false );

		// Set default order for lesson inside module
		$order_module_key = '_order_module_' . $module_id;
		if ( ! get_post_meta( $this->lesson_id, $order_module_key, true ) ) {
			update_post_meta( $this->lesson_id, $order_module_key, 0 );
		}
	}

	// Get modules taxonomy
	private function modules_taxonomy() {
		return Sensei()->modules->taxonomy;
	}

}

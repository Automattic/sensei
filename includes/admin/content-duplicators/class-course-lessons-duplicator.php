<?php
/**
 * File containing the Course_Lessons_Duplicator class.
 *
 * @package sensei
 */

namespace Sensei\Admin\Content_Duplicators;

use WP_Post;

/**
 * Class Course_Lessons_Duplicator
 *
 * @since 4.21.0
 */
class Course_Lessons_Duplicator {

	/**
	 * Post duplicator insance.
	 *
	 * @var Post_Duplicator
	 */
	private Post_Duplicator $post_duplicator;

	/**
	 * Lesson quiz duplicator instance.
	 *
	 * @var Lesson_Quiz_Duplicator
	 */
	private Lesson_Quiz_Duplicator $lesson_quiz_duplicator;

	/**
	 * Course_Lessons_Duplicator contructor.
	 */
	public function __construct() {
		$this->post_duplicator        = new Post_Duplicator();
		$this->lesson_quiz_duplicator = new Lesson_Quiz_Duplicator();
	}

	/**
	 * Duplicate coures lessons.
	 *
	 * @param int $old_course_id Original course ID.
	 * @param int $new_course_id Destination courus ID.
	 * @return int Returns the number of duplicated lessons.
	 */
	public function duplicate( int $old_course_id, int $new_course_id ): int {
		$lessons              = Sensei()->course->course_lessons( $old_course_id, 'any' );
		$new_lesson_id_lookup = array();
		$lessons_to_update    = array();

		foreach ( $lessons as $lesson ) {
			$new_lesson = $this->post_duplicator->duplicate( $lesson, '', true );
			if ( ! $new_lesson ) {
				continue;
			}

			add_post_meta( $new_lesson->ID, '_lesson_course', $new_course_id );

			$update_prerequisite_object = $this->get_prerequisite_update_object( $lesson->ID, $new_lesson->ID );

			if ( ! is_null( $update_prerequisite_object ) ) {
				$lessons_to_update[] = $update_prerequisite_object;
			}

			$new_lesson_id_lookup[ $lesson->ID ] = $new_lesson->ID;
			$this->lesson_quiz_duplicator->duplicate( $lesson->ID, $new_lesson->ID );

			// Update the _order_{course_id} meta on the lesson.
			$this->update_lesson_order_on_lesson( $new_lesson->ID, $old_course_id, $new_course_id );
		}

		$this->update_lesson_prerequisite_ids( $lessons_to_update, $new_lesson_id_lookup );

		// Update the _lesson_order meta on the course.
		$this->update_lesson_order_on_course( $new_course_id, $new_lesson_id_lookup );

		return count( $lessons );
	}

	/**
	 * Update prerequisite ids after course duplication.
	 *
	 * @param array $lessons_to_update    List with lesson_id and old_prerequisite_id id to update.
	 * @param array $new_lesson_id_lookup History with the id before and after duplication.
	 */
	private function update_lesson_prerequisite_ids( array $lessons_to_update, array $new_lesson_id_lookup ): void {
		foreach ( $lessons_to_update as $lesson_to_update ) {
			$old_prerequisite_id = $lesson_to_update['old_prerequisite_id'];
			$new_prerequisite_id = $new_lesson_id_lookup[ $old_prerequisite_id ];
			add_post_meta( $lesson_to_update['lesson_id'], '_lesson_prerequisite', $new_prerequisite_id );
		}
	}

	/**
	 * Get an prerequisite update object.
	 *
	 * @param int $old_lesson_id ID of the lesson before the duplication.
	 * @param int $new_lesson_id New ID of the lesson.
	 * @return array|null Object with the id of the lesson to update and its old prerequisite id.
	 */
	private function get_prerequisite_update_object( int $old_lesson_id, int $new_lesson_id ): ?array {
		$lesson_prerequisite = get_post_meta( $old_lesson_id, '_lesson_prerequisite', true );

		if ( empty( $lesson_prerequisite ) ) {
			return null;
		}

		return array(
			'lesson_id'           => $new_lesson_id,
			'old_prerequisite_id' => $lesson_prerequisite,
		);
	}

	/**
	 * Update the _lesson_order meta on the duplicated Course so that it uses the new Lesson IDs.
	 *
	 * @param int   $course_id            The ID of the new Course.
	 * @param array $new_lesson_id_lookup An array mapping old lesson IDs to the IDs of their duplicates.
	 */
	private function update_lesson_order_on_course( int $course_id, array $new_lesson_id_lookup ): void {
		$old_lesson_order_string = get_post_meta( $course_id, '_lesson_order', true );

		if ( empty( $old_lesson_order_string ) ) {
			return;
		}

		$old_lesson_order = explode( ',', $old_lesson_order_string );
		$new_lesson_order = [];

		// Map old lesson IDs to new IDs.
		foreach ( $old_lesson_order as $old_lesson_id ) {
			if ( ! isset( $new_lesson_id_lookup[ $old_lesson_id ] ) ) {
				continue;
			}

			// Add new lesson ID to order.
			$new_lesson_id      = $new_lesson_id_lookup[ $old_lesson_id ];
			$new_lesson_order[] = $new_lesson_id;
		}

		// Persist new lesson order to course meta.
		$new_lesson_order_string = implode( ',', $new_lesson_order );
		update_post_meta( $course_id, '_lesson_order', $new_lesson_order_string );
	}

	/**
	 * Update the _order_{course_id} on a newly duplicated Lesson to use the new Course ID.
	 *
	 * @param int $new_lesson_id The new Lesson.
	 * @param int $old_course_id The ID of the old Course that was duplicated.
	 * @param int $new_course_id The ID of the new Course.
	 */
	private function update_lesson_order_on_lesson( int $new_lesson_id, int $old_course_id, int $new_course_id ): void {
		$lesson_order_value = get_post_meta( $new_lesson_id, "_order_$old_course_id", true );
		update_post_meta( $new_lesson_id, "_order_$new_course_id", $lesson_order_value );
		delete_post_meta( $new_lesson_id, "_order_$old_course_id" );
	}
}

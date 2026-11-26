<?php
/**
 * File containing the Course_Lesson class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Course_Lessons\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Course_Lesson.
 *
 * A row of the course lessons table: the place of a lesson in a course.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Course_Lesson {
	/**
	 * Course ID.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Lesson ID.
	 *
	 * @var int
	 */
	private $lesson_id;

	/**
	 * Module term ID, 0 when the lesson is at the course root.
	 *
	 * @var int
	 */
	private $module_term_id;

	/**
	 * Position in the module, or in the course root when there is no module. Null when none is stored.
	 *
	 * @var int|null
	 */
	private $lesson_order;

	/**
	 * Prerequisite lesson ID, 0 when none.
	 *
	 * @var int
	 */
	private $prerequisite_lesson_id;

	/**
	 * Course_Lesson constructor.
	 *
	 * @internal
	 *
	 * @param int      $course_id              Course ID.
	 * @param int      $lesson_id              Lesson ID.
	 * @param int      $module_term_id         Module term ID, 0 when the lesson is at the course root.
	 * @param int|null $lesson_order           Position in the module or in the course root. Null when none is stored.
	 * @param int      $prerequisite_lesson_id Prerequisite lesson ID, 0 when none.
	 */
	public function __construct( int $course_id, int $lesson_id, int $module_term_id = 0, ?int $lesson_order = null, int $prerequisite_lesson_id = 0 ) {
		$this->course_id              = $course_id;
		$this->lesson_id              = $lesson_id;
		$this->module_term_id         = $module_term_id;
		$this->lesson_order           = $lesson_order;
		$this->prerequisite_lesson_id = $prerequisite_lesson_id;
	}

	/**
	 * Get the course ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_course_id(): int {
		return $this->course_id;
	}

	/**
	 * Get the lesson ID.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_lesson_id(): int {
		return $this->lesson_id;
	}

	/**
	 * Get the module term ID, 0 when the lesson is at the course root.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_module_term_id(): int {
		return $this->module_term_id;
	}

	/**
	 * Get the position in the module, or in the course root when there is no module.
	 *
	 * @internal
	 *
	 * @return int|null Null when none is stored.
	 */
	public function get_lesson_order(): ?int {
		return $this->lesson_order;
	}

	/**
	 * Get the prerequisite lesson ID, 0 when none.
	 *
	 * @internal
	 *
	 * @return int
	 */
	public function get_prerequisite_lesson_id(): int {
		return $this->prerequisite_lesson_id;
	}
}

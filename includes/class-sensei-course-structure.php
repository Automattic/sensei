<?php
/**
 * File containing the class Sensei_Course_Structure.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains methods for retrieving and saving a Sensei course's structure.
 *
 * @since 3.6.0
 */
class Sensei_Course_Structure {
	/**
	 * Course instances.
	 *
	 * @var self[]
	 */
	private static $instances = [];

	/**
	 * The course post ID.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Get an instance of this class for a course.
	 *
	 * @param int $course_id The course post ID.
	 *
	 * @return static
	 */
	public static function instance( int $course_id ) : self {
		if ( ! isset( self::$instances[ $course_id ] ) ) {
			self::$instances[ $course_id ] = new static( $course_id );
		}

		return self::$instances[ $course_id ];
	}

	/**
	 * Sensei_Course_Structure constructor.
	 *
	 * @param int $course_id The course post ID.
	 */
	private function __construct( int $course_id ) {
		$this->course_id = $course_id;
	}

	/**
	 * Get the course structure.
	 *
	 * @return array
	 */
	public function get() : array {
		$structure = [];

		$all_lessons       = Sensei()->course->course_lessons( $this->course_id, 'any', 'ids' );
		$no_module_lessons = wp_list_pluck( Sensei()->modules->get_none_module_lessons( $this->course_id, 'any' ), 'ID' );

		if ( count( $all_lessons ) !== count( $no_module_lessons ) ) {
			$modules = $this->get_modules();
			foreach ( $modules as $module_term ) {
				$structure[] = $this->prepare_module( $module_term );
			}
		}

		foreach ( array_intersect( $all_lessons, $no_module_lessons ) as $lesson_id ) {
			$lesson = get_post( $lesson_id );
			if ( ! $lesson ) {
				continue;
			}

			$structure[] = $this->prepare_lesson( $lesson );
		}

		return $structure;
	}

	/**
	 * Prepare the result for a module.
	 *
	 * @param WP_Term $module_term Module term.
	 */
	private function prepare_module( WP_Term $module_term ) {
		$lessons = $this->get_module_lessons( $module_term->term_id );
		$module  = [
			'type'        => 'module',
			'id'          => $module_term->term_id,
			'title'       => $module_term->name,
			'description' => $module_term->description,
			'lessons'     => [],
		];

		foreach ( $lessons as $lesson ) {
			$module['lessons'][] = $this->prepare_lesson( $lesson );
		}

		return $module;
	}

	/**
	 * Prepare the result for a lesson.
	 *
	 * @param WP_Post $lesson_post Lesson post object.
	 *
	 * @return array
	 */
	private function prepare_lesson( WP_Post $lesson_post ) {
		return [
			'type'  => 'lesson',
			'id'    => $lesson_post->ID,
			'title' => $lesson_post->post_title,
		];
	}

	/**
	 * Get the lessons for a module.
	 *
	 * @param int $module_term_id Term ID for the module.
	 *
	 * @return WP_Post[]
	 */
	private function get_module_lessons( int $module_term_id ) {
		$lessons_query = Sensei()->modules->get_lessons_query( $this->course_id, $module_term_id, 'any' );

		return $lessons_query instanceof WP_Query ? $lessons_query->posts : [];
	}

	/**
	 * Get module terms in the correct order.
	 *
	 * @return WP_Term[]
	 */
	private function get_modules() : array {
		$modules = Sensei()->modules->get_course_modules( $this->course_id );

		if ( is_wp_error( $modules ) ) {
			$modules = [];
		}

		return $modules;
	}

	/**
	 * Save a new course structure.
	 *
	 * @param array $structure Course structure to save.
	 *
	 * @return bool
	 */
	public function save( array $structure ) : bool {
		// TODO: Implement.

		return false;
	}

}

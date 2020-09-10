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
	 * @param array $raw_structure Course structure to save in its raw, unsanitized form.
	 *
	 * @return bool|WP_Error
	 */
	public function save( array $raw_structure ) {
		$structure = $this->validate_and_sanitize_structure( $raw_structure );
		if ( is_wp_error( $structure ) ) {
			return $structure;
		}

		return false;
	}

	/**
	 * Parse, validate, and sanitize the structure input.
	 *
	 * @param array $raw_structure Structure array.
	 *
	 * @return WP_Error|array False if the input is invalid.
	 */
	private function validate_and_sanitize_structure( array $raw_structure ) : bool {
		$structure = [];
		foreach ( $raw_structure as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				return new WP_Error(
					'sensei_course_structure_invalid_item',
					__( 'Each item must be an array', 'sensei-lms' )
				);
			}

			$item = $this->validate_and_sanitize_item( $raw_item );
			if ( is_wp_error( $item ) ) {
				return $item;
			}

			$structure[] = $item;
		}

		return $structure;
	}

	/**
	 * Validate and sanitize input item of structure.
	 *
	 * @param array $raw_item Raw item to sanitize.
	 *
	 * @return array|WP_Error
	 */
	private function validate_and_sanitize_item( array $raw_item ) {
		if ( ! isset( $raw_item['type'] ) || ! in_array( $raw_item['type'], [ 'module', 'lesson' ], true ) ) {
			return new WP_Error(
				'sensei_course_structure_invalid_item_type',
				__( 'All items must have a `type` set.', 'sensei-lms' )
			);
		}

		if ( ! isset( $raw_item['title'] ) || '' === trim( sanitize_text_field( $raw_item['title'] ) ) ) {
			return new WP_Error(
				'sensei_course_structure_missing_title',
				__( 'All items must have a `title` set.', 'sensei-lms' )
			);
		}

		if (
			'module' === $raw_item['type']
			&& (
				! isset( $raw_item['lessons'] )
				|| ! is_array( $raw_item['lessons'] )
			)
		) {
			return new WP_Error(
				'sensei_course_structure_missing_lessons',
				__( 'Module items must include a `lessons` array.', 'sensei-lms' )
			);
		}

		$item = [
			'type'  => $raw_item['type'],
			'id'    => ! empty( $raw_item['id'] ) ? intval( $raw_item['id'] ) : null,
			'title' => trim( sanitize_text_field( $raw_item['title'] ) ),
		];

		if ( 'module' === $raw_item['type'] ) {
			$item['description'] = isset( $raw_item['description'] ) ? trim( sanitize_text_field( $raw_item['description'] ) ) : null;
			$item['lessons']     = [];
			foreach ( $raw_item['lessons'] as $raw_lesson ) {
				$lesson = $this->validate_and_sanitize_item( $raw_lesson );
				if ( is_wp_error( $lesson ) ) {
					return $lesson;
				}

				if ( 'lesson' !== $lesson['type'] ) {
					return new WP_Error(
						'sensei_course_structure_invalid_module_lesson',
						__( 'Module lessons array can only contain lessons.', 'sensei-lms' )
					);
				}

				$item['lessons'][] = $lesson;
			}
		}

		return $item;
	}
}

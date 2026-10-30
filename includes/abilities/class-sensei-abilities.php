<?php
/**
 * Sensei Abilities registration.
 *
 * @package sensei-lms
 * @since 4.26.0
 */

use Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Sensei abilities with the WordPress Abilities API.
 *
 * Requires WordPress 6.9+ (Abilities API). On earlier versions the
 * init is a no-op — the class loads but registers nothing.
 */
class Sensei_Abilities {

	const CATEGORY_SLUG = 'sensei';

	/**
	 * Initialize the abilities registration.
	 *
	 * No-ops on WordPress versions without the Abilities API (<6.9).
	 */
	public static function init(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register the Sensei ability category.
	 *
	 * @access private
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY_SLUG,
			array(
				'label'       => __( 'Sensei', 'sensei-lms' ),
				'description' => __( 'Abilities for interacting with Sensei LMS.', 'sensei-lms' ),
			)
		);
	}

	/**
	 * Register all Sensei abilities.
	 *
	 * @access private
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_courses_list_ability();
		self::register_lessons_list_ability();
		self::register_questions_list_ability();
		self::register_students_list_ability();
		self::register_course_create_ability();
		self::register_course_structure_get_ability();
		self::register_course_structure_set_ability();
		self::register_quiz_questions_set_ability();
	}

	/**
	 * Register the sensei/courses-list ability.
	 */
	private static function register_courses_list_ability(): void {
		$course_output_item_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'           => array( 'type' => 'integer' ),
				'title'        => array( 'type' => 'string' ),
				'status'       => array( 'type' => 'string' ),
				'link'         => array( 'type' => 'string' ),
				'teacher'      => array(
					'type'       => 'object',
					'properties' => array(
						'id'           => array( 'type' => 'integer' ),
						'display_name' => array( 'type' => 'string' ),
					),
				),
				'categories'   => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array( 'type' => 'integer' ),
							'name' => array( 'type' => 'string' ),
						),
					),
				),
				'modified_gmt' => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
			),
		);

		wp_register_ability(
			'sensei/courses-list',
			array(
				'label'               => __( 'List courses', 'sensei-lms' ),
				'description'         => __( 'List Sensei courses. Teachers see only their own.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(
						'status'   => array(
							'type'        => 'string',
							'description' => __( 'Filter by post status.', 'sensei-lms' ),
							'enum'        => array( 'publish', 'draft', 'pending', 'private', 'any' ),
							'default'     => 'any',
						),
						'search'   => array(
							'type'        => 'string',
							'description' => __( 'Search course titles and content.', 'sensei-lms' ),
						),
						'page'     => array(
							'type'        => 'integer',
							'description' => __( 'Page number for paginated results.', 'sensei-lms' ),
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'type'        => 'integer',
							'description' => __( 'Number of courses to return per page (max 100).', 'sensei-lms' ),
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items'       => array(
							'type'  => 'array',
							'items' => $course_output_item_schema,
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_courses_list' ),
				'permission_callback' => array( __CLASS__, 'can_edit_courses' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the sensei/lessons-list ability.
	 */
	private static function register_lessons_list_ability(): void {
		$lesson_output_item_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'           => array( 'type' => 'integer' ),
				'title'        => array( 'type' => 'string' ),
				'status'       => array( 'type' => 'string' ),
				'link'         => array( 'type' => 'string' ),
				'courses'      => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array( 'type' => 'integer' ),
							'title' => array( 'type' => 'string' ),
						),
					),
				),
				'modules'      => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array( 'type' => 'integer' ),
							'name' => array( 'type' => 'string' ),
						),
					),
				),
				'modified_gmt' => array(
					'type'   => 'string',
					'format' => 'date-time',
				),
			),
		);

		wp_register_ability(
			'sensei/lessons-list',
			array(
				'label'               => __( 'List lessons', 'sensei-lms' ),
				'description'         => __( 'List Sensei lessons. Teachers see only their own.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'default'              => array(),
					'properties'           => array(
						'course'   => array(
							'type'        => 'integer',
							'description' => __( 'Return only lessons in this course ID.', 'sensei-lms' ),
						),
						'status'   => array(
							'type'        => 'string',
							'description' => __( 'Filter by post status.', 'sensei-lms' ),
							'enum'        => array( 'publish', 'draft', 'pending', 'private', 'any' ),
							'default'     => 'any',
						),
						'search'   => array(
							'type'        => 'string',
							'description' => __( 'Search lesson titles and content.', 'sensei-lms' ),
						),
						'page'     => array(
							'type'        => 'integer',
							'description' => __( 'Page number for paginated results.', 'sensei-lms' ),
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'type'        => 'integer',
							'description' => __( 'Number of lessons to return per page (max 100).', 'sensei-lms' ),
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'items'       => array(
							'type'  => 'array',
							'items' => $lesson_output_item_schema,
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_lessons_list' ),
				'permission_callback' => array( __CLASS__, 'can_edit_lessons' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the sensei/questions-list ability.
	 */
	private static function register_questions_list_ability(): void {
		$question_output_item_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'    => array( 'type' => 'integer' ),
				'title' => array( 'type' => 'string' ),
				'type'  => array(
					'type'        => 'string',
					'description' => __( 'Question type slug from the question-type taxonomy (e.g. multiple-choice, boolean, gap-fill). Extensions may register additional slugs. Pool placeholders use the synthetic slug category-question.', 'sensei-lms' ),
				),
				'grade' => array(
					'type'        => 'integer',
					'description' => __( 'Points awarded for a correct answer. Absent on category-question placeholders.', 'sensei-lms' ),
				),
			),
		);

		wp_register_ability(
			'sensei/questions-list',
			array(
				'label'               => __( 'List questions', 'sensei-lms' ),
				'description'         => __( 'List the questions on a Sensei lesson\'s quiz.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'lesson' ),
					'properties'           => array(
						'lesson'   => array(
							'type'        => 'integer',
							'description' => __( 'The lesson ID whose quiz questions to fetch.', 'sensei-lms' ),
						),
						'page'     => array(
							'type'        => 'integer',
							'description' => __( 'Page number for paginated results.', 'sensei-lms' ),
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'type'        => 'integer',
							'description' => __( 'Number of questions to return per page (max 100).', 'sensei-lms' ),
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'lesson'      => array(
							'type'       => 'object',
							'properties' => array(
								'id'    => array( 'type' => 'integer' ),
								'title' => array( 'type' => 'string' ),
							),
						),
						'items'       => array(
							'type'  => 'array',
							'items' => $question_output_item_schema,
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_questions_list' ),
				'permission_callback' => array( __CLASS__, 'can_edit_quiz_lesson' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the sensei/students-list ability.
	 */
	private static function register_students_list_ability(): void {
		wp_register_ability(
			'sensei/students-list',
			array(
				'label'               => __( 'List students', 'sensei-lms' ),
				'description'         => __( 'List students enrolled in a course. Optionally filter by progress state or search.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'course' ),
					'properties'           => array(
						'course'          => array(
							'type'        => 'integer',
							'description' => __( 'The course ID to list enrolled students for.', 'sensei-lms' ),
						),
						'progress_status' => array(
							'type'        => 'string',
							'description' => __( 'Filter by progress state on the course.', 'sensei-lms' ),
							'enum'        => array( Course_Progress_Interface::STATUS_IN_PROGRESS, Course_Progress_Interface::STATUS_COMPLETE ),
						),
						'search'          => array(
							'type'        => 'string',
							'description' => __( 'Search by display name, login, or email.', 'sensei-lms' ),
						),
						'page'            => array(
							'type'        => 'integer',
							'description' => __( 'Page number for paginated results.', 'sensei-lms' ),
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page'        => array(
							'type'        => 'integer',
							'description' => __( 'Number of students to return per page (max 100).', 'sensei-lms' ),
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'course'      => array(
							'type'       => 'object',
							'properties' => array(
								'id'    => array( 'type' => 'integer' ),
								'title' => array( 'type' => 'string' ),
							),
						),
						'items'       => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'              => array( 'type' => 'integer' ),
									'display_name'    => array( 'type' => 'string' ),
									'user_email'      => array( 'type' => 'string' ),
									'progress_status' => array(
										'type'        => 'string',
										'description' => __( 'Progress state on the course. Absent when the student has no progress record (not started).', 'sensei-lms' ),
										'enum'        => array( Course_Progress_Interface::STATUS_IN_PROGRESS, Course_Progress_Interface::STATUS_COMPLETE ),
									),
								),
							),
						),
						'total'       => array( 'type' => 'integer' ),
						'total_pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_students_list' ),
				'permission_callback' => array( __CLASS__, 'can_manage_grades' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the sensei/course-create ability.
	 *
	 * @since $$next-version$$
	 */
	private static function register_course_create_ability(): void {
		wp_register_ability(
			'sensei/course-create',
			array(
				'label'               => __( 'Create course', 'sensei-lms' ),
				'description'         => __( 'Create a draft Sensei course with the default course layout: description, Take Course button, progress bar and course outline. The course has no modules or lessons yet. Publishing is left to a person.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'title' ),
					'properties'           => array(
						'title'       => array(
							'type'        => 'string',
							'description' => __( 'Course title.', 'sensei-lms' ),
							'minLength'   => 1,
						),
						'description' => array(
							'type'        => 'string',
							'description' => __( 'Short plain-text description. Stored as the course excerpt and shown in the description paragraph of the course page.', 'sensei-lms' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'        => array( 'type' => 'integer' ),
						'title'     => array( 'type' => 'string' ),
						'status'    => array( 'type' => 'string' ),
						'link'      => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_course_create' ),
				'permission_callback' => array( __CLASS__, 'can_create_courses' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the `sensei/course-structure-get` ability.
	 *
	 * @since $$next-version$$
	 */
	private static function register_course_structure_get_ability(): void {
		wp_register_ability(
			'sensei/course-structure-get',
			array(
				'label'               => __( 'Get course structure', 'sensei-lms' ),
				'description'         => __( 'Get the modules and lessons of a Sensei course in order, including drafts. Pass the returned ids to sensei/course-structure-set to update items in place instead of creating duplicates.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'course' ),
					'properties'           => array(
						'course' => array(
							'type'        => 'integer',
							'description' => __( 'Course ID.', 'sensei-lms' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => self::get_course_structure_output_schema(),
				'execute_callback'    => array( __CLASS__, 'execute_course_structure_get' ),
				'permission_callback' => array( __CLASS__, 'can_get_course_structure' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the `sensei/course-structure-set` ability.
	 *
	 * @since $$next-version$$
	 */
	private static function register_course_structure_set_ability(): void {
		$lesson_item_schema = array(
			'type'                 => 'object',
			'required'             => array( 'type', 'title' ),
			'properties'           => array(
				'type'  => array(
					'type' => 'string',
					'enum' => array( 'lesson' ),
				),
				'id'    => array(
					'type'        => 'integer',
					'description' => __( 'ID of an existing lesson to keep or move into this course. Omit to create a new draft lesson.', 'sensei-lms' ),
				),
				'title' => array(
					'type'        => 'string',
					'minLength'   => 1,
					'description' => __( 'Lesson title.', 'sensei-lms' ),
				),
			),
			'additionalProperties' => false,
		);

		$ignored_description = __( 'Returned by sensei/course-structure-get and ignored here, so a returned structure can be sent back as is.', 'sensei-lms' );
		foreach ( array( 'draft', 'preview' ) as $key ) {
			$lesson_item_schema['properties'][ $key ] = array(
				'type'        => 'boolean',
				'readonly'    => true,
				'description' => $ignored_description,
			);
		}
		$lesson_item_schema['properties']['initialContent'] = array(
			'type'        => 'string',
			'readonly'    => true,
			'description' => $ignored_description,
		);

		$module_item_schema = array(
			'type'                 => 'object',
			'required'             => array( 'type', 'title', 'lessons' ),
			'properties'           => array(
				'type'        => array(
					'type' => 'string',
					'enum' => array( 'module' ),
				),
				'id'          => array(
					'type'        => 'integer',
					'description' => __( 'ID of an existing module. Omit to create one, or reuse an existing module of the same name by the same teacher.', 'sensei-lms' ),
				),
				'title'       => array(
					'type'        => 'string',
					'minLength'   => 1,
					'description' => __( 'Module title.', 'sensei-lms' ),
				),
				'description' => array(
					'type'        => 'string',
					'description' => __( 'Module description. Overwrites the description of a reused module.', 'sensei-lms' ),
				),
				'lessons'     => array(
					'type'        => 'array',
					'description' => __( 'Lessons in the module, in order.', 'sensei-lms' ),
					'items'       => $lesson_item_schema,
				),
			),
			'additionalProperties' => false,
		);
		foreach ( array( 'teacher', 'lastTitle', 'slug' ) as $key ) {
			$module_item_schema['properties'][ $key ] = array(
				'type'        => 'string',
				'readonly'    => true,
				'description' => $ignored_description,
			);
		}
		$module_item_schema['properties']['teacherId'] = array(
			'type'        => 'integer',
			'readonly'    => true,
			'description' => $ignored_description,
		);

		wp_register_ability(
			'sensei/course-structure-set',
			array(
				'label'               => __( 'Set course structure', 'sensei-lms' ),
				'description'         => __( 'Replace the modules and lessons of a Sensei course, in order: modules first, then lessons outside any module, which Sensei shows after the modules. Lessons without an id are created as drafts, each with an empty quiz. Modules without an id reuse an existing module of the same name by the same teacher. Lessons and modules left out are detached from the course, not deleted. Pass the ids this ability returns to update items in place; repeating a call with id-less items creates duplicates. The structure sensei/course-structure-get returns can be sent back as is.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'course', 'structure' ),
					'properties'           => array(
						'course'    => array(
							'type'        => 'integer',
							'description' => __( 'Course ID.', 'sensei-lms' ),
						),
						'structure' => array(
							'type'        => 'array',
							'description' => __( 'Modules first, then lessons outside any module, each list in order.', 'sensei-lms' ),
							'items'       => array(
								'oneOf' => array( $module_item_schema, $lesson_item_schema ),
							),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => self::get_course_structure_output_schema(),
				'execute_callback'    => array( __CLASS__, 'execute_course_structure_set' ),
				'permission_callback' => array( __CLASS__, 'can_set_course_structure' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}

	/**
	 * Register the `sensei/quiz-questions-set` ability.
	 *
	 * @since $$next-version$$
	 */
	private static function register_quiz_questions_set_ability(): void {
		$saver = new Sensei_Lesson_Quiz_Saver();

		$options_schema = $saver->get_options_schema();
		unset( $options_schema['properties']['pagination']['required'] );
		$options_schema['description'] = __( 'Quiz settings. Settings left out keep their current value, or the default for a new quiz.', 'sensei-lms' );

		wp_register_ability(
			'sensei/quiz-questions-set',
			array(
				'label'               => __( 'Set quiz questions', 'sensei-lms' ),
				'description'         => __( 'Replace the settings and questions of a Sensei lesson\'s quiz, creating the quiz if the lesson has none. Questions without an id are created; questions left out are removed from the quiz but kept in the question bank, except category-question placeholders, which are deleted. Settings you omit keep their current values. Pass the ids this ability returns to update questions in place.', 'sensei-lms' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'lesson', 'questions' ),
					'properties'           => array(
						'lesson'    => array(
							'type'        => 'integer',
							'description' => __( 'Lesson ID.', 'sensei-lms' ),
						),
						'options'   => $options_schema,
						'questions' => array(
							'type'        => 'array',
							'description' => __( 'Questions in order. Each has a type (multiple-choice, boolean, gap-fill, single-line, multi-line, file-upload or category-question), a title, an optional description and type-specific answer and options.', 'sensei-lms' ),
							'items'       => $saver->get_single_question_schema(),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'lesson'    => array(
							'type'       => 'object',
							'properties' => array(
								'id'     => array( 'type' => 'integer' ),
								'title'  => array( 'type' => 'string' ),
								'status' => array( 'type' => 'string' ),
							),
						),
						'quiz_id'   => array( 'type' => 'integer' ),
						'options'   => array( 'type' => 'object' ),
						'questions' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
				),
				'execute_callback'    => array( __CLASS__, 'execute_quiz_questions_set' ),
				'permission_callback' => array( __CLASS__, 'can_set_quiz_questions' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
					),
				),
			)
		);
	}
	/**
	 * Output schema shared by the course structure abilities.
	 *
	 * Extra keys are allowed: the structure carries editor bookkeeping too.
	 *
	 * @since $$next-version$$
	 *
	 * @return array
	 */
	private static function get_course_structure_output_schema(): array {
		$lesson_schema = array(
			'type'       => 'object',
			'properties' => array(
				'type'  => array( 'type' => 'string' ),
				'id'    => array( 'type' => 'integer' ),
				'title' => array( 'type' => 'string' ),
				'draft' => array( 'type' => 'boolean' ),
			),
		);

		return array(
			'type'       => 'object',
			'properties' => array(
				'course'    => array(
					'type'       => 'object',
					'properties' => array(
						'id'     => array( 'type' => 'integer' ),
						'title'  => array( 'type' => 'string' ),
						'status' => array( 'type' => 'string' ),
					),
				),
				'structure' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'type'        => array( 'type' => 'string' ),
							'id'          => array( 'type' => 'integer' ),
							'title'       => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'draft'       => array( 'type' => 'boolean' ),
							'lessons'     => array(
								'type'  => 'array',
								'items' => $lesson_schema,
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Determine a student's progress state on a course.
	 *
	 * Returns null when the student has no progress record for the course
	 * (matches Sensei's data model — there is no "not started" status).
	 *
	 * @param int $user_id   Student user ID.
	 * @param int $course_id Course post ID.
	 */
	private static function resolve_progress_status( int $user_id, int $course_id ): ?string {
		$progress = Sensei()->course_progress_repository->get( $course_id, $user_id );

		return $progress ? $progress->get_status() : null;
	}

	/**
	 * Execute sensei/courses-list.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public static function execute_courses_list( $input = array() ): array {
		$args = array(
			'post_type'      => 'course',
			'post_status'    => $input['status'] ?? 'any',
			'posts_per_page' => min( 100, max( 1, (int) ( $input['per_page'] ?? 20 ) ) ),
			'paged'          => max( 1, (int) ( $input['page'] ?? 1 ) ),
		);

		if ( ! current_user_can( 'edit_others_courses' ) ) {
			$args['author__in'] = array( get_current_user_id() );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = $input['search'];
		}

		$query = new WP_Query( $args );

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$teacher = get_userdata( (int) $post->post_author );

			$terms      = get_the_terms( $post->ID, 'course-category' );
			$categories = array();
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = array(
						'id'   => (int) $term->term_id,
						'name' => $term->name,
					);
				}
			}

			$items[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'status'       => $post->post_status,
				'link'         => (string) get_permalink( $post->ID ),
				'teacher'      => array(
					'id'           => (int) $post->post_author,
					'display_name' => $teacher ? $teacher->display_name : '',
				),
				'categories'   => $categories,
				'modified_gmt' => mysql_to_rfc3339( $post->post_modified_gmt ),
			);
		}

		return array(
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Execute sensei/lessons-list.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	public static function execute_lessons_list( $input = array() ): array {
		$args = array(
			'post_type'      => 'lesson',
			'post_status'    => $input['status'] ?? 'any',
			'posts_per_page' => min( 100, max( 1, (int) ( $input['per_page'] ?? 20 ) ) ),
			'paged'          => max( 1, (int) ( $input['page'] ?? 1 ) ),
		);

		if ( ! current_user_can( 'edit_others_lessons' ) ) {
			$args['author__in'] = array( get_current_user_id() );
		}

		if ( ! empty( $input['course'] ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_lesson_course',
					'value' => (int) $input['course'],
				),
			);
		}

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = $input['search'];
		}

		$query = new WP_Query( $args );

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}

			$item = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'status'       => $post->post_status,
				'link'         => (string) get_permalink( $post->ID ),
				'modified_gmt' => mysql_to_rfc3339( $post->post_modified_gmt ),
			);

			// Courses and modules are wrapped in arrays even though a lesson maps to
			// only one of each in practice — keeps the shape stable if Sensei later
			// allows multi-assignment without a schema break.
			$item['courses'] = array();
			$course_id       = (int) Sensei()->lesson->get_course_id( $post->ID );
			if ( $course_id ) {
				$course = get_post( $course_id );
				if ( $course instanceof WP_Post ) {
					$item['courses'][] = array(
						'id'    => $course_id,
						'title' => $course->post_title,
					);
				}
			}

			$item['modules'] = array();
			$module_terms    = wp_get_post_terms( $post->ID, 'module' );
			if ( is_array( $module_terms ) ) {
				foreach ( $module_terms as $term ) {
					$item['modules'][] = array(
						'id'   => (int) $term->term_id,
						'name' => $term->name,
					);
				}
			}

			$items[] = $item;
		}

		return array(
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Execute sensei/questions-list.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_questions_list( $input ) {
		$lesson_id = (int) $input['lesson'];
		$lesson    = get_post( $lesson_id );

		if ( ! $lesson instanceof WP_Post || 'lesson' !== $lesson->post_type ) {
			return new WP_Error(
				'sensei_lesson_not_found',
				__( 'No lesson exists with the given ID.', 'sensei-lms' )
			);
		}

		$quiz_id = (int) Sensei()->lesson->lesson_quizzes( $lesson_id );
		if ( ! $quiz_id ) {
			return new WP_Error(
				'sensei_quiz_not_found',
				__( 'This lesson does not have a quiz.', 'sensei-lms' )
			);
		}

		$per_page = min( 100, max( 1, (int) ( $input['per_page'] ?? 20 ) ) );
		$page     = max( 1, (int) ( $input['page'] ?? 1 ) );

		$questions   = Sensei()->quiz->get_questions( $quiz_id );
		$total       = count( $questions );
		$total_pages = (int) ceil( $total / $per_page );
		$page_slice  = array_slice( $questions, ( $page - 1 ) * $per_page, $per_page );

		$items = array();
		foreach ( $page_slice as $question ) {
			if ( ! $question instanceof WP_Post ) {
				continue;
			}

			$items[] = self::prepare_question_item( $question );
		}

		return array(
			'lesson'      => array(
				'id'    => $lesson_id,
				'title' => $lesson->post_title,
			),
			'items'       => $items,
			'total'       => $total,
			'total_pages' => $total_pages,
		);
	}

	/**
	 * Shape a single question post for the questions-list response.
	 *
	 * Category questions ("pick N from category X") don't have a title, so emit
	 * them with a distinct type so agents can tell they're placeholders rather
	 * than assume questions went missing.
	 *
	 * @param WP_Post $question Question or multiple_question post.
	 */
	private static function prepare_question_item( WP_Post $question ): array {
		if ( 'multiple_question' === $question->post_type ) {
			$category_id = (int) get_post_meta( $question->ID, 'category', true );
			$number      = (int) get_post_meta( $question->ID, 'number', true );
			$category    = $category_id ? get_term( $category_id, 'question-category' ) : null;
			// Fall back to a visible marker when the referenced category was
			// deleted — otherwise the synthesized title reads as a broken
			// sentence ("3 questions from ").
			$cat_name = $category instanceof WP_Term
				? $category->name
				: __( '(deleted category)', 'sensei-lms' );

			return array(
				'id'    => (int) $question->ID,
				'title' => sprintf(
					/* translators: 1: number of questions, 2: category name. */
					_n( '%1$d question from %2$s', '%1$d questions from %2$s', $number, 'sensei-lms' ),
					$number,
					$cat_name
				),
				'type'  => 'category-question',
			);
		}

		return array(
			'id'    => (int) $question->ID,
			'title' => $question->post_title,
			'type'  => Sensei()->question->get_question_type( $question->ID ),
			'grade' => (int) Sensei()->question->get_question_grade( $question->ID ),
		);
	}

	/**
	 * Execute sensei/students-list.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_students_list( $input ) {
		$course_id   = (int) $input['course'];
		$course_post = get_post( $course_id );
		if ( ! $course_post instanceof WP_Post || 'course' !== $course_post->post_type ) {
			return new WP_Error(
				'sensei_course_not_found',
				__( 'No course exists with the given ID.', 'sensei-lms' )
			);
		}

		$per_page    = min( 100, max( 1, (int) ( $input['per_page'] ?? 20 ) ) );
		$page        = max( 1, (int) ( $input['page'] ?? 1 ) );
		$course_echo = array(
			'id'    => $course_id,
			'title' => $course_post->post_title,
		);

		$enrolment    = Sensei_Course_Enrolment::get_course_instance( $course_id );
		$enrolled_ids = $enrolment->get_enrolled_user_ids();

		// Progress status isn't joinable in WP_User_Query, so narrow the enrolled
		// set before paginating — otherwise `total` would drift from `items`.
		// O(enrolled) per request; acceptable because typical courses have tens
		// to hundreds of students, not thousands.
		if ( ! empty( $input['progress_status'] ) ) {
			$target_status = $input['progress_status'];
			$enrolled_ids  = array_values(
				array_filter(
					$enrolled_ids,
					static fn( $user_id ) => self::resolve_progress_status( (int) $user_id, $course_id ) === $target_status
				)
			);
		}

		// WP_User_Query treats an empty `include` as "no restriction" and returns every user,
		// so short-circuit here when no one matches.
		if ( empty( $enrolled_ids ) ) {
			return array(
				'course'      => $course_echo,
				'items'       => array(),
				'total'       => 0,
				'total_pages' => 0,
			);
		}

		$query_args = array(
			'number'  => $per_page,
			'paged'   => $page,
			'fields'  => 'ID',
			'include' => $enrolled_ids,
		);

		if ( ! empty( $input['search'] ) ) {
			$query_args['search']         = '*' . $input['search'] . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$user_query = new WP_User_Query( $query_args );
		$user_ids   = $user_query->get_results();

		$items = array();
		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$item = array(
				'id'           => (int) $user_id,
				'display_name' => $user->display_name,
				'user_email'   => $user->user_email,
			);

			$status = self::resolve_progress_status( (int) $user_id, $course_id );
			if ( null !== $status ) {
				$item['progress_status'] = $status;
			}

			$items[] = $item;
		}

		return array(
			'course'      => $course_echo,
			'items'       => $items,
			'total'       => (int) $user_query->get_total(),
			'total_pages' => (int) ceil( $user_query->get_total() / $per_page ),
		);
	}

	/**
	 * Execute the sensei/course-create ability.
	 *
	 * The course is always a draft: the ability never publishes, so a teacher
	 * cannot reach through it what the editor would not let them publish.
	 *
	 * @access private
	 * @since $$next-version$$
	 *
	 * @param array $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function execute_course_create( $input ) {
		$title = trim( sanitize_text_field( $input['title'] ?? '' ) );
		if ( '' === $title ) {
			return new WP_Error( 'sensei_course_missing_title', __( 'A course title is required.', 'sensei-lms' ) );
		}

		$description = trim( sanitize_textarea_field( $input['description'] ?? '' ) );

		// wp_insert_post() expects slashed data; ability input is not slashed.
		$course_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'course',
					'post_status'  => 'draft',
					'post_title'   => $title,
					'post_excerpt' => $description,
					'post_content' => self::get_default_course_content( $description ),
					'post_author'  => get_current_user_id(),
				)
			),
			true
		);

		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		$course = get_post( $course_id );

		return array(
			'id'        => $course->ID,
			'title'     => $course->post_title,
			'status'    => $course->post_status,
			'link'      => (string) get_permalink( $course ),
			'edit_link' => (string) get_edit_post_link( $course, 'raw' ),
		);
	}

	/**
	 * Get the content the editor gives a new course: the default course pattern
	 * with the description placeholder filled in.
	 *
	 * Honours a Sensei pattern set through `sensei_course_block_template`, and
	 * falls back to `course-default` when the template points elsewhere.
	 *
	 * @param string $description Sanitized plain-text description, may be empty.
	 *
	 * @return string Block markup.
	 */
	private static function get_default_course_content( string $description ): string {
		$pattern_name  = 'course-default';
		$post_type     = get_post_type_object( 'course' );
		$template_slug = $post_type->template[0][1]['slug'] ?? '';
		if ( is_string( $template_slug ) && 0 === strpos( $template_slug, 'sensei-lms/' ) ) {
			$pattern_name = substr( $template_slug, strlen( 'sensei-lms/' ) );
		}

		$content = Sensei_Block_Patterns::get_pattern_content( 'course', $pattern_name )
			?? Sensei_Block_Patterns::get_pattern_content( 'course', 'course-default' )
			?? '';

		if ( '' === $description ) {
			return $content;
		}

		return Sensei_Block_Patterns::replace_placeholders(
			$content,
			array( 'sensei-content-description' => esc_html( $description ) )
		);
	}

	/**
	 * Execute the course structure get ability.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_course_structure_get( $input ) {
		$course = self::get_course_post( (int) ( $input['course'] ?? 0 ) );
		if ( ! $course ) {
			return new WP_Error( 'sensei_course_not_found', __( 'No course exists with the given ID.', 'sensei-lms' ) );
		}

		return self::prepare_course_structure( $course, false );
	}

	/**
	 * Execute the course structure set ability.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_course_structure_set( $input ) {
		$course = self::get_course_post( (int) ( $input['course'] ?? 0 ) );
		if ( ! $course ) {
			return new WP_Error( 'sensei_course_not_found', __( 'No course exists with the given ID.', 'sensei-lms' ) );
		}

		$structure = self::strip_structure_bookkeeping( is_array( $input['structure'] ?? null ) ? $input['structure'] : array() );

		$order_error = self::find_structure_order_error( $structure );
		if ( $order_error ) {
			return $order_error;
		}

		$result = Sensei_Course_Structure::instance( $course->ID )->save( $structure );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( false === $result ) {
			return new WP_Error(
				'sensei_course_structure_unknown_error',
				__( 'An error occurred while saving the course structure. Earlier items may have been saved: read the structure back with sensei/course-structure-get before retrying.', 'sensei-lms' )
			);
		}

		return self::prepare_course_structure( $course, (bool) wp_using_ext_object_cache() );
	}

	/**
	 * Drop the keys a returned structure carries that are not input.
	 *
	 * `sensei/course-structure-get` returns the editor's bookkeeping (`lastTitle`, `slug`,
	 * `teacherId`, `initialContent`, …) so the output matches the REST route. The input
	 * schema accepts those keys so a returned structure can be sent back, but only the
	 * documented keys reach Sensei_Course_Structure::save().
	 *
	 * @param array $structure Structure as given to the ability.
	 *
	 * @return array Structure with only `type`, `id`, `title`, `description` and `lessons`.
	 */
	private static function strip_structure_bookkeeping( array $structure ): array {
		$stripped = array();

		foreach ( $structure as $item ) {
			if ( ! is_array( $item ) ) {
				$stripped[] = $item;
				continue;
			}

			$keys = 'module' === ( $item['type'] ?? null )
				? array( 'type', 'id', 'title', 'description', 'lessons' )
				: array( 'type', 'id', 'title' );
			$kept = array_intersect_key( $item, array_flip( $keys ) );

			if ( isset( $kept['lessons'] ) && is_array( $kept['lessons'] ) ) {
				$kept['lessons'] = self::strip_structure_bookkeeping( $kept['lessons'] );
			}

			$stripped[] = $kept;
		}

		return $stripped;
	}

	/**
	 * Check that no module follows a lesson outside a module.
	 *
	 * Sensei stores module order and top-level lesson order separately and always shows
	 * the modules first, so a mixed order could not be kept. Rejecting it is better than
	 * silently reordering what the caller asked for.
	 *
	 * @param array $structure Structure as given to the ability.
	 *
	 * @return WP_Error|null Error naming the misplaced module, or null when the order can be kept.
	 */
	private static function find_structure_order_error( array $structure ): ?WP_Error {
		$lesson_seen = false;

		foreach ( $structure as $item ) {
			$type = is_array( $item ) ? ( $item['type'] ?? null ) : null;

			if ( 'lesson' === $type ) {
				$lesson_seen = true;
			} elseif ( 'module' === $type && $lesson_seen ) {
				return new WP_Error(
					'sensei_course_structure_module_after_lesson',
					sprintf(
						/* translators: %s: module title. */
						__( 'Modules must come before lessons outside modules, because Sensei shows all modules first. Move the module "%s" before the top-level lessons.', 'sensei-lms' ),
						(string) ( $item['title'] ?? '' )
					)
				);
			}
		}

		return null;
	}

	/**
	 * Get a course post by ID.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return WP_Post|null The course, or null when the ID is not a course.
	 */
	private static function get_course_post( int $course_id ): ?WP_Post {
		$course = get_post( $course_id );

		if ( ! $course instanceof WP_Post || 'course' !== $course->post_type ) {
			return null;
		}

		return $course;
	}

	/**
	 * Build the course structure ability output.
	 *
	 * @param WP_Post $course   Course post.
	 * @param bool    $no_cache Whether to bypass the query cache, as the editor does right after a save.
	 *
	 * @return array
	 */
	private static function prepare_course_structure( WP_Post $course, bool $no_cache ): array {
		return array(
			'course'    => array(
				'id'     => $course->ID,
				'title'  => $course->post_title,
				'status' => $course->post_status,
			),
			'structure' => Sensei_Course_Structure::instance( $course->ID )->get( 'edit', $no_cache ),
		);
	}

	/**
	 * Execute the quiz questions set ability.
	 *
	 * @since $$next-version$$
	 * @access private
	 *
	 * @param array $input Ability input.
	 * @return array|WP_Error
	 */
	public static function execute_quiz_questions_set( $input ) {
		$lesson = get_post( (int) ( $input['lesson'] ?? 0 ) );
		if ( ! $lesson instanceof WP_Post || 'lesson' !== $lesson->post_type ) {
			return new WP_Error( 'sensei_lesson_not_found', __( 'No lesson exists with the given ID.', 'sensei-lms' ) );
		}

		$saver = new Sensei_Lesson_Quiz_Saver();

		// The Abilities API validates input against the schema but does not sanitize it.
		$questions = is_array( $input['questions'] ?? null ) ? $input['questions'] : array();
		$valid     = $saver->validate_questions( $questions );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$questions = $saver->sanitize_questions( $questions );
		if ( is_wp_error( $questions ) ) {
			return $questions;
		}

		$options = is_array( $input['options'] ?? null ) ? $input['options'] : array();
		$options = rest_sanitize_value_from_schema( $options, $saver->get_options_schema() );
		if ( ! is_array( $options ) ) {
			return new WP_Error( 'sensei_quiz_invalid_options', __( 'The quiz options could not be sanitized.', 'sensei-lms' ) );
		}

		$quiz_id = Sensei()->lesson->lesson_quizzes( $lesson->ID );
		$quiz    = $quiz_id ? get_post( $quiz_id ) : null;
		$options = $saver->get_options_for_update( $quiz instanceof WP_Post ? $quiz : null, $options );

		$quiz_id = $saver->save(
			$lesson,
			array(
				'options'   => $options,
				'questions' => $questions,
			)
		);
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		$quiz_data = $saver->get_quiz_data( get_post( $quiz_id ), $lesson );

		return array(
			'lesson'    => array(
				'id'     => $lesson->ID,
				'title'  => $lesson->post_title,
				'status' => $lesson->post_status,
			),
			'quiz_id'   => $quiz_id,
			'options'   => $quiz_data['options'],
			'questions' => $quiz_data['questions'],
		);
	}

	/**
	 * Permission check: user can edit courses.
	 *
	 * Mirrors the Courses admin screen capability.
	 *
	 * @access private
	 */
	public static function can_edit_courses(): bool {
		return current_user_can( 'edit_courses' );
	}

	/**
	 * Permission check: user can edit lessons.
	 *
	 * Mirrors the Lessons admin screen capability. When a `course` input is
	 * provided the caller is additionally scoped to courses they can edit, so
	 * teachers asking about another teacher's course get a clear permission
	 * failure instead of a silently empty list.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 */
	public static function can_edit_lessons( $input = array() ): bool {
		if ( ! current_user_can( 'edit_lessons' ) ) {
			return false;
		}
		if ( empty( $input['course'] ) ) {
			return true;
		}
		$course = get_post( (int) $input['course'] );
		if ( ! $course instanceof WP_Post || 'course' !== $course->post_type ) {
			return false;
		}
		$post_type = get_post_type_object( 'course' );
		if ( ! $post_type ) {
			return false;
		}
		return current_user_can( $post_type->cap->edit_post, $course->ID )
			|| current_user_can( 'manage_options' );
	}

	/**
	 * Permission check: user can edit the specific lesson whose quiz is being requested.
	 *
	 * Uses the `edit_lesson` meta capability, which map_meta_cap resolves to an
	 * ownership check for teachers (they can only reach their own lessons) and
	 * allows admins through.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 */
	public static function can_edit_quiz_lesson( $input = array() ): bool {
		if ( empty( $input['lesson'] ) ) {
			return false;
		}
		$lesson = get_post( (int) $input['lesson'] );
		if ( ! $lesson || 'lesson' !== $lesson->post_type ) {
			return false;
		}
		$post_type = get_post_type_object( 'lesson' );
		if ( ! $post_type ) {
			return false;
		}
		return current_user_can( $post_type->cap->edit_post, $lesson->ID )
			|| current_user_can( 'manage_options' );
	}

	/**
	 * Permission check: user can read students for the given course.
	 *
	 * Mirrors the Students admin screen, which requires `manage_sensei_grades`
	 * and scopes teachers to courses they author via pre_get_posts. Here we
	 * require `manage_sensei_grades` (admins receive this cap by default) and
	 * then gate on `edit_post` of the course, with `manage_options` as the
	 * admin fallback for the per-course check.
	 *
	 * @access private
	 *
	 * @param array $input Ability input.
	 */
	public static function can_manage_grades( $input = array() ): bool {
		if ( ! current_user_can( 'manage_sensei_grades' ) ) {
			return false;
		}
		if ( empty( $input['course'] ) ) {
			return false;
		}
		$course = get_post( (int) $input['course'] );
		if ( ! $course instanceof WP_Post || 'course' !== $course->post_type ) {
			return false;
		}
		$post_type = get_post_type_object( 'course' );
		if ( ! $post_type ) {
			return false;
		}
		return current_user_can( $post_type->cap->edit_post, $course->ID )
			|| current_user_can( 'manage_options' );
	}

	/**
	 * Permission check: user can create courses.
	 *
	 * Uses the course post type's `create_posts` capability, the one the "Add New
	 * Course" screen requires. Core maps it to `edit_courses` unless a plugin
	 * overrides it. Teachers have it; they lack `publish_courses`, which is why the
	 * ability only creates drafts.
	 *
	 * @access private
	 * @since $$next-version$$
	 */
	public static function can_create_courses(): bool {
		$post_type_object = get_post_type_object( 'course' );
		if ( ! $post_type_object ) {
			return false;
		}

		return current_user_can( $post_type_object->cap->create_posts );
	}

	/**
	 * Permission check: user can read a course's structure for editing.
	 *
	 * Mirrors the structure REST endpoint with `context=edit`: the course must exist
	 * and the user must be able to edit it.
	 *
	 * @access private
	 * @since $$next-version$$
	 *
	 * @param array $input Ability input.
	 */
	public static function can_get_course_structure( $input = array() ): bool {
		return Sensei_Course_Structure::can_current_user_save( (int) ( $input['course'] ?? 0 ), array() );
	}

	/**
	 * Permission check: user can save a course's structure.
	 *
	 * Shares the check with the structure REST endpoint: the user can edit the
	 * course, every lesson id in the structure and every module id in it.
	 *
	 * @access private
	 * @since $$next-version$$
	 *
	 * @param array $input Ability input.
	 */
	public static function can_set_course_structure( $input = array() ): bool {
		$structure = is_array( $input['structure'] ?? null ) ? $input['structure'] : array();

		return Sensei_Course_Structure::can_current_user_save( (int) ( $input['course'] ?? 0 ), $structure );
	}

	/**
	 * Permission check: user can save the quiz of the given lesson.
	 *
	 * Shares the check with the lesson-quiz REST endpoint: the lesson must exist and
	 * the user must be able to edit it, which limits teachers to their own lessons.
	 *
	 * @access private
	 * @since $$next-version$$
	 *
	 * @param array $input Ability input.
	 */
	public static function can_set_quiz_questions( $input = array() ): bool {
		return Sensei_Lesson_Quiz_Saver::can_current_user_save( (int) ( $input['lesson'] ?? 0 ) );
	}
}

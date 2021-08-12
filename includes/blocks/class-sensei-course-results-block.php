<?php
/**
 * File containing the Sensei_Course_Results_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Results_Block
 */
class Sensei_Course_Results_Block {

	/**
	 * Rendered HTML output for the block.
	 *
	 * @var string
	 */
	private $block_content;

	/**
	 * Sensei_Course_Results_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Resets block content.
	 *
	 * @access private
	 */
	public function clear_block_content() {
		$this->block_content = null;
	}

	/**
	 * Register course results block.
	 */
	private function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-results',
			[
				'render_callback' => [ $this, 'render_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-results-block' )
		);
	}

	/**
	 * Render Course Results block.
	 *
	 * @access private
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block HTML.
	 */
	public function render_block( $attributes ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used safely if the user completed course.
		$course_id = isset( $_GET['course_id'] ) ? (int) $_GET['course_id'] : false;

		// Check that a course has been passed to the page this block is on and the user has completed that course.
		if (
			! $course_id
			|| ! get_current_user_id()
			|| 'course' !== get_post_type( $course_id )
			|| ! Sensei_Utils::user_completed_course( $course_id, get_current_user_id() )
		) {
				return '';
		}

		if ( $this->block_content ) {
			return $this->block_content;
		}

		$class_name        = Sensei_Block_Helpers::block_class_with_default_style( $attributes, [] );
		$structure         = Sensei_Course_Structure::instance( $course_id )->get( 'view' );
		$has_other_lessons = $this->course_structure_has_type( $structure, 'lesson' );
		$block_content     = [];
		$block_content[]   = '<section class="wp-block-sensei-lms-course-results sensei-block-wrapper ' . $class_name . '">';
		$block_content[]   = $this->render_total_grade( $course_id );
		$block_content[]   = $this->render_course_title( $course_id, $structure, $attributes );

		// Render modules and associated lessons.
		foreach ( $structure as $item ) {
			if ( 'module' === $item['type'] ) {
				$block_content[] = $this->render_module( $item, $attributes );
			}
		}

		// Render other lessons.
		if ( $has_other_lessons ) {
			$block_content[] = '<ul class="wp-block-sensei-lms-course-results__lessons wp-block-sensei-lms-course-results__lessons--has-other">';

			foreach ( $structure as $item ) {
				if ( 'lesson' === $item['type'] ) {
					$block_content[] = $this->render_lesson( $item );
				}
			}

			$block_content[] = '</ul>';
		}

		$block_content[] = '</section>';

		$this->block_content = implode( $block_content );

		return $this->block_content;
	}

	/**
	 * Render the total grade.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return string HTML for the total grade.
	 */
	private function render_total_grade( $course_id ) {
		// Course does not have a quiz.
		if ( ! Sensei()->course->course_quizzes( $course_id, true ) ) {
			return '';
		}

		$content   = [];
		$content[] = '<div class="wp-block-sensei-lms-course-results__grade">';
		$content[] = '<span class="wp-block-sensei-lms-course-results__grade-label">';
		$content[] = __( 'Your Total Grade', 'sensei-lms' );
		$content[] = '</span>';
		$content[] = '<span class="wp-block-sensei-lms-course-results__grade-score">';
		$content[] = Sensei_Utils::sensei_course_user_grade( $course_id, get_current_user_id() ) . '%';
		$content[] = '</span>';
		$content[] = '</div>';

		return implode( $content );
	}

	/**
	 * Render the course title.
	 *
	 * @param int   $course_id  Course ID.
	 * @param array $structure  Course structure information.
	 * @param array $attributes The block attributes.
	 *
	 * @return string HTML for the course title.
	 */
	private function render_course_title( $course_id, $structure, $attributes ) {
		$content      = [];
		$course_title = $course_id ? get_the_title( $course_id ) : '';

		if ( $course_title ) {
			$content[] = '<h2 class="wp-block-sensei-lms-course-results__course-title">';
			$content[] = $course_title;
			$content[] = '</h2>';
		}

		// Render separator for courses that don't have any modules.
		$has_modules = $this->course_structure_has_type( $structure, 'module' );

		if ( ! $has_modules ) {
			$content[] = '<div class="wp-block-sensei-lms-course-results__separator"></div>';
		}

		return implode( $content );
	}

	/**
	 * Render a module in the course results block.
	 *
	 * @param array $item       The course structure item.
	 * @param array $attributes The block attributes.
	 *
	 * @return string
	 */
	private function render_module( $item, $attributes ) {
		if ( empty( $item['lessons'] ) ) {
			return '';
		}

		$section_content   = [];
		$class_name        = Sensei_Block_Helpers::block_class_with_default_style( $attributes, [] );
		$module_header_css = [];
		$is_default_style  = false !== strpos( $class_name, 'is-style-default' );
		$is_minimal_style  = false !== strpos( $class_name, 'is-style-minimal' );

		// Only set header CSS whether it's the default style or the text color is set.
		if (
			$is_default_style
			|| ! empty( $attributes['textColor'] )
			|| ! empty( $attributes['customTextColor'] )
		) {
			$module_header_css = Sensei_Block_Helpers::build_styles(
				$attributes,
				[
					'mainColor'       => $is_default_style ? 'background-color' : null,
					'backgroundColor' => null,
					'borderColor'     => null,
				]
			);
		}

		$section_content[] = '<section ' . $this->get_module_html_attributes( $class_name, $attributes ) . '>';
		$section_content[] = '<header ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-course-results__module-header' ], $module_header_css ) . '>';
		$section_content[] = '<h3 class="wp-block-sensei-lms-course-results__module-title">';
		$section_content[] = esc_html( $item['title'] );
		$section_content[] = '</h3>';
		$section_content[] = '</header>';

		if ( $is_minimal_style ) {
			$header_border_css = Sensei_Block_Helpers::build_styles(
				$attributes,
				[
					'mainColor'   => 'background-color',
					'borderColor' => null,
				]
			);

			$section_content[] = '<div ' . Sensei_Block_Helpers::render_style_attributes( 'wp-block-sensei-lms-course-results__separator', $header_border_css ) . '></div>';

		}

		if ( 0 < count( $item['lessons'] ) ) {
			$section_content[] = '<ul class="wp-block-sensei-lms-course-results__lessons">';

			foreach ( $item['lessons'] as $lesson ) {
				$section_content[] = $this->render_lesson( $lesson );
			}

			$section_content[] = '</ul>';
		}

		$section_content[] = '</section>';

		return implode( $section_content );
	}

	/**
	 * Calculates the module section html attributes.
	 *
	 * @param string $class_name The block class name.
	 * @param array  $attributes The outline attributes.
	 *
	 * @return string The html attributes.
	 */
	private function get_module_html_attributes( $class_name, $attributes ) : string {
		$class_names   = [ 'wp-block-sensei-lms-course-results__module' ];
		$inline_styles = [];
		$css           = Sensei_Block_Helpers::build_styles(
			$attributes,
			[
				'textColor' => null,
			]
		);

		if ( ! empty( $attributes['moduleBorder'] ) ) {
			$class_names[] = 'wp-block-sensei-lms-course-results__module--has-border';

			if ( ! empty( $attributes['borderColorValue'] ) ) {
				$inline_styles[] = sprintf( 'border-color: %s;', $attributes['borderColorValue'] );
			}
		}

		return Sensei_Block_Helpers::render_style_attributes(
			$class_names,
			[
				'css_classes'   => $css['css_classes'],
				'inline_styles' => array_merge( $css['inline_styles'], $inline_styles ),
			]
		);
	}

	/**
	 * Render a lesson in the course results block.
	 *
	 * @param array $item The course structure item.
	 */
	private function render_lesson( $item ) {
		$section_content   = [];
		$section_content[] = '<li class="wp-block-sensei-lms-course-results__lesson">';
		$section_content[] = '<a href="' . esc_url( get_permalink( $item['id'] ) ) . '" class="wp-block-sensei-lms-course-results__lesson-link">';
		$section_content[] = '<span class="wp-block-sensei-lms-course-results__lesson-title">';
		$section_content[] = esc_html( $item['title'] );
		$section_content[] = '</span>';

		$grade = $this->get_lesson_grade( $item['id'] );
		if ( null !== $grade ) {
			$section_content[] = '<span class="wp-block-sensei-lms-course-results__lesson-score">';
			$section_content[] = $grade . '%';
			$section_content[] = '</span>';
		}

		$section_content[] = '</a>';
		$section_content[] = '</li>';

		return implode( $section_content );
	}

	/**
	 * Get the lesson grade.
	 *
	 * @param int $lesson_id
	 *
	 * @return string|null
	 */
	private function get_lesson_grade( $lesson_id ) {
		$activity_args   = [
			'post_id' => $lesson_id,
			'user_id' => get_current_user_id(),
			'type'    => 'sensei_lesson_status',
			'status'  => [ 'graded', 'passed', 'failed' ],
		];
		$lesson_activity = Sensei_Utils::sensei_check_for_activity( $activity_args, true );

		if ( empty( $lesson_activity ) ) {
			return null;
		}

		if ( is_array( $lesson_activity ) ) {
			$lesson_activity = $lesson_activity[0];
		}

		$grade = get_comment_meta( $lesson_activity->comment_ID, 'grade', true );

		if ( false === $grade || '' === $grade ) {
			return null;
		}

		return $grade;
	}

	/**
	 * Check whether the course structure contains an item of the specified type.
	 *
	 * @param array  $structure  Course structure information.
	 * @param string $type       Type to check (module, lesson).
	 *
	 * @return bool Whether an item of the specified type exists in the course structure.
	 */
	private function course_structure_has_type( $structure, $type ) {
		$type_counts = array_count_values( array_column( $structure, 'type' ) );

		return array_key_exists( $type, $type_counts ) && 0 < $type_counts[ $type ];
	}
}

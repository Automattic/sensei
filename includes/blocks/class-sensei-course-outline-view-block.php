<?php
/**
 * File containing the Sensei_Course_Outline_View_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Outline_View_Block
 */
class Sensei_Course_Outline_View_Block {

	/**
	 * @var array
	 */
	private $settings;


	/**
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {

		$this->register_block();

		$this->settings = [ 'collapsibleModules' => true ];
	}


	/**
	 * Register course outline block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-outline-view',
			[
				'render_callback' => [ $this, 'render_course_outline_view_block' ],
			]
		);


	}

	/**
	 * Render Course Outline block.
	 *
	 * @access private
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block HTML.
	 */
	public function render_course_outline_view_block( $attributes ) {

		$course_id = Sensei_Utils::get_current_course();

		$structure = Sensei_Course_Structure::instance( $course_id )->get();

		$outline_html = implode(
			'',
			array_map(
				function( $item ) use ( $course_id ) {
					switch ( $item['type'] ) {
						case 'module':
							return $this->render_module( $item, $course_id );
						case 'lesson':
							return $this->render_lesson( $item, $course_id );
					}
				},
				$structure
			)
		);

		return '<div class="sensei-lms-course-outline-view">
			' . $outline_html . '
			' . $this->render_svg_icon_library() . '
		</div>';
	}


	/**
	 * Get module block HTML.
	 *
	 * @param array $module    Module data.
	 * @param int   $course_id The course id.
	 *
	 * @return string Module HTML
	 */
	public function render_module( $module, $course_id ) {

		$module_id = $module['id'];
		$title     = esc_html( $module['title'] );
		$lessons   = $module['lessons'];
		//$user_progress = Sensei()->modules->get_user_module_progress( $module_id, $course_id, get_current_user_id() );

		$module_url = add_query_arg( 'course_id', $course_id, get_term_link( $module_id, 'module' ) );

		$collapse_toggle = '<button type="button" class="wp-block-sensei-lms-course-outline__arrow sensei-collapsible__toggle">
						<svg><use xlink:href="#sensei-chevron-up"></use></svg>
						<span class="screen-reader-text">' . esc_html__( 'Toggle module content', 'sensei-lms' ) . '</span>
					</button>';

		$lessons_html = implode(
			'',
			array_map(
				function( $lesson ) use ( $course_id ) {
					return $this->render_lesson( $lesson, $course_id );
				},
				$lessons
			)
		);

		return '
			<section class="sensei-collapsible">
				<header class="wp-block-sensei-lms-course-outline-module__header">
					<h2 class="wp-block-sensei-lms-course-outline-module__title">
						<a href="' . esc_url( $module_url ) . '">' . $title . '</a>
					</h2>
					' .
			( ! empty( $this->settings['collapsibleModules'] ) ? $collapse_toggle : '' ) .
			'</header>
				<div class="wp-block-sensei-lms-collapsible sensei-collapsible__content">
					' .
			$lessons_html
			. '
				</div>
			</section>
		';
	}


	public function render_lesson( $lesson, $course_id ) {
		$lesson_id  = $lesson['id'];
		$status     = $this->get_user_lesson_status( $lesson_id );
		$is_current = $lesson_id === Sensei_Utils::get_current_lesson();
		$has_quiz   = Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );
		$quiz_id    = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$classes = [ 'wp-block-sensei-lms-course-outline-lesson', 'status-' . $status ];

		if ( $is_current ) {
			$classes[] = 'current-lesson';
		}

		$lesson_quiz_html = '';

		if( $has_quiz ) {
			$lesson_quiz_html = '<a class="wp-block-sensei-lms-course-outline-lesson__quiz" href="' . esc_url( get_permalink( $quiz_id ) ) . '">' . __( 'Quiz', 'sensei-lms' ) . '</a>';
		}

		return '
		<div ' . Sensei_Block_Helpers::render_style_attributes( $classes, [] ) . '>
			<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" class="wp-block-sensei-lms-course-outline-lesson__link">
				' . self::lesson_status_icon( $status ) . '
				<span class="wp-block-sensei-lms-course-outline-lesson__title">
					' . esc_html( $lesson['title'] ) . '
				</span>
			</a>
			' . $lesson_quiz_html . '
		</div>';
	}

	public static function lesson_status_icon( $status ) {
		return '<svg class="wp-block-sensei-lms-course-outline-lesson__status">
					<use xlink:href="#sensei-lesson-status-' . $status . '"></use>
				</svg>';
	}

	/**
	 * Get the lesson status for the user.
	 *
	 * @param $lesson_id
	 *
	 * @return string
	 */
	private function get_user_lesson_status( $lesson_id ): string {
		$status    = 'not-started';
		$completed = Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() );

		if ( $completed ) {
			$status = 'completed';
		} else {
			$user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, get_current_user_id() );
			if ( isset( $user_lesson_status->comment_approved ) ) {
				$status = $user_lesson_status->comment_approved;
			}
		}
		return $status;
	}

	/**
	 * Build HTML to reference SVG icons from.
	 *
	 * @return string
	 */
	private function render_svg_icon_library() {
		return '<svg xmlns="http://www.w3.org/2000/svg" style="display: none">
			<symbol id="sensei-chevron-right" viewBox="0 0 24 24">
				<path d="M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" fill="" />
			</symbol>
			<symbol id="sensei-chevron-up" viewBox="0 0 24 24">
				<path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="" />
			</symbol>
			<symbol id="sensei-lesson-status-not-started" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M12 18.667a6.667 6.667 0 100-13.334 6.667 6.667 0 000 13.334zM12 20a8 8 0 100-16 8 8 0 000 16z"/>
			</symbol>
			<symbol id="sensei-lesson-status-in-progress" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M4 12a8 8 0 1116 0v.052A8 8 0 014 12zm1.333 0a6.667 6.667 0 0113.334 0H5.333z"/>
			</symbol>
			<symbol id="sensei-lesson-status-completed" viewBox="0 0 24 24">
        <path fill-rule="evenodd" d="M12 20a8 8 0 100-16 8 8 0 000 16zm-1.024-3.949l5.764-7.753-.802-.596-5.466 7.351-2.942-2.187-.596.802 3.342 2.486.402.298.298-.401z"/>
			</symbol>
		</svg>';
	}

}

<?php
/**
 * File containing the Sensei_Course_Navigation_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Navigation_Block
 */
class Sensei_Course_Navigation_Block {

	/**
	 * Sensei_Course_Navigation_Block constructor.
	 */
	public function __construct() {

		$this->register_block();

	}

	/**
	 * Register course navigation block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-navigation',
			[
				'render_callback' => [ $this, 'render_course_navigation' ],
			]
		);
	}

	/**
	 * Render Course Navigation View block.
	 *
	 * @access private
	 *
	 * @return string Block HTML.
	 */
	public function render_course_navigation() {

		$course_id = Sensei_Utils::get_current_course();

		if ( ! $course_id ) {
			return '';
		}

		$structure = Sensei_Course_Structure::instance( $course_id )->get();

		$modules_html = implode(
			'',
			array_map(
				function( $item ) use ( $course_id ) {
					if ( 'module' === $item['type'] ) {
						return $this->render_module( $item, $course_id );
					}
					return '';
				},
				$structure
			)
		);

		$lessons_html = implode(
			'',
			array_map(
				function( $item ) use ( $course_id ) {
					if ( 'lesson' === $item['type'] ) {
						return $this->render_lesson( $item );
					}
					return '';
				},
				$structure
			)
		);

		if ( $modules_html ) {
			$modules_html = '<div class="sensei-lms-course-navigation__modules">
				' . $modules_html . '
			</div>';
		}

		if ( $lessons_html ) {
			$lessons_html = '<div class="sensei-lms-course-navigation__lessons">
				' . $lessons_html . '
			</div>';
		}

		return '<div class="sensei-lms-course-navigation">
			' . $modules_html . '
			' . $lessons_html . '
			' . $this->render_svg_icon_library() . '
		</div>';
	}


	/**
	 * Build module block HTML.
	 *
	 * @param array $module    Module data.
	 * @param int   $course_id The course id.
	 *
	 * @return string Module HTML
	 */
	public function render_module( $module, $course_id ) {

		$module_id  = $module['id'];
		$title      = esc_html( $module['title'] );
		$lessons    = $module['lessons'];
		$module_url = add_query_arg( 'course_id', $course_id, get_term_link( $module_id, 'module' ) );

		$lessons_html = implode(
			'',
			array_map(
				function( $lesson ) {
					return $this->render_lesson( $lesson );
				},
				$lessons
			)
		);

		$current_lesson_id  = Sensei_Utils::get_current_lesson();
		$has_current_lesson = count(
			array_filter(
				$lessons,
				function( $lesson ) use ( $current_lesson_id ) {
					return $current_lesson_id === $lesson['id'];
				}
			)
		);
		$is_current_module  = get_the_ID() === $module_id || $has_current_lesson;

		$lesson_count = count( $lessons );
		$quiz_count   = count(
			array_filter(
				$lessons,
				function( $lesson ) {
					return Sensei_Lesson::lesson_quiz_has_questions( $lesson['id'] );
				}
			)
		);

		// Translators: placeholder is number of lessons.
		$summary_lessons = _n( '%d lesson', '%d lessons', $lesson_count, 'sensei-lms' );
		// Translators: placeholder is number of quizzes.
		$summary_quizzes = _n( '%d quiz', '%d quizzes', $quiz_count, 'sensei-lms' );
		$summary         = sprintf( $summary_lessons . ', ' . $summary_quizzes, $lesson_count, $quiz_count );

		$classes   = [ 'sensei-lms-course-navigation-module sensei-collapsible' ];
		$collapsed = '';
		if ( ! $is_current_module ) {
			$collapsed = 'collapsed';
		}

		$collapse_toggle = '<button type="button" class="sensei-lms-course-navigation__arrow sensei-collapsible__toggle ' . $collapsed . '">
				<svg><use xlink:href="#sensei-chevron-up"></use></svg>
				<span class="screen-reader-text">' . esc_html__( 'Toggle module content', 'sensei-lms' ) . '</span>
			</button>';

		return '
			<section ' . Sensei_Block_Helpers::render_style_attributes( $classes, [] ) . '>
				<header class="sensei-lms-course-navigation-module__header">
					<h2 class="sensei-lms-course-navigation-module__title">
						<a href="' . esc_url( $module_url ) . '">' . $title . '</a>
					</h2>
					' . $collapse_toggle .
			'</header>
				<div class="sensei-lms-course-navigation-module__lessons sensei-collapsible__content ' . $collapsed . '">
					' . $lessons_html . '
				</div>
				<div class="sensei-lms-course-navigation-module__summary">
				' . wp_kses_post( $summary ) . '
				</div>
			</section>
		';
	}

	/**
	 * Build lesson HTML.
	 *
	 * @param array $lesson Lesson data.
	 *
	 * @return string
	 */
	public function render_lesson( $lesson ) {
		$lesson_id  = $lesson['id'];
		$status     = $this->get_user_lesson_status( $lesson_id );
		$is_current = Sensei_Utils::get_current_lesson() === $lesson_id;
		$has_quiz   = Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );
		$quiz_id    = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$classes = [ 'sensei-lms-course-navigation-lesson', 'status-' . $status ];

		if ( $is_current ) {
			$classes[] = 'current-lesson';
		}

		$lesson_quiz_html = '';

		if ( $has_quiz ) {
			$lesson_quiz_html = '<a class="sensei-lms-course-navigation-lesson__quiz" href="' . esc_url( get_permalink( $quiz_id ) ) . '">' . esc_html__( 'Quiz', 'sensei-lms' ) . '</a>';
		}

		return '
		<div ' . Sensei_Block_Helpers::render_style_attributes( $classes, [] ) . '>
			<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" class="sensei-lms-course-navigation-lesson__link">
				' . self::lesson_status_icon( $status ) . '
				<span class="sensei-lms-course-navigation-lesson__title">
					' . esc_html( $lesson['title'] ) . '
				</span>
			</a>
			' . $lesson_quiz_html . '
		</div>';
	}

	/**
	 * Get the lesson status icon.
	 *
	 * @param string $status
	 *
	 * @return string Icon HTML.
	 */
	public static function lesson_status_icon( $status ) {
		return '<svg class="sensei-lms-course-navigation-lesson__status">
					<use xlink:href="#sensei-lesson-status-' . $status . '"></use>
				</svg>';
	}

	/**
	 * Get the lesson status string for the user.
	 *
	 * @param int $lesson_id
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
			<symbol id="sensei-contact-teacher-success" viewBox="0 0 42 42" fill="none">
				<circle cx="21" cy="21" r="20.25" stroke="#30968B" stroke-width="1.5"/>
				<path d="M27.1135 15.1507L18.8804 26.2233L14.1064 22.6735" stroke="#30968B" stroke-width="2"/>
			</symbol>
			<symbol id="sensei-close" viewBox="0 0 16 16" fill="none">
				<path d="M1.40456 1L15 15M1 15L14.5954 1" stroke="#1E1E1E" stroke-width="1.5" />
			</symbol>
		</svg>';
	}

}

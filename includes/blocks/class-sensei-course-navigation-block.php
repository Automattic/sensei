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
	 * Course ID.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Sensei_Course_Navigation_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Register course navigation block.
	 */
	private function register_block() {
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
		$this->course_id = Sensei_Utils::get_current_course();

		if ( ! $this->course_id ) {
			return '';
		}

		$structure = Sensei_Course_Structure::instance( $this->course_id )->get();

		$modules_html = implode(
			'',
			array_map(
				function( $item ) {
					if ( 'module' === $item['type'] ) {
						return $this->render_module( $item );
					}
					return '';
				},
				$structure
			)
		);

		$lessons_html = implode(
			'',
			array_map(
				function( $item ) {
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
		</div>';
	}


	/**
	 * Build module block HTML.
	 *
	 * @param array $module Module data.
	 *
	 * @return string Module HTML
	 */
	private function render_module( $module ) {
		$module_id  = $module['id'];
		$title      = esc_html( $module['title'] );
		$lessons    = $module['lessons'];
		$module_url = add_query_arg( 'course_id', $this->course_id, get_term_link( $module_id, 'module' ) );

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
				' . Sensei()->assets->get_icon( 'chevron-up' ) . '
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
	private function render_lesson( $lesson ) {
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
				' . $this->lesson_status_icon( $status ) . '
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
	private function lesson_status_icon( $status ) {
		return Sensei()->assets->get_icon( 'lesson-status-' . $status, 'sensei-lms-course-navigation-lesson__status' );
	}

	/**
	 * Get the lesson status string for the user.
	 *
	 * @param int $lesson_id
	 *
	 * @return string
	 */
	private function get_user_lesson_status( $lesson_id ): string {
		$status               = 'not-started';
		$completed            = Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() );
		$in_progress_statuses = [ 'failed', 'ungraded' ];

		if ( $completed ) {
			$status = 'completed';
		} else {
			$user_lesson_status = Sensei_Utils::user_lesson_status( $lesson_id, get_current_user_id() );
			if ( isset( $user_lesson_status->comment_approved ) ) {
				$status = $user_lesson_status->comment_approved;

				if ( in_array( $status, $in_progress_statuses, true ) ) {
					$status = 'in-progress';
				}
			}
		}

		return $status;
	}
}

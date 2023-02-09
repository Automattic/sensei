<?php
/**
 * File containing the Course_Navigation class.
 *
 * @package sensei
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course navigation block.
 */
class Course_Navigation {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/course-navigation/course-navigation.block.json';

	const ICONS = [
		'not-started' => 'circle',
		'in-progress' => 'half-filled-circle',
		'ungraded'    => 'half-filled-circle',
		'completed'   => 'check-filled-circle',
		'failed'      => 'half-filled-circle',
		'locked'      => 'lock',
		'preview'     => 'eye',
	];

	/**
	 * Course ID.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Whether user is enrolled.
	 *
	 * @var bool
	 */
	private $is_enrolled;

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
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		\Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-navigation',
			[
				'render_callback' => [ $this, 'render_course_navigation' ],
				'style'           => 'sensei-theme-blocks',
				'script'          => 'sensei-blocks-frontend',
			],
			$block_json_path
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
		$this->course_id = \Sensei_Utils::get_current_course();

		if ( ! $this->course_id || ! in_array( get_post_type(), [ 'lesson', 'quiz' ], true ) ) {
			return '';
		}

		$this->is_enrolled = \Sensei_Course::is_user_enrolled( $this->course_id );
		$this->user_id     = get_current_user_id();
		$structure         = \Sensei_Course_Structure::instance( $this->course_id )->get();

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
			$modules_html = '<ol class="sensei-lms-course-navigation__modules">
				' . $modules_html . '
			</ol>';
		}

		if ( $lessons_html ) {
			$lessons_html = '<ol class="sensei-lms-course-navigation__lessons">
				' . $lessons_html . '
			</ol>';
		}

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-lms-course-navigation',
			]
		);

		return sprintf(
			'<nav aria-label="%1$s"%2$s>
			%3$s
			%4$s
		</nav>',
			esc_attr__( 'Course outline', 'sensei-lms' ),
			$wrapper_attr,
			$modules_html,
			$lessons_html
		);
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

		$current_lesson_id  = \Sensei_Utils::get_current_lesson();
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
					return \Sensei_Lesson::lesson_quiz_has_questions( $lesson['id'] );
				}
			)
		);

		// Translators: placeholder is number of lessons.
		$summary_lessons = _n( '%d lesson', '%d lessons', $lesson_count, 'sensei-lms' );
		// Translators: placeholder is number of quizzes.
		$summary_quizzes = _n( '%d quiz', '%d quizzes', $quiz_count, 'sensei-lms' );
		$summary         = 0 === $quiz_count
			? sprintf( $summary_lessons, $lesson_count )
			: sprintf( $summary_lessons . ', ' . $summary_quizzes, $lesson_count, $quiz_count );

		$classes   = [ 'sensei-lms-course-navigation-module sensei-collapsible' ];
		$collapsed = '';
		if ( ! $is_current_module ) {
			$collapsed = 'sensei-collapsed';
		}

		$content_id = esc_attr( 'sensei-course-navigation-module-' . $module_id . '-' . wp_generate_uuid4() );

		return '
			<li ' . \Sensei_Block_Helpers::render_style_attributes( $classes, [] ) . '>
				<div class="sensei-lms-course-navigation-module__header">
					<button type="button" class="sensei-collapsible__toggle sensei-lms-course-navigation-module__button ' . $collapsed . '"
						aria-controls="' . $content_id . '" aria-expanded="' . esc_attr( $is_current_module ? 'true' : 'false' ) . '">
						<div class="sensei-lms-course-navigation-module__title">' . $title . '</div>
						' . Sensei()->assets->get_icon( 'chevron-up', 'sensei-lms-course-navigation-module__collapsible-icon' ) . '
					</button>
				</div>
				<ol id="' . $content_id . '" class="sensei-lms-course-navigation-module__lessons sensei-collapsible__content ' . $collapsed . '">
					' . $lessons_html . '
				</ol>
				<div class="sensei-lms-course-navigation-module__summary">
				' . wp_kses_post( $summary ) . '
				</div>
			</li>
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
		$lesson_id     = $lesson['id'];
		$locked_lesson = ! $this->is_enrolled || ! \Sensei_Lesson::is_prerequisite_complete( $lesson_id, $this->user_id );
		$status        = $this->get_user_lesson_status( $lesson, $locked_lesson );
		$is_current    = \Sensei_Utils::get_current_lesson() === $lesson_id;
		$has_quiz      = \Sensei_Lesson::lesson_quiz_has_questions( $lesson_id );
		$quiz_id       = Sensei()->lesson->lesson_quizzes( $lesson_id );

		$classes = [ 'sensei-lms-course-navigation-lesson', 'status-' . $status ];

		if ( $is_current ) {
			$classes[] = 'current-lesson';
		}

		$extra_html = '';

		if ( $lesson['preview'] && $locked_lesson ) {
			// Translators: placeholder is the lesson title.
			$preview_label = sprintf( __( 'Preview lesson %s', 'sensei-lms' ), $lesson['title'] );
			$extra_html    = '<a class="sensei-lms-course-navigation-lesson__extra" href="' . esc_url( get_permalink( $lesson_id ) ) . '" aria-label="' . esc_attr( $preview_label ) . '" >' . esc_html__( 'Preview', 'sensei-lms' ) . '</a>';
		} elseif ( $has_quiz && ! $locked_lesson ) {
			// Translators: placeholder is the lesson title.
			$quiz_label = sprintf( __( 'View quiz for %s', 'sensei-lms' ), $lesson['title'] );
			$extra_html = '<a class="sensei-lms-course-navigation-lesson__extra" href="' . esc_url( get_permalink( $quiz_id ) ) . '" aria-label="' . esc_attr( $quiz_label ) . '" >' . esc_html__( 'Quiz', 'sensei-lms' ) . '</a>';
		}

		return '
		<li ' . \Sensei_Block_Helpers::render_style_attributes( $classes, [] ) . '>
			<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" class="sensei-lms-course-navigation-lesson__link">
				' . $this->lesson_status_icon( $status ) . '
				<span class="sensei-lms-course-navigation-lesson__title">
					' . esc_html( $lesson['title'] ) . '
				</span>
			</a>
			' . $extra_html . '
		</li>';
	}

	/**
	 * Get the lesson status icon.
	 *
	 * @param string $status The lesson status.
	 *
	 * @return string Icon HTML.
	 */
	private function lesson_status_icon( $status ) {
		$icon = Sensei()->assets->get_icon( self::ICONS[ $status ], 'sensei-lms-course-navigation-lesson__status' );

		return apply_filters( 'sensei_learning_mode_lesson_status_icon', $icon, $status );
	}

	/**
	 * Get the lesson status string for the user.
	 *
	 * @param array $lesson        Lesson object.
	 * @param bool  $locked_lesson Whether lesson is locked.
	 *
	 * @return string
	 */
	private function get_user_lesson_status( $lesson, $locked_lesson ): string {
		if ( $locked_lesson ) {
			if ( $lesson['preview'] ) {
				return 'preview';
			}

			return 'locked';
		}

		$lesson_id            = $lesson['id'];
		$status               = 'not-started';
		$completed            = \Sensei_Utils::user_completed_lesson( $lesson_id, $this->user_id );
		$in_progress_statuses = [ 'failed', 'ungraded' ];

		if ( $completed ) {
			$status = 'completed';
		} else {
			$user_lesson_status = \Sensei_Utils::user_lesson_status( $lesson_id, $this->user_id );
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

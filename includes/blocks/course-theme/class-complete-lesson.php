<?php
/**
 * File containing the Complete_Lesson class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Complete_Lesson is responsible for rendering the 'Mark complete' block.
 */
class Complete_Lesson {

	/**
	 * Complete_Lesson constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-complete-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render() : string {
		$post = get_post();

		// Return empty if we can't get the post for some reason.
		if ( empty( $post ) ) {
			return '';
		}

		$course_id = Sensei()->lesson->get_course_id( $post->ID );

		if (
			// Return empty if it is not a lesson post.
			'lesson' !== $post->post_type ||

			// Return empty if user is not enrolled.
			! \Sensei_Course::is_user_enrolled( $course_id ) ||

			// Return empty if the lesson requires a quiz pass.
			Sensei()->lesson->lesson_has_quiz_with_questions_and_pass_required( $post->ID )
		) {
			return '';
		}

		// Render "Completed" if user already completed the lesson.
		if ( \Sensei_Utils::user_completed_lesson( $post->ID ) ) {
			$text = esc_html( __( 'Completed', 'sensei-lms' ) );
			$icon = \Sensei()->assets->get_icon( 'check' );

			return ( "
				<div class='sensei-course-theme-completed-lesson'>
					<div class='sensei-course-theme-completed-lesson-inner'>
						{$icon}<span>{$text}</span>
					</div>
				</div>
			" );
		}

		// Render "Mark Complete" button.
		$nonce     = wp_nonce_field( 'woothemes_sensei_complete_lesson_noonce', 'woothemes_sensei_complete_lesson_noonce', false, false );
		$permalink = esc_url( get_permalink() );
		$text      = esc_html( __( 'Mark complete', 'sensei-lms' ) );

		return ( "
			<form class='sensei-course-theme-complete-lesson-form' method='POST' action='{$permalink}'>
				{$nonce}
				<input type='hidden' name='quiz_action' value='lesson-complete' />
				<button type='submit' class='sensei-course-theme-complete-lesson'>
					{$text}
				</button>
			</form>
		" );
	}
}

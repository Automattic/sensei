<?php
/**
 * File containing the Sensei\Blocks\Course_Theme\Quiz_Content class.
 *
 * @package sensei
 * @since 4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the current quiz.
 * Uses Sensei templates.
 */
class Quiz_Content {

	/**
	 * Render the current quiz page's content.
	 *
	 * @return string HTML.
	 */
	public static function render_quiz() {

		remove_action( 'sensei_single_quiz_questions_before', [ Sensei()->post_types->messages, 'send_message_link' ], 10 );
		remove_action( 'sensei_single_quiz_questions_after', [ 'Sensei_Quiz', 'action_buttons' ], 10 );

		remove_action( 'sensei_single_quiz_content_inside_before', array( 'Sensei_Quiz', 'the_user_status_message' ), 40 );
		do_action( 'sensei_single_quiz_content_inside_before', get_the_ID() );

		if ( ! sensei_can_user_view_lesson() ) {
			return '';
		}

		$content = self::render_questions_loop();

		return "<div>
			<ol id='sensei-quiz-list'>{$content}</ol>
		</div>";
	}

	/**
	 * Render the questions.
	 *
	 * @return string
	 */
	private static function render_questions_loop() {

		ob_start();
		do_action( 'sensei_single_quiz_questions_before', get_the_id() );

		while ( sensei_quiz_has_questions() ) {
			sensei_setup_the_question();
			?>
			<li class="sensei-quiz-question <?php sensei_the_question_class(); ?>">
				<?php
				do_action( 'sensei_quiz_question_inside_before', sensei_get_the_question_id() );
				sensei_the_question_content();
				do_action( 'sensei_quiz_question_inside_after', sensei_get_the_question_id() );
				?>
			</li>
			<?php
		}

		// In "Learning Mode" we do not want the quiz pagination as part
		// of the quiz post content. Because we will render it separately
		// in the footer of the "Learning Mode" screen.
		remove_action( 'sensei_single_quiz_questions_after', array( 'Sensei_Quiz', 'the_quiz_pagination' ), 9 );
		do_action( 'sensei_single_quiz_questions_after', get_the_id() );

		return ob_get_clean();
	}

}

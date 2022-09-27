<?php
/**
 * File containing the Sensei\Blocks\Course_Theme\Quiz_Content class.
 *
 * @package sensei
 * @since   4.0.0
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

		// The following content are rendered separately in Learning Mode.
		// So we need to remove them from here, otherwise they are repeated.
		remove_action( 'sensei_single_quiz_questions_before', [ Sensei()->post_types->messages, 'send_message_link' ], 10 );
		remove_action( 'sensei_single_quiz_questions_after', [ 'Sensei_Quiz', 'action_buttons' ], 10 );
		remove_action( 'sensei_single_quiz_content_inside_before', [ 'Sensei_Quiz', 'the_user_status_message' ], 40 );
		remove_action( 'sensei_single_quiz_content_inside_before', [ 'Sensei_Quiz', 'the_title' ], 20 );
		remove_action( 'sensei_single_quiz_questions_before', [ 'Sensei_Quiz', 'the_quiz_progress_bar' ], 20 );

		ob_start();

		do_action( 'sensei_single_quiz_content_inside_before', get_the_ID() );

		if ( ! sensei_can_user_view_lesson() ) {
			return ob_get_clean();
		}

		self::render_questions_loop();

		do_action( 'sensei_single_quiz_content_inside_after', get_the_ID() );

		$content = ob_get_clean();

		return ( "<form id='sensei-quiz-form' method='post' enctype='multipart/form-data' class='sensei-form'>{$content}</form>" );
	}

	/**
	 * Render the questions.
	 */
	private static function render_questions_loop() {

		do_action( 'sensei_single_quiz_questions_before', get_the_id() );

		echo "<ol id='sensei-quiz-list'>";

		while ( sensei_quiz_has_questions() ) {
			sensei_setup_the_question();
			?>
			<li
				class="sensei-quiz-question <?php sensei_the_question_class(); ?>"
				value="<?php echo esc_attr( sensei_get_the_question_number() ); ?>"
			>
				<?php
				do_action( 'sensei_quiz_question_inside_before', sensei_get_the_question_id() );
				sensei_the_question_content();
				do_action( 'sensei_quiz_question_inside_after', sensei_get_the_question_id() );
				?>
			</li>
			<?php
		}

		echo '</ol>';

		// In "Learning Mode" we do not want the quiz pagination as part
		// of the quiz post content. Because we will render it separately
		// in the footer of the "Learning Mode" screen.
		remove_action( 'sensei_single_quiz_questions_after', [ 'Sensei_Quiz', 'the_quiz_pagination' ], 9 );
		do_action( 'sensei_single_quiz_questions_after', get_the_id() );
	}

}

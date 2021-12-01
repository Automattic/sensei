<?php
/**
 * File containing the Sensei\Blocks\Course_Theme\The_Content class.
 *
 * @package sensei
 * @since 4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Block to render the content for the current lesson, quiz or module page.
 */
class The_Content {

	/**
	 * Content constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/the-content',
			[
				'render_callback' => [ $this, 'render_content' ],
			]
		);
	}

	/**
	 * Render content for the current page.
	 *
	 * @access private
	 *
	 * @return string HTML
	 */
	public function render_content() {
		$type = get_post_type();

		if ( is_tax( 'module' ) ) {
			$type = 'module';
		}

		switch ( $type ) {
			case 'quiz':
				return $this->render_quiz_content();
			case 'lesson':
				return $this->render_lesson_content();
			case 'module':
				return $this->render_module_content();
		}

		return '';

	}

	/**
	 * Render the current lesson page's content.
	 *
	 * @return false|string
	 */
	private function render_lesson_content() {
		ob_start();

		if ( sensei_can_user_view_lesson() ) {
			the_content();
		} else {

			wp_kses_post( get_the_excerpt() );

		}
		return ob_get_clean();
	}

	/**
	 * Render the current module page's content.
	 *
	 * @return string
	 */
	private function render_module_content() {
		return get_the_content();
	}

	/**
	 * Render the current quiz page's content.
	 *
	 * @access private
	 *
	 * @return string HTML.
	 */
	private function render_quiz_content() {

		remove_action( 'sensei_single_quiz_questions_before', [ Sensei()->post_types->messages, 'send_message_link' ], 10 );
		remove_action( 'sensei_single_quiz_questions_after', [ 'Sensei_Quiz', 'action_buttons' ], 10 );

		\Sensei_Quiz::start_quiz_questions_loop();

		if ( ! sensei_can_user_view_lesson() ) {
			return '';
		}

		$content = $this->render_questions_loop();

		return "<form method='post' enctype='multipart/form-data'>
				<ol id='sensei-quiz-list'>{$content}</ol>
			</form>";
	}

	/**
	 * Render the questions.
	 *
	 * @return string
	 */
	private function render_questions_loop() {

		ob_start();
		do_action( 'sensei_single_quiz_questions_before', get_the_id() );

		while ( sensei_quiz_has_questions() ) {
			sensei_setup_the_question();
			?>
			<li class="sensei-quiz-question <?php sensei_the_question_class(); ?>">
				<div>
					<?php
					do_action( 'sensei_quiz_question_inside_before', sensei_get_the_question_id() );
					sensei_the_question_content();
					do_action( 'sensei_quiz_question_inside_after', sensei_get_the_question_id() );
					?>
				</div>
			</li>
			<?php
		}

		do_action( 'sensei_single_quiz_questions_after', get_the_id() );

		return ob_get_clean();
	}

}

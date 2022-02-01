<?php
/**
 * File containing the Quiz_Actions class.
 *
 * @package sensei
 * @since 4.0.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use Sensei_Quiz;

/**
 * Class Quiz_Actions is responsible for rendering the quiz
 * actions button as well as quiz pagination if enabled.
 */
class Quiz_Actions {
	/**
	 * Quiz_Actions constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-actions',
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

		if ( ! sensei_can_user_view_lesson() || 'quiz' !== get_post_type() ) {
			return '';
		}

		global $sensei_question_loop;
		$pagination_enabled = $sensei_question_loop && $sensei_question_loop['total_pages'] > 1;

		// Get quiz actions. Either actions with pagination
		// or only action if pagination is not enabled.
		ob_start();
		if ( $pagination_enabled ) {
			Sensei_Quiz::the_quiz_pagination();
			Sensei_Quiz::output_quiz_hidden_fields();
		} else {
			Sensei_Quiz::action_buttons();
		}
		$actions = ob_get_clean();

		if ( ! $actions ) {
			return '';
		}

		return $actions;
	}
}

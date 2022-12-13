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
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/quiz-blocks/quiz-actions.block.json';

	/**
	 * Quiz_Actions constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-actions',
			[
				'render_callback' => [ $this, 'render' ],
				'style'           => 'sensei-theme-blocks',
			],
			$block_json_path
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

		$lesson_id = \Sensei_Utils::get_current_lesson();

		$sensei_is_quiz_available = Sensei_Quiz::is_quiz_available();
		$sensei_is_quiz_completed = Sensei_Quiz::is_quiz_completed();

		// Get quiz actions. Either actions with pagination
		// or only action if pagination is not enabled.
		// Also, don't paginate if quiz has been completed.
		ob_start();
		if ( $pagination_enabled && $sensei_is_quiz_available && ! $sensei_is_quiz_completed ) {
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

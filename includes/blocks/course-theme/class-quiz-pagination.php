<?php
/**
 * File containing the Quiz_Pagination class.
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
 * Class Quiz_Pagination is responsible for rendering the quiz pagination block.
 */
class Quiz_Pagination {
	/**
	 * Quiz_Pagination constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/quiz-pagination',
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
		\Sensei_Quiz::start_quiz_questions_loop();
		global $sensei_question_loop;
		$pagination_enabled = $sensei_question_loop['total_pages'] > 1;
		$pagination         = '';

		// Get quiz actions. Either actions with pagination
		// or only action if pagination is not enabled.
		ob_start();
		if ( $pagination_enabled ) {
			Sensei_Quiz::the_quiz_pagination();
		} else {
			Sensei_Quiz::action_buttons();
		}
		$pagination = ob_get_clean();

		return ( "
			<form method='POST' enctype='multipart/form-data'>
				{$pagination}
			</form>
		" );
	}
}

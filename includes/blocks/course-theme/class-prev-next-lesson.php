<?php
/**
 * File containing the Prev_Next_Lesson class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use \Sensei\Blocks\Course_Theme\Prev_Lesson;
use \Sensei\Blocks\Course_Theme\Next_Lesson;

/**
 * Class Prev_Next_Lesson is responsible for rendering the '< Prev Lesson | Next Lesson >' blocks.
 */
class Prev_Next_Lesson {

	/**
	 * Reference to the previous lesson button block.
	 *
	 * @var Sensei_CT_Prev_Lesson_Block
	 */
	private $prev = null;

	/**
	 * Reference to the previous lesson button block.
	 *
	 * @var Sensei_CT_Next_Lesson_Block
	 */
	private $next = null;

	/**
	 * Prev_Next_Lesson constructor.
	 *
	 * @param Prev_Lesson $prev The previous lesson block.
	 * @param Next_Lesson $next The next lesson block.
	 */
	public function __construct( Prev_Lesson $prev, Next_Lesson $next ) {
		$this->prev = $prev;
		$this->next = $next;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-prev-next-lesson',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @return string The block HTML.
	 */
	public function render() : string {
		$prev = $this->prev->render();
		$next = $this->next->render();
		return "<div  class='sensei-course-theme-prev-next-lesson-container'>$prev $next</div>";
	}
}

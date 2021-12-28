<?php
/**
 * File containing the Notices class.
 *
 * @package sensei
 * @since 3.15.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;
use \Sensei_Context_Notices;

/**
 * Class Notices
 */
class Notices {
	/**
	 * Notices constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-notices',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ) : string {
		$notices_html = Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'course-theme/lesson-quiz-notice.php' )
			. Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'course-theme/locked-lesson-notice.php' )
			. Sensei_Context_Notices::instance( 'course_theme_quiz_grade' )->get_notices_html( 'course-theme/quiz-grade-notice.php' );

		return $notices_html;
	}
}

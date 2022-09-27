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
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/learning-mode-notices.block.json';

	/**
	 * Notices constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'learning-mode/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/learning-mode-notices',
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
	 * @param array $attributes The block attributes.
	 *
	 * @access private
	 *
	 * @return string The block HTML.
	 */
	public function render( array $attributes = [] ) : string {
		$notices_html = Sensei_Context_Notices::instance( 'course_theme_lesson_regular' )->get_notices_html( 'learning-mode/lesson-quiz-notice.php' )
			. Sensei_Context_Notices::instance( 'course_theme_lesson_quiz' )->get_notices_html( 'learning-mode/lesson-quiz-notice.php' )
			. Sensei_Context_Notices::instance( 'course_theme_locked_lesson' )->get_notices_html( 'learning-mode/locked-lesson-notice.php' )
			. Sensei_Context_Notices::instance( 'course_theme_quiz_grade' )->get_notices_html( 'learning-mode/quiz-grade-notice.php' );

		$wrapper_attr = get_block_wrapper_attributes();
		return sprintf( '<div %1$s>%2$s</div>', $wrapper_attr, $notices_html );
	}
}

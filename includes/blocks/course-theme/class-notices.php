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
		return Sensei_Context_Notices::instance( 'course_theme', 'sensei-course-theme' )->get_notices_html();
	}
}

<?php
/**
 * File containing the Course_Progress_Bar class.
 *
 * @package sensei
 * @since 3.13.4
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Course_Progress_Bar is responsible for rendering the '[==========----------]' block.
 */
class Course_Progress_Bar {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/course-theme-course-progress-bar.block.json';

	/**
	 * Course_Progress_Bar constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-course-progress-bar',
			[
				'render_callback' => [ $this, 'render' ],
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
		$course_id = \Sensei_Utils::get_current_course();
		if ( ! $course_id ) {
			return '';
		}

		$stats = Sensei()->course->get_progress_stats( $course_id );

		return sprintf(
			'<div class="sensei-course-theme-course-progress-bar">
				<div class="sensei-course-theme-course-progress-bar-inner" style="width: %s%%;" data-completed="%d" data-count="%d"></div>
			</div>',
			$stats['completed_lessons_percentage'],
			$stats['completed_lessons_count'],
			$stats['lessons_count']
		);
	}
}

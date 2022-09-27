<?php
/**
 * File containing the Course_Progress_Counter class.
 *
 * @package sensei
 * @since 3.15.0
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks;

/**
 * Class Course_Progress_Counter is responsible for rendering the '1 of 10 lessons complete (10%)' block.
 */
class Course_Progress_Counter {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/learning-mode-course-progress-counter.block.json';

	/**
	 * Course_Progress_Counter constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'learning-mode/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/learning-mode-course-progress-counter',
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
		$course_id = \Sensei_Utils::get_current_course();
		if ( ! $course_id ) {
			return '';
		}

		$stats  = \Sensei()->course->get_progress_stats( $course_id );
		$output = sprintf(
			/* translators: Placeholder %1$d is the completed lessons count, %2$d is the total lessons count and %3$d is the percentage of completed lessons. */
			__( '%1$d of %2$d lessons complete (%3$d%%)', 'sensei-lms' ),
			$stats['completed_lessons_count'],
			$stats['lessons_count'],
			$stats['completed_lessons_percentage']
		);

		$wrapper_attr = get_block_wrapper_attributes(
			[
				'class' => 'sensei-learning-mode-course-progress',
			]
		);

		return sprintf(
			'
			<div %s>
				%s
			</div>
		',
			$wrapper_attr,
			$output
		);
	}
}

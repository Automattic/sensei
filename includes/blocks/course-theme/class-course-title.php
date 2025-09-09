<?php
/**
 * File containing the Course_Title class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Sensei_Blocks;
use Sensei_Utils;

/**
 * Display the title of the current course for the current lesson/quiz/module.
 */
class Course_Title {
	/**
	 * Block JSON file.
	 */
	const BLOCK_JSON_FILE = '/lesson-blocks/course-title/block.json';

	/**
	 * Course_Title constructor.
	 */
	public function __construct() {
		$block_json_path = Sensei()->assets->src_path( 'course-theme/blocks' ) . self::BLOCK_JSON_FILE;
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-title',
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
	public function render( array $attributes = [] ): string {

		// Get heading level from attributes.
		$level   = isset( $attributes['level'] ) ? strtolower( (string) $attributes['level'] ) : '1';
		$heading = 'h' . $level;

		// Whitelist Heading tag.
		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		if ( ! in_array( $heading, $allowed, true ) ) {
			$heading = 'h1';
		}

		// Get the current course ID.
		$course_id = Sensei_Utils::get_current_course();

		if ( ! $course_id ) {
			return '';
		}

		$title = get_the_title( $course_id );

		if ( ! $title ) {
			return '';
		}

		$wrapper_attributes = get_block_wrapper_attributes();

		return sprintf(
			'<%1s %2s>%3$s</%1s>',
			$heading,
			$wrapper_attributes,
			$title
		);
	}
}

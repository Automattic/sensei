<?php
/**
 * File containing the Sensei_Course_Completed_Actions_Block class.
 *
 * @package sensei
 * @since 3.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Completed_Actions_Block
 */
class Sensei_Course_Completed_Actions_Block {

	/**
	 * Sensei_Course_Completed_Actions_Block constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Register the block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-completed-actions',
			[
				'render_callback' => [ $this, 'render_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-completed-actions' )
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string Block HTML.
	 */
	public function render_block( $attributes, $content ): string {
		$dom = new DomDocument();
		$dom->loadHTML( $content );
		$parent_div = $dom->getElementsByTagName( 'div' )->length > 0 ? $dom->getElementsByTagName( 'div' )[0] : '';
		$anchor     = $parent_div && $parent_div->getElementsByTagName( 'a' )->length > 0 ? $parent_div->getElementsByTagName( 'a' )[0] : '';

		// Open the course archive page when the button is clicked.
		if ( $anchor ) {
			$course_archive_page_url = Sensei_Course::get_courses_page_url();

			if ( $course_archive_page_url ) {
				$anchor->setAttribute( 'href', $course_archive_page_url );
				$content = $dom->saveHTML( $parent_div );
			} else {
				return '';
			}
		}

		return $content;
	}
}

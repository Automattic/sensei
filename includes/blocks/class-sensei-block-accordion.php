<?php
/**
 * File containing the Sensei_Block_Take_Course class.
 *
 * @package sensei
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for Take course button.
 */
class Sensei_Block_Accordion {

	/**
	 * Sensei_Block_Take_Course constructor.
	 */
	public function __construct() {
		$this->register_block();
	}


	/**
	 * Register progress bar block.
	 *
	 * @access private
	 */
	public function register_block() {

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/accordion',
			[ 'render_callback' => [ $this, 'render' ] ],
			Sensei()->assets->src_path( 'blocks/accordion' )
		);


		Sensei_Blocks::register_sensei_block(
			'sensei-lms/accordion-section',
			[ 'render_callback' => [ $this, 'render_details' ] ],
			Sensei()->assets->src_path( 'blocks/accordion/section' )
		);


		Sensei_Blocks::register_sensei_block(
			'sensei-lms/accordion-content',
			[ 'render_callback' => [ $this, 'render_content' ] ],
			Sensei()->assets->src_path( 'blocks/accordion/content' )
		);


		Sensei_Blocks::register_sensei_block(
			'sensei-lms/accordion-summary',
			[ 'render_callback' => [ $this, 'render_summary' ] ],
			Sensei()->assets->src_path( 'blocks/accordion/summary' )
		);

	}

	/**
	 * Render the take course button. Wraps block HTML within a form.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string Form to start the course, with the block content as the submit button.
	 */
	public function render( $attributes, $content): string {

		$wrapper_attributes = get_block_wrapper_attributes();
		return "<div $wrapper_attributes> $content</div>";
	}

	public function render_details( $attributes, $content): string {

		$wrapper_attributes = get_block_wrapper_attributes();
		return "<details $wrapper_attributes> $content </details>";
	}

	public function render_content( $attributes, $content): string {

		$wrapper_attributes = get_block_wrapper_attributes();
		return "<div $wrapper_attributes> $content </div>";
	}

	public function render_summary( $attributes): string {

		// return '';
		$summary = $attributes['summary'];
		$wrapper_attributes = get_block_wrapper_attributes();

		return "<summary $wrapper_attributes> $summary </summary>";
	}


}

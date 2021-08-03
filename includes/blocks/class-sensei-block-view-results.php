<?php
/**
 * File containing the Sensei_Block_View_Results class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for View Results button.
 */
class Sensei_Block_View_Results {

	/**
	 * Sensei_Block_View_Results constructor.
	 */
	public function __construct() {
		$this->register_block();
	}

	/**
	 * Register View Results button block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/button-view-results',
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Render the View Results button.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string The HTML of the block.
	 */
	public function render( $attributes, $content ): string {
		if ( ! Sensei()->course::is_user_enrolled( get_the_ID() ) ) {
			return '';
		}

		return preg_replace(
			'/<a(.*)>/',
			'<a href="' . esc_url( Sensei_Course::get_view_results_link( get_the_ID() ) ) . '" $1>',
			$content,
			1
		);
	}
}

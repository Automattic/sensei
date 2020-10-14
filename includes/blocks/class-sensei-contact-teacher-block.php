<?php
/**
 * File containing the Sensei_Contact_Teacher_Block class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for Contact teacher button.
 */
class Sensei_Contact_Teacher_Block {

	/**
	 * Sensei_Contact_Teacher_Block constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
	}


	/**
	 * Register progress bar block.
	 *
	 * @access private
	 */
	public function register_block() {
		register_block_type(
			'sensei-lms/button-contact-teacher',
			[
				'render_callback' => [ $this, 'render_contact_teacher_block' ],
			]
		);
	}

	/**
	 * Render the contact teacher block, adding dynamic attributes to the existing editor-generated HTML content.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string The HTML of the block.
	 */
	public function render_contact_teacher_block( $attributes, $content ): string {
		global $post;
		$href = add_query_arg( array( 'contact' => $post->post_type ) );

		return wp_kses_post( $this->add_href_attribute( $content, $href ) );
	}

	/**
	 * Add href attribute to the block's <a> tag.
	 *
	 * @param string $content Block HTML.
	 * @param string $href    Link URL.
	 *
	 * @return string Block HTML with additional href attribute.
	 */
	private function add_href_attribute( $content, $href ) {
		return preg_replace( '/<a/', '<a href="' . esc_url( $href ) . '" ', $content, 1 );
	}
}

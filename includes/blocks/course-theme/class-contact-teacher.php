<?php
/**
 * File containing the Contact_Teacher class.
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
 * Class Contact_Teacher is responsible for rendering the 'Contact Teacher' block.
 */
class Contact_Teacher {
	/**
	 * Contact_Teacher constructor.
	 */
	public function __construct() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-theme-contact-teacher',
			[
				'render_callback' => [ $this, 'render' ],
			]
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
		$nonce      = wp_create_nonce( \Sensei_Messages::NONCE_ACTION_NAME );
		$nonce_name = \Sensei_Messages::NONCE_FIELD_NAME;
		$text       = __( 'Contact Teacher', 'sensei-lms' );
		$post_id    = get_post()->ID ?? 0;
		return ( "
			<button
				class='sensei-course-theme-contact-teacher__button'
				data-nonce-name='{$nonce_name}'
				data-nonce-value='{$nonce}'
				data-post-id='{$post_id}'
			>
				{$text}
			</button>
		" );
	}
}

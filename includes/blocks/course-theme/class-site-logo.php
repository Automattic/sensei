<?php
/**
 * File containing the Site_Logo class.
 *
 * @package sensei
 * @since
 */

namespace Sensei\Blocks\Course_Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the site logo, linking to the course page.
 */
class Site_Logo {

	/**
	 * Site_Logo constructor.
	 */
	public function __construct() {
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'core/site-logo' ) ) {
			register_block_type(
				'core/site-logo',
				[
					'render_callback' => [ $this, 'render_site_logo' ],
					'style'           => 'sensei-theme-blocks',
				]
			);
		}
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
	public function render_site_logo( array $attributes ): string {

		return '<div class="wp-block-site-logo">' . get_custom_logo() . '</div>';
	}
}

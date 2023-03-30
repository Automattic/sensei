<?php
/**
 * File containing the Email_Template_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use WP_Block_Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email_Page_Template_Repository class.
 *
 * @since 4.12.0
 */
class Email_Page_Template_Repository {

	/**
	 * Get the page template stored on the database when the user saves a customizations
	 *
	 * @internal
	 *
	 * @param string $identifier  Email Page Template identifier.
	 *
	 * @return WP_Block_Template | null
	 */
	public function get( string $identifier ) {

		list(, $slug) = explode( '//', $identifier, 2 );

		$wp_query_args = array(
			'post_name__in'  => array( $slug ),
			'post_type'      => 'wp_template',
			'post_status'    => array( 'auto-draft', 'draft', 'publish', 'trash' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		);

		$query = new \WP_Query( $wp_query_args );

		if ( $query->post_count > 0 ) {
			return $this->build_from_post( $query->posts[0], $identifier );
		}

		return null;
	}

	/**
	 * Get the page template from the file
	 *
	 * @internal
	 *
	 * @param string $path  File Path.
	 * @param string $identifier  Email Page Template identifier.
	 *
	 * @return WP_Block_Template | null
	 */
	public function get_from_file( $path, $identifier ) {
		$path = \Sensei_Templates::locate_template( $path );

		if ( empty( $path ) ) {
			return null;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		ob_start();
		require $path;
		$content = ob_get_clean();
		return $this->build_from_content( $content, $identifier );
	}

	/**
	 * Convert a post to a WP_Block_Template
	 *
	 * @internal
	 *
	 * @param \WP_Post $post  Email template post.
	 * @param string   $identifier  Email Page Template identifier.
	 *
	 * @return WP_Block_Template | null
	 */
	private function build_from_post( \WP_Post $post, string $identifier ) {
		list( $theme, ) = explode( '//', $identifier, 2 );

		$template                 = new WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = $identifier;
		$template->theme          = $theme;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->origin         = 'theme';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file = true;
		$template->is_custom      = true;
		$template->author         = $post->post_author;

		return $template;
	}

	/**
	 * Convert a content string to a WP_Block_Template
	 *
	 * @internal
	 *
	 * @param string $content  Email Content.
	 * @param string $identifier  Email Page Template identifier.
	 *
	 * @return WP_Block_Template | null
	 */
	private function build_from_content( string $content, string $identifier ) {
		list($theme, $slug ) = explode( '//', $identifier, 2 );

		$template                 = new WP_Block_Template();
		$template->wp_id          = null;
		$template->id             = $identifier;
		$template->origin         = null;
		$template->source         = 'theme';
		$template->title          = __( 'Sensei Email', 'sensei-lms' );
		$template->slug           = $slug;
		$template->status         = 'publish';
		$template->theme          = $theme;
		$template->type           = 'wp_template';
		$template->description    = __( 'Displays a Sensei email.', 'sensei-lms' );
		$template->content        = $content;
		$template->author         = 0;
		$template->is_custom      = true;
		$template->has_theme_file = true;

		return $template;
	}
}


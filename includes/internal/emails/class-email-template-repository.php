<?php
/**
 * File containing the Email_Template_Repository class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email_Template_Repository class.
 *
 * @since $$next-version$$
 */
class Email_Template_Repository {

	private const CONTENT  =  '<!-- wp:group {"tagName":"main"} -->
		<main class="wp-block-group"><!-- wp:group {"layout":{"inherit":true}} -->
		<div class="wp-block-group"><!-- wp:post-title {"level":1,"align":"wide","style":{"spacing":{"margin":{"bottom":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dmedium, 6rem)"}}}} /-->

		<!-- wp:post-featured-image {"align":"wide","style":{"spacing":{"margin":{"bottom":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dmedium, 6rem)"}}}} /-->

		<!-- wp:separator {"align":"wide","className":"is-style-wide"} -->
		<hr class="wp-block-separator alignwide is-style-wide"/>
		<!-- /wp:separator --></div>
		<!-- /wp:group -->

		<!-- wp:spacer {"height":32} -->
		<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:post-content {"layout":{"inherit":true}} /-->

		<!-- wp:spacer {"height":32} -->
		<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:group {"layout":{"inherit":true}} -->
		<div class="wp-block-group"><!-- wp:group {"layout":{"type":"flex"}} -->
		<div class="wp-block-group"><!-- wp:post-date {"format":"F j, Y","style":{"typography":{"fontStyle":"italic","fontWeight":"400"}},"fontSize":"small"} /-->

		<!-- wp:post-author {"showAvatar":false,"fontSize":"small"} /-->

		<!-- wp:post-terms {"term":"category","fontSize":"small"} /-->

		<!-- wp:post-terms {"term":"post_tag","fontSize":"small"} /--></div>
		<!-- /wp:group -->

		<!-- wp:spacer {"height":32} -->
		<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:post-comments /--></div>
		<!-- /wp:group --></main>
		<!-- /wp:group -->
	';

	private const META_IDENTIFIER = '_sensei_email_template_identifier';
	private const DEFAULT_TEMPLATE_IDENTIFIER = 'default_sensei_email_template';
	private const DEFAULT_TEMPLATE_NAME = 'single-sensei_email';
	/**
	 * Create the default email page template;
	 *
	 * @internal
	 *
	 * @return int|null Email post ID. Returns false if email already exists. Returns WP_Error on failure.
	 */
	public function create() {
		$exist = $this->get();
		if($exist) {
			return $this->get();
		}

		$email_data = [
			'post_status'  => 'publish',
			'post_type'    => 'wp_template',
			'post_title'   => __('E-mail template', 'sensei-lms'),
			'post_excerpt' => __('Displays a single item: Email', 'sensei-lms'),
			'post_name'    => $this->get_default_template_name(),
			'post_status'  => 'publish',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'menu_order' => 0,
			'post_content' => self::CONTENT,
			'meta_input'   => [
				self::META_IDENTIFIER  => self::DEFAULT_TEMPLATE_IDENTIFIER,
			],
		];
		return wp_insert_post( $email_data );
	}

	/**
	 * Delete all email page template.
	 *
	 * @internal
	 *
	 * @param string $identifier  .Email Template identifier.
	 * @param array  $title       Email Template title.
	 * @param string $description Email Template Template description.
	 * @param string $content     Email content.
	 *
	 * @return \WP_Block_Template | null
	 */
	public function delete_all() {
		$query = new WP_Query(
			[
				'select'     => 'ID',
				'post_type'  => 'wp_template',
				'meta_key'   => self::META_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => self::DEFAULT_TEMPLATE_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( 0 === $query->post_count ) {
			return null;
		}

		$result = true;
		foreach ( $query->posts as $post ) {
			$last_result = wp_delete_post( $post->ID, true );
			if ( ! $last_result ) {
				$result = false;
			}
		}

		return $result;
	}


	public function get_default_template_name() {
		return self::DEFAULT_TEMPLATE_NAME;
	}

	/**
	 * Get the default email page template.
	 *
	 * @internal
	 *
	 * @param string $identifier  .Email Template identifier.
	 * @param array  $title       Email Template title.
	 * @param string $description Email Template Template description.
	 * @param string $content     Email content.
	 *
	 * @return \WP_Block_Template | null
	 */
	public function get() {
		$query = new WP_Query(
			[
				'post_type'  => 'wp_template',
				'meta_key'   => self::META_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => self::DEFAULT_TEMPLATE_IDENTIFIER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);

		if ( 0 === $query->post_count ) {
			return null;
		}
		$post 					  =  $query->posts[0];

		$template                 = new \WP_Block_Template();
		$template->wp_id          = $post->ID;
		$template->id             = 'sensei-email' . '//' . $post->post_name;
		$template->theme          =  null;
		$template->content        = $post->post_content;
		$template->slug           = $post->post_name;
		$template->source         = 'custom';
		$template->origin         = 'plugin';
		$template->type           = $post->post_type;
		$template->description    = $post->post_excerpt;
		$template->title          = $post->post_title;
		$template->status         = $post->post_status;
		$template->has_theme_file  = false;
		$template->is_custom      = true;
		$template->author         = $post->post_author;

		return $template;
	}
}


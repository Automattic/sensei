<?php
/**
 * File containing the Email_List_Table_Actions class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class responsible for handling the email list table actions.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_List_Table_Actions {
	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_action( 'post_action_enable-email', [ $this, 'enable_email' ] );
		add_action( 'post_action_disable-email', [ $this, 'disable_email' ] );
	}

	/**
	 * Enable the email by publishing the post.
	 *
	 * @internal
	 *
	 * @param int $post_id The post ID.
	 */
	public function enable_email( $post_id ): void {
		check_admin_referer( 'enable-email-post_' . $post_id );

		wp_publish_post( $post_id );

		$this->redirect_back();
	}

	/**
	 * Disable the email by changing the post status to "draft".
	 *
	 * @internal
	 *
	 * @param int $post_id The post ID.
	 */
	public function disable_email( $post_id ): void {
		check_admin_referer( 'disable-email-post_' . $post_id );

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$this->redirect_back();
	}

	/**
	 * Redirect the user back to where it came from.
	 */
	private function redirect_back(): void {
		wp_safe_redirect( wp_get_referer() );
		exit;
	}
}

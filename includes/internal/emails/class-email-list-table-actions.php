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
		add_action( 'admin_action_bulk-disable-email', [ $this, 'bulk_disable_emails' ] );
		add_action( 'admin_action_bulk-enable-email', [ $this, 'bulk_enable_emails' ] );
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
		$this->validate_request( $post_id );

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
		$this->validate_request( $post_id );

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$this->redirect_back();
	}

	/**
	 * Ensures the request is valid and the user has permission.
	 * If the request is not valid, the method will exit with a message.
	 *
	 * @param int $post_id The post ID.
	 */
	private function validate_request( $post_id ): void {
		if ( ! current_user_can( 'manage_sensei' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		if ( Email_Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
		}
	}

	/**
	 * Redirect the user back to where it came from.
	 * If the action link was accessed directly, redirect to the emails list.
	 */
	private function redirect_back(): void {
		$referer  = wp_get_referer();
		$location = $referer
			? $referer
			: admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' );

		wp_safe_redirect( $location );
		exit;
	}
}

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
 * @since 4.12.0
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
		$this->validate_post_action_request( $post_id );

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
		$this->validate_post_action_request( $post_id );

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$this->redirect_back();
	}

	/**
	 * Bulk enable the emails.
	 *
	 * @access private
	 * @internal
	 */
	public function bulk_enable_emails(): void {
		$this->validate_bulk_action_request();

		foreach ( $this->get_email_ids_from_request() as $email_id ) {
			wp_publish_post( $email_id );
		}

		$this->redirect_back();
	}

	/**
	 * Bulk disable the emails.
	 *
	 * @internal
	 */
	public function bulk_disable_emails(): void {
		$this->validate_bulk_action_request();

		foreach ( $this->get_email_ids_from_request() as $email_id ) {
			wp_update_post(
				[
					'ID'          => $email_id,
					'post_status' => 'draft',
				]
			);
		}
		$this->redirect_back();
	}

	/**
	 * Ensures the request is valid and the user has permission.
	 * If the request is not valid, the method will exit with a message.
	 * Return the list of valid email IDs that passes the checks.
	 *
	 * @param int $post_id The post ID.
	 */
	private function validate_post_action_request( $post_id ): void {
		if ( ! current_user_can( 'manage_sensei' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		if ( Email_Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
		}
	}

	/**
	 * Ensures the bulk request is valid and the user has permission.
	 * If the request is not valid, the method will exit with a message.
	 */
	private function validate_bulk_action_request() {
		if ( ! current_user_can( 'manage_sensei' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		check_admin_referer( 'sensei_email_bulk_action' );

		$email_ids = $this->get_email_ids_from_request();

		if ( empty( $email_ids ) ) {
			wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
		}

		$args = [
			'fields'         => 'ids',
			'post__in'       => $email_ids,
			'post_type'      => Email_Post_Type::POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => [ 'draft', 'publish' ],
		];

		$existing_email_ids = get_posts( $args );

		if ( count( $existing_email_ids ) !== count( $email_ids ) ) {
			wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
		}
	}

	/**
	 * Get the email IDs from the request.
	 *
	 * @return int[] The email IDs.
	 */
	private function get_email_ids_from_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked in `validate_bulk_action_request`.
		return array_map( 'intval', $_REQUEST['email'] ?? [] );
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

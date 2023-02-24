<?php
/**
 * File containing the Email_Preview class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class responsible for the email preview.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Preview {
	/**
	 * The email sender instance.
	 *
	 * @var Email_Sender
	 */
	private $email_sender;

	/**
	 * Class constructor.
	 *
	 * @internal
	 *
	 * @param Email_Sender $email_sender The email sender instance.
	 */
	public function __construct( Email_Sender $email_sender ) {
		$this->email_sender = $email_sender;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_action( 'template_redirect', [ $this, 'render_preview' ] );
	}

	/**
	 * Render the preview page or email.
	 *
	 * @internal
	 */
	public function render_preview(): void {
		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce validated at a later point.
		if ( empty( $_GET['sensei_email_preview_id'] ) ) {
			return;
		}

		$this->validate_request();

		// phpcs:disable WordPress.Security.NonceVerification -- Nonce already validated.
		if ( ! empty( $_GET['render_email'] ) ) {
			$this->render_email();
		} else {
			$this->render_page();
		}

		exit;
	}

	/**
	 * Render the preview page.
	 */
	private function render_page(): void {
		$post         = $this->get_email_post();
		$subject      = $this->email_sender->replace_placeholders(
			get_the_title( $post ),
			$this->get_placeholders()
		);
		$from_address = Sensei()->emails->get_from_address();
		$from_name    = Sensei()->emails->get_from_name();
		$avatar       = get_avatar( $from_address, 40, '', '', [ 'force_display' => true ] );

		require __DIR__ . '/views/preview.php';
	}

	/**
	 * Render the email body.
	 */
	private function render_email(): void {
		$this->validate_request();

		// TODO: Remove the error control operator when the warnings are fixed.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$email_body = @$this->email_sender->get_email_body( $this->get_email_post() );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->email_sender->replace_placeholders( $email_body, $this->get_placeholders() );
		exit;
	}

	/**
	 * Get the email post.
	 *
	 * @return WP_Post|null
	 */
	private function get_email_post(): ?WP_Post {
		// phpcs:ignore WordPress.Security.NonceVerification -- Nonce validated at a later point.
		$post_id = isset( $_GET['sensei_email_preview_id'] ) ? (int) $_GET['sensei_email_preview_id'] : 0;

		return get_post( $post_id );
	}

	/**
	 * Get the placeholders to be replaced.
	 *
	 * @return string[]
	 */
	private function get_placeholders(): array {
		// TODO: Add all placeholders.
		return [
			'course.name'         => __( 'Learn Photography', 'sensei-lms' ),
			'student:displayname' => __( 'James S', 'sensei-lms' ),
		];
	}

	/**
	 * Validate the request.
	 */
	private function validate_request(): void {
		$post = $this->get_email_post();

		if ( ! $post || ! Email_Post_Type::POST_TYPE === $post->post_type ) {
			wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
		}

		if ( ! current_user_can( 'manage_sensei' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		check_admin_referer( 'preview-email-post_' . $post->ID );
	}
}

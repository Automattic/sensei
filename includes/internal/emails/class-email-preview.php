<?php
/**
 * File containing the Email_Preview class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

use Sensei_Assets;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class responsible for the email preview.
 *
 * @internal
 *
 * @since 4.12.0
 */
class Email_Preview {
	/**
	 * The email sender instance.
	 *
	 * @var Email_Sender
	 */
	private $email_sender;

	/**
	 * The assets instance.
	 *
	 * @var Sensei_Assets
	 */
	private $assets;

	/**
	 * Class constructor.
	 *
	 * @internal
	 *
	 * @param Email_Sender  $email_sender The email sender instance.
	 * @param Sensei_Assets $assets The assets instance.
	 */
	public function __construct( Email_Sender $email_sender, Sensei_Assets $assets ) {
		$this->email_sender = $email_sender;
		$this->assets       = $assets;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_action( 'template_redirect', [ $this, 'render_preview' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_scripts' ] );
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

		// Prevent the current request from loading the theme.
		add_filter( 'wp_using_themes', '__return_false' );

		// phpcs:disable WordPress.Security.NonceVerification -- Nonce already validated.
		if ( ! empty( $_GET['render_email'] ) ) {
			$this->render_email();
		} else {
			$this->render_page();
		}
	}

	/**
	 * Register and enqueue scripts and styles that are needed in the backend.
	 *
	 * @internal
	 */
	public function register_admin_scripts(): void {
		$screen = get_current_screen();
		if ( ! $screen || Email_Post_Type::POST_TYPE !== $screen->id ) {
			return;
		}

		$this->assets->enqueue( 'sensei-email-preview-button', 'admin/emails/email-preview-button/index.js', [], true );
		$this->assets->enqueue( 'sensei-email-preview-button', 'admin/emails/email-preview-button/email-preview-button.css' );

		wp_localize_script(
			'sensei-email-preview-button',
			'sensei_email_preview',
			[
				'link' => self::get_preview_link( get_the_ID() ),
			]
		);
	}

	/**
	 * Get the preview link.
	 *
	 * @internal
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string
	 */
	public static function get_preview_link( int $post_id ): string {
		return wp_nonce_url(
			get_home_url() . "?sensei_email_preview_id=$post_id",
			'preview-email-post_' . $post_id
		);
	}

	/**
	 * Render the preview page.
	 */
	private function render_page(): void {
		$subject      = $this->email_sender->get_email_subject( $this->get_email_post_for_preview(), $this->get_placeholders() );
		$from_address = Sensei()->emails->get_from_address();
		$from_name    = Sensei()->emails->get_from_name();
		$avatar       = get_avatar( $from_address, 40, '', '', [ 'force_display' => true ] );

		require __DIR__ . '/views/preview.php';
	}

	/**
	 * Render the email body.
	 */
	private function render_email(): void {
		// TODO: Remove the error control operator when the warnings are fixed.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$email_body = @$this->email_sender->get_email_body( $this->get_email_post_for_preview(), $this->get_placeholders() );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $email_body;
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
	 * Get the email post that should be displayed for preview.
	 * This might be the latest post revision.
	 *
	 * @return WP_Post|null
	 */
	private function get_email_post_for_preview(): ?WP_Post {
		$post = $this->get_email_post();
		if ( ! $post ) {
			return null;
		}

		$autosave = wp_get_post_autosave( $post->ID );

		return $autosave ? $autosave : $post;
	}

	/**
	 * Get the placeholders to be replaced.
	 *
	 * @return string[]
	 */
	private function get_placeholders(): array {
		return [
			'author:displayname'     => __( 'Pedro T.', 'sensei-lms' ),
			'certificate:url'        => '#',
			'completed:url'          => '#',
			'course:name'            => __( 'Photography Class', 'sensei-lms' ),
			'course:url'             => '#',
			'date:dtext'             => __( 'today', 'sensei-lms' ),
			'editcourse:url'         => '#',
			'grade:percentage'       => '89%',
			'grade:quiz'             => '#',
			'grade:validation'       => __( 'You Passed!', 'sensei-lms' ),
			'lesson:name'            => __( 'Learning about macro', 'sensei-lms' ),
			'lesson:url'             => '#',
			'manage:course'          => '#',
			'manage:students'        => '#',
			'message:displaymessage' => __( 'Hello! Can I ask a question?', 'sensei-lms' ),
			'quiz:url'               => '#',
			'reply:url'              => '#',
			'results:url'            => '#',
			'resume:url'             => '#',
			'student:displayname'    => __( 'James S.', 'sensei-lms' ),
			'subject:displaysubject' => __( 'Hello from your new student', 'sensei-lms' ),
			'teacher:displayname'    => __( 'Pedro T.', 'sensei-lms' ),
		];
	}

	/**
	 * Validate the request.
	 */
	private function validate_request(): void {
		$post = $this->get_email_post();

		if ( ! $post || Email_Post_Type::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'Invalid request', 'sensei-lms' ) );
		}

		if ( ! current_user_can( 'manage_sensei' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'sensei-lms' ) );
		}

		check_admin_referer( 'preview-email-post_' . $post->ID );
	}
}

<?php
/**
 * File containing the Sensei_Block_Contact_Teacher class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for Contact teacher button.
 */
class Sensei_Block_Contact_Teacher {

	/**
	 * Sensei_Block_Contact_Teacher constructor.
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
		Sensei_Blocks::register_sensei_block(
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

		if ( ! empty( Sensei()->settings->settings['messages_disable'] ) || ! is_user_logged_in() ) {
			return '';
		}

		$contact_form_link = add_query_arg( array( 'contact' => $post->post_type ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		$contact_form_open = isset( $_GET['contact'] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		$message_sent = ( isset( $_GET['send'] ) && 'complete' === $_GET['send'] );
		$notice       = $message_sent ? $this->confirmation_notice() : '';

		$contact_form = $this->teacher_contact_form( $post );

		return '<div id="private_message" class="sensei-block-wrapper sensei-collapsible">
				' . ( $this->add_button_attributes( $content, $contact_form_link, $attributes ) ) . '
				' . $notice . '
				<div class="sensei-collapsible__content ' . ( $contact_form_open ? '' : 'collapsed' ) . '">' . $contact_form . '</div>
			</div>';
	}

	/**
	 * Render a notice confirming the message was sent.
	 */
	private function confirmation_notice() {
		$confirmation_message = __( 'Your private message has been sent.', 'sensei-lms' );
		return '<div class="sensei-message tick">' . esc_html( $confirmation_message ) . '</div>';
	}

	/**
	 * Render contact teacher form.
	 *
	 * @param WP_Post $post The current post.
	 *
	 * @return string
	 */
	private function teacher_contact_form( $post ) {

		$nonce = wp_nonce_field( 'message_teacher', 'sensei_message_teacher_nonce', true, false );

		return '
			<form name="contact-teacher" action="" method="post" class="sensei-contact-teacher-form">
				<label>' . esc_html__( 'Send Private Message', 'sensei-lms' ) . '</label>
				<textarea name="contact_message" required placeholder="' . esc_attr__( 'Enter your private message.', 'sensei-lms' ) . '"></textarea>

				<input type="hidden" name="post_id" value="' . esc_attr( absint( $post->ID ) ) . '" />
				' . $nonce . '
				<p class="sensei-contact-teacher-form__actions">
				<button class="sensei-contact-teacher-form__submit">' . esc_html__( 'Send Message', 'sensei-lms' ) . '</button>
				</p>
			</form>';
	}

	/**
	 * Add attributes to the block's <div> and <a> tags.
	 *
	 * @param string $content    Block HTML.
	 * @param string $href       Link URL.
	 * @param array  $attributes Block attributes.
	 *
	 * @return string Block HTML with additional href attribute.
	 */
	private function add_button_attributes( $content, $href, $attributes = [] ) {
		// Add <div> class names.
		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $attributes, [], 'outline' );
		$content    = preg_replace(
			'/<div(.*?)class="(.*?)"(.*?)>/',
			'<div class="$2 ' . esc_attr( $class_name ) . '" $1 $3>',
			$content,
			1
		);

		// Add <a> attributes.
		return preg_replace(
			'/<a(.*)class="(.*)"(.*)>(.+)<\/a>/',
			'<a href="' . esc_url( $href ) . '#private_message" class="sensei-collapsible__toggle $2" $1 $3>$4</a>',
			$content,
			1
		);
	}
}

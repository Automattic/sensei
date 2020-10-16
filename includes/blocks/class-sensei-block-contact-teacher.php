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
		register_block_type(
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
		$message_sent = ( isset( $_GET['send'] ) && 'complete' === $_GET['send'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		$contact_form_open = isset( $_GET['contact'] ) || $message_sent;

		$contact_form = $this->teacher_contact_form( $post, $message_sent );

		return '<div id="private_message" class="sensei-collapsible">
				' . ( $this->add_button_attributes( $content, $contact_form_link ) ) . '
				<div class="sensei-collapsible__content ' . ( $contact_form_open ? '' : 'collapsed' ) . '">' . $contact_form . '</div>
			</div>';
	}

	/**
	 * Render contact teacher form.
	 *
	 * @param WP_Post $post         The current post.
	 * @param bool    $message_sent Display message sent feedback.
	 *
	 * @return string
	 */
	private function teacher_contact_form( $post, $message_sent ) {

		$confirmation = '';
		if ( $message_sent ) {

			$confirmation_message = __( 'Your private message has been sent.', 'sensei-lms' );
			$confirmation         = '<div class="sensei-message tick">' . esc_html( $confirmation_message ) . '</div>';
		}
		$nonce = wp_nonce_field( 'message_teacher', 'sensei_message_teacher_nonce', true, false );

		return '
			' . $confirmation . '
			<form name="contact-teacher" action="" method="post" class="sensei-contact-teacher-form">
				<label>' . esc_html__( 'Send Private Message', 'sensei-lms' ) . '</label>
				<textarea name="contact_message" placeholder="' . esc_attr__( 'Enter your private message.', 'sensei-lms' ) . '"></textarea>
				
				<input type="hidden" name="post_id" value="' . esc_attr( absint( $post->ID ) ) . '" />
				' . $nonce . '
				<p class="sensei-contact-teacher-form__actions">
				<button class="sensei-contact-teacher-form__submit">' . esc_attr__( 'Send Message', 'sensei-lms' ) . '</button>
				</p>
			</form>';
	}

	/**
	 * Add attributes to the block's <a> tag.
	 *
	 * @param string $content Block HTML.
	 * @param string $href    Link URL.
	 *
	 * @return string Block HTML with additional href attribute.
	 */
	private function add_button_attributes( $content, $href ) {
		return preg_replace(
			'/<a(.*)class="(.*)"(.*)>(.+)<\/a>/',
			'<a href="' . esc_url( $href ) . '#private_message" class="sensei-collapsible__toggle $2" $1 $3>$4</a>',
			$content,
			1
		);
	}
}

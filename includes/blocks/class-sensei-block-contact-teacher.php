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
		$this->register_block();
		$this->add_notices();
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
	 * Check if a notice should be displayed.
	 * Currently, it's also handling notices for legacy lessons (using templates).
	 *
	 * @access private
	 */
	public function add_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		if ( isset( $_GET['send'] ) && 'complete' === $_GET['send'] ) {
			Sensei()->notices->add_notice( __( 'Your private message has been sent.', 'sensei-lms' ), 'tick', 'sensei-contact-teacher-confirm' );
		}
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

		if ( ! $post
			|| ! empty( Sensei()->settings->settings['messages_disable'] )
			|| ! is_user_logged_in()
		) {
			return '';
		}

		if ( ! $content ) {
			$content = '<a class="sensei-course-theme-contact-teacher__button">' . esc_html__( 'Contact Teacher', 'sensei-lms' ) . '</a>';
		}

		$contact_form_link = add_query_arg( array( 'contact' => $post->post_type ) );
		$post_link         = remove_query_arg( 'contact' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		$contact_form_open = isset( $_GET['contact'] ) && ! ( isset( $_GET['send'] ) && 'complete' === $_GET['send'] );

		$contact_form = $this->teacher_contact_form( $post );
		$text_close   = esc_html__( 'Close', 'sensei-lms' );

		return '<div id="private_message" class="sensei-block-wrapper sensei-contact-teacher-wrapper sensei-collapsible" data-sensei-modal ' . ( $contact_form_open ? 'data-sensei-modal-is-open' : '' ) . '>
				' . ( $this->add_button_attributes( $content, $contact_form_link ) ) . '
				<a href="' . esc_url( $post_link ) . '" data-sensei-modal-overlay aria-label="' . $text_close . '"></a>
				<div data-sensei-modal-content class="sensei-course-theme__frame">
					' . $contact_form . '
					<a class="sensei-contact-teacher-close" href="' . esc_url( $post_link ) . '" data-sensei-modal-close title="' . $text_close . '">
						' . \Sensei()->assets->get_icon( 'close' ) . '
					</a>
				</div>
			</div>';
	}

	/**
	 * Render contact teacher form.
	 *
	 * @param WP_Post $post The current post.
	 *
	 * @return string
	 */
	private function teacher_contact_form( $post ) {

		$nonce         = wp_nonce_field( \Sensei_Messages::NONCE_ACTION_NAME, \Sensei_Messages::NONCE_FIELD_NAME, true, false );
		$wp_rest_nonce = wp_nonce_field( 'wp_rest', '_wpnonce', true, false );

		return '
			<form name="contact-teacher" action="" method="post" class="sensei-contact-teacher-form" onsubmit="sensei.submitContactTeacher(event)">
				<label>' . esc_html__( 'Contact your teacher', 'sensei-lms' ) . '</label>
				<textarea rows="5" name="contact_message" required placeholder="' . esc_attr__( 'Enter your message', 'sensei-lms' ) . '"></textarea>

				<input type="hidden" name="post_id" value="' . esc_attr( absint( $post->ID ) ) . '" />
				' . $nonce . '
				' . $wp_rest_nonce . '
				<p class="sensei-contact-teacher-form__actions">
				<button class="sensei-contact-teacher-form__submit">' . esc_html__( 'Send Message', 'sensei-lms' ) . '</button>
				</p>
				<div class="sensei-contact-teacher-success">
					' . Sensei()->assets->get_icon( 'check-circle' ) . '
					<p>' . __( 'Your message has been sent', 'sensei-lms' ) . '</p>
				</div>
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
			'<a href="' . esc_url( $href ) . '#private_message" class="sensei-contact-teacher-open $2" data-sensei-modal-open $1 $3>$4</a>',
			$content,
			1
		);
	}
}

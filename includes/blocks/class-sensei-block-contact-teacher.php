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

		$contact_form_link = add_query_arg( array( 'contact' => $post->post_type ) );
		$post_link         = remove_query_arg( 'contact' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Arguments used for comparison.
		$contact_form_open = isset( $_GET['contact'] ) && ! ( isset( $_GET['send'] ) && 'complete' === $_GET['send'] );

		$uuid         = wp_generate_uuid4();
		$contact_form = $this->teacher_contact_form( $post );

		return '<div id="private_message" class="sensei-block-wrapper sensei-collapsible">
				' . ( $this->add_button_attributes( $content, $contact_form_link, $uuid ) ) . '
				<a href="' . $post_link . '" data-sensei-modal-overlay="' . $uuid . '" ' . ( $contact_form_open ? 'data-sensei-modal-overlay-is-open' : '' ) . '></a>
				<div data-sensei-modal-content="' . $uuid . '" ' . ( $contact_form_open ? 'data-sensei-modal-content-is-open' : '' ) . '>
					' . $contact_form . '
					<div class="sensei-contact-teacher-success">
						<svg><use xlink:href="#sensei-contact-teacher-success"></use></svg>
						<p>' . __( 'Your message has been sent', 'sensei-lms' ) . '</p>
					</div>
					<a class="sensei-contact-teacher-close" href="' . $post_link . '" data-sensei-modal-close="' . $uuid . '">
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

		$nonce = wp_nonce_field( \Sensei_Messages::NONCE_ACTION_NAME, \Sensei_Messages::NONCE_FIELD_NAME, true, false );

		return '
			<form name="contact-teacher" action="" method="post" class="sensei-contact-teacher-form">
				<label>' . esc_html__( 'Contact your teacher', 'sensei-lms' ) . '</label>
				<textarea rows="5" name="contact_message" required placeholder="' . esc_attr__( 'Enter your message', 'sensei-lms' ) . '"></textarea>

				<input type="hidden" name="post_id" value="' . esc_attr( absint( $post->ID ) ) . '" />
				' . $nonce . '
				<p class="sensei-contact-teacher-form__actions">
				<button class="sensei-contact-teacher-form__submit">' . esc_html__( 'Send Message', 'sensei-lms' ) . '</button>
				</p>
			</form>';
	}

	/**
	 * Add attributes to the block's <a> tag.
	 *
	 * @param string $content Block HTML.
	 * @param string $href    Link URL.
	 * @param string $uuid    The unique identifier for this block.
	 *
	 * @return string Block HTML with additional href attribute.
	 */
	private function add_button_attributes( $content, $href, $uuid ) {
		return preg_replace(
			'/<a(.*)class="(.*)"(.*)>(.+)<\/a>/',
			'<a href="' . esc_url( $href ) . '#private_message" class="sensei-contact-teacher-open $2" data-sensei-modal-open="' . $uuid . '" $1 $3>$4</a>',
			$content,
			1
		);
	}
}

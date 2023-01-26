<?php
/**
 * File containing the Sensei_Block_Take_Course class.
 *
 * @package sensei
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block for Take course button.
 */
class Sensei_Block_Take_Course {

	/**
	 * Sensei_Block_Take_Course constructor.
	 */
	public function __construct() {
		$this->register_block();
	}


	/**
	 * Register progress bar block.
	 *
	 * @access private
	 */
	public function register_block() {
		Sensei_Blocks::register_sensei_block( 'sensei-lms/button-take-course', [ 'render_callback' => [ $this, 'render_take_course_block' ] ] );
	}

	/**
	 * Render the take course button. Wraps block HTML within a form.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block HTML.
	 *
	 * @return string Form to start the course, with the block content as the submit button.
	 */
	public function render_take_course_block( $attributes, $content ): string {
		global $post;

		if ( ! $post ) {
			return '';
		}

		$course_id = $post->ID;
		$html      = '';

		if ( 'course' !== get_post_type( $course_id ) ) {
			return '';
		}

		if ( Sensei_Course::can_current_user_manually_enrol( $course_id ) ) {
			if ( ! Sensei_Course::is_prerequisite_complete( $course_id ) ) {
				Sensei()->notices->add_notice( Sensei()->course::get_course_prerequisite_message( $course_id ), 'info', 'sensei-take-course-prerequisite' );
				$html = $this->render_disabled( $content );
			} else {
				// Replace button label in case it's coming from a sign in with redirect to take course.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action based on input.
				if ( isset( $_GET['take_course_sign_in'] ) ) {
					$content = preg_replace(
						'/(.*)<button(.*)>(.*)<\/button>(.*)/',
						'$1<button$2>' . __( 'Start course', 'sensei-lms' ) . '</button>$4',
						$content,
						1
					);
				}
				$html = $this->render_with_start_course_form( $course_id, $content );
			}
		} elseif ( ! is_user_logged_in() ) {
			$html = $this->render_with_login( $content );
		}

		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'sensei-block-wrapper sensei-cta' ) );
		return ! empty( $html ) ? '<div ' . $wrapper_attributes . '>' . $html . '</div>' : '';
	}

	/**
	 * Render block with start course action.
	 *
	 * @param int    $course_id ID of the course.
	 * @param string $button    Block HTML.
	 *
	 * @return string
	 */
	private function render_with_start_course_form( $course_id, $button ) {
		$nonce = wp_nonce_field( 'woothemes_sensei_start_course_noonce', 'woothemes_sensei_start_course_noonce', false, false );
		return ( '
			<form method="POST" action="' . esc_url( get_permalink( $course_id ) ) . '">
			<input type="hidden" name="course_start" value="1" />
			' . $nonce . '
			' . $this->add_button_classes( $button ) . '
			</form>
			' );
	}

	/**
	 * Add additional classes to the button.
	 *
	 * @param string $button The button html.
	 *
	 * @return string The html with the added classes.
	 */
	private function add_button_classes( $button ) : string {
		wp_enqueue_script( 'sensei-stop-double-submission' );

		if ( preg_match( '/<button(.*)class="(.*)"/', $button ) ) {
			return preg_replace(
				'/<button(.*) class="(.*)"/',
				'<button$1 class="sensei-stop-double-submission $2"',
				$button,
				1
			);
		}

		return preg_replace(
			'/<button(.*)/',
			'<button class="sensei-stop-double-submission" $1',
			$button,
			1
		);
	}

	/**
	 * Render block with link to login page, when the user is not logged in.
	 *
	 * @param string $content Block HTML.
	 *
	 * @return string
	 */
	private function render_with_login( $content ) {
		$target = sensei_user_registration_url();

		$course_url = add_query_arg( 'take_course_sign_in', '1', get_permalink() );

		return ( '
			<form method="GET" action="' . esc_url( $target ) . '">
			<input type="hidden" name="redirect_to" value="' . esc_url_raw( $course_url ) . '" />
			' . $content . '
			</form>
			' );
	}

	/**
	 * Render with a disabled state.
	 *
	 * @param string $content Block HTML.
	 *
	 * @return string
	 */
	private function render_disabled( $content ) {
		$content = preg_replace( '/(\<button)/i', '<button disabled="disabled"', $content );

		return $content;
	}

	/**
	 * Message text for prerequisite course the learner has to complete.
	 *
	 * @param int $course_id ID of the course.
	 *
	 * @deprecated 3.8.0 use Sensei_Course::get_course_prerequisite_message.
	 *
	 * @return string
	 */
	public function get_course_prerequisite_message( $course_id ) {
		_deprecated_function( __METHOD__, '3.8.0', 'Sensei_Course::get_course_prerequisite_message' );

		return Sensei()->course::get_course_prerequisite_message( $course_id );
	}
}

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
	 * Sensei_Contact_Teacher_Block constructor.
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
			'sensei-lms/button-take-course',
			[
				'render_callback' => [ $this, 'render_take_course_block' ],
			]
		);
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
		$course_id = $post->ID;

		if ( Sensei_Course::can_current_user_manually_enrol( $course_id ) ) {
			if ( ! Sensei_Course::is_prerequisite_complete( $course_id ) ) {
				return $this->render_disabled_with_prerequisite( $course_id, $content );
			}
			return $this->render_with_start_course_form( $course_id, $content );
		}

		if ( ! is_user_logged_in() ) {
			return $this->render_with_login( $content );
		}

		return '';

	}

	/**
	 * Render block with start course action.
	 *
	 * @param int    $course_id
	 * @param string $button Block HTML.
	 *
	 * @return string
	 */
	private function render_with_start_course_form( $course_id, $button ) {
		$nonce = wp_nonce_field( 'woothemes_sensei_start_course_noonce', 'woothemes_sensei_start_course_noonce', false, false );
		return ( '
			<form method="POST" action="' . esc_url( get_permalink( $course_id ) ) . '">
			<input type="hidden" name="course_start" value="1" />
			' . $nonce . '
			' . $button . '
			</form>
			' );
	}

	/**
	 * Render block with link to login page, when the user is not logged in.
	 *
	 * @param string $content Block HTML.
	 *
	 * @return string
	 */
	private function render_with_login( $content ) {

		/**
		 * Filter to force Sensei to output the default WordPress user
		 * registration link.
		 *
		 * @param bool $wp_register_link default false
		 *
		 * @since 1.9.0
		 */
		$wp_register_link = apply_filters( 'sensei_use_wp_register_link', false );

		$settings = Sensei()->settings->get_settings();
		if ( ! empty( $settings['my_course_page'] ) && ! $wp_register_link ) {
			$my_courses_url = get_permalink( intval( $settings['my_course_page'] ) );
			$target         = esc_url( $my_courses_url );
		} else {
			$target = wp_registration_url();
		}

		return ( '
			<form method="GET" action="' . esc_url( $target ) . '">
			' . $content . '
			</form>
			' );
	}

	/**
	 * Render disabled state when a prerequisite course is required.
	 *
	 * @param int    $course_id
	 * @param string $content Block HTML.
	 *
	 * @return string
	 */
	private function render_disabled_with_prerequisite( $course_id, $content ) {
		$notice  = '<figcaption>' . $this->get_course_prerequisite_message( $course_id ) . '</figcaption>';
		$content = preg_replace( '/(\<button)/i', '<button disabled="disabled"', $content );
		$content = preg_replace( '/(<\/button>)/', '$1 ' . $notice, $content );
		return $content;
	}

	/**
	 * Message text for prerequisite course the learner has to complete.
	 *
	 * @param int $course_id
	 *
	 * @return string
	 */
	private function get_course_prerequisite_message( $course_id ) {
		$course_prerequisite_id   = absint( get_post_meta( $course_id, '_course_prerequisite', true ) );
		$course_title             = get_the_title( $course_prerequisite_id );
		$prerequisite_course_link = '<a href="' . esc_url( get_permalink( $course_prerequisite_id ) )
			. '" title="'
			. sprintf(
			// translators: Placeholder $1$s is the course title.
				esc_attr__( 'You must first complete: %1$s', 'sensei-lms' ),
				$course_title
			)
			. '">' . $course_title . '</a>';

		$complete_prerequisite_message = sprintf(
		// translators: Placeholder $1$s is the course title.
			esc_html__( 'You must first complete %1$s before taking this course', 'sensei-lms' ),
			$prerequisite_course_link
		);

		/**
		 * Filter sensei_course_complete_prerequisite_message.
		 *
		 * @param string $complete_prerequisite_message the message to filter
		 *
		 * @since 1.9.10
		 */
		return apply_filters( 'sensei_course_complete_prerequisite_message', $complete_prerequisite_message );

	}
}

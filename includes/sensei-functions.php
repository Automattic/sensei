<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Global Sensei functions
 */

/**
 * Determine if the current page is a Sensei LMS page.
 *
 * @since 1.5.0
 *
 * @return bool True if current page is a Sensei LMS page.
 */
function is_sensei() {
	global $post;

	$is_sensei = false;

	$post_types = array( 'lesson', 'course', 'quiz', 'question', 'sensei_message' );
	$taxonomies = array( 'course-category', 'quiz-type', 'question-type', 'lesson-tag', 'module' );

	if ( is_post_type_archive( $post_types )
		|| is_singular( $post_types )
		|| is_tax( $taxonomies )
	) {
		$is_sensei = true;
	} elseif ( is_object( $post ) && ! is_wp_error( $post ) ) {
		$course_page_id           = intval( Sensei()->settings->settings['course_page'] );
		$my_courses_page_id       = intval( Sensei()->settings->settings['my_course_page'] );
		$course_completed_page_id = intval( Sensei()->settings->settings['course_completed_page'] );

		if ( in_array( $post->ID, array( $course_page_id, $my_courses_page_id, $course_completed_page_id ) )
			|| Sensei_Utils::is_learner_profile_page()
			|| Sensei_Utils::is_course_results_page()
			|| Sensei_Utils::is_teacher_archive_page()
			|| Sensei()->blocks->has_sensei_blocks()
		) {
			$is_sensei = true;
		}
	}

	return apply_filters( 'is_sensei', $is_sensei, $post );
}

/**
 * Determine if a user is an admin that can access all of Sensei without restrictions or if he is a teacher accessing
 * his own course.
 *
 * @since 1.4.0
 * @since 3.0.0 Added `$user_id` argument. Preserves backward compatibility.
 *
 * @param int $user_id User ID. Defaults to current user.
 *
 * @return boolean
 */
function sensei_all_access( $user_id = null ) {
	if ( null === $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$access = false;

	if ( user_can( $user_id, 'manage_sensei' ) ) {
		$access = true;
	} else {
		$course_id = Sensei_Utils::get_current_course();

		if ( $course_id ) {
			$teacher = (int) get_post( $course_id )->post_author;
			$access  = $user_id === $teacher;
		}
	}

	if ( has_filter( 'sensei_all_access' ) ) {
		// For backwards compatibility with filter, we temporarily need to change the current user.
		$previous_current_user_id = get_current_user_id();
		wp_set_current_user( $user_id );

		/**
		 * Filter sensei_all_access function result which determines if the current user
		 * can access all of Sensei without restrictions.
		 *
		 * @since 1.4.0
		 * @deprecated 3.0.0
		 *
		 * @param bool $access True if user has all access.
		 */
		$access = apply_filters_deprecated( 'sensei_all_access', [ $access ], '3.0.0', 'sensei_user_all_access' );

		wp_set_current_user( $previous_current_user_id );
	}

	/**
	 * Filter if a particular user has access to all of Sensei without restrictions.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $access  True if user has all access.
	 * @param int  $user_id User ID to check.
	 */
	return apply_filters( 'sensei_user_all_access', $access, $user_id );
}

if ( ! function_exists( 'sensei_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark colour on a background colour
	 *
	 * @access public
	 * @param mixed  $color
	 * @param string $dark (default: '#000000')
	 * @param string $light (default: '#FFFFFF')
	 * @return string
	 */
	function sensei_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {

		$hex = str_replace( '#', '', $color );

		$c_r        = hexdec( substr( $hex, 0, 2 ) );
		$c_g        = hexdec( substr( $hex, 2, 2 ) );
		$c_b        = hexdec( substr( $hex, 4, 2 ) );
		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155 ? $dark : $light;
	}
}

if ( ! function_exists( 'sensei_rgb_from_hex' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @return string
	 */
	function sensei_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb      = [];
		$rgb['R'] = hexdec( $color[0] . $color[1] );
		$rgb['G'] = hexdec( $color[2] . $color[3] );
		$rgb['B'] = hexdec( $color[4] . $color[5] );
		return $rgb;
	}
}

if ( ! function_exists( 'sensei_hex_darker' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int   $factor (default: 30)
	 * @return string
	 */
	function sensei_hex_darker( $color, $factor = 30 ) {
		$base  = sensei_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) :
			$amount      = $v / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) :
				$new_hex_component = '0' . $new_hex_component;
			endif;
			$color .= $new_hex_component;
		endforeach;

		return $color;
	}
}

if ( ! function_exists( 'sensei_hex_lighter' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int   $factor (default: 30)
	 * @return string
	 */
	function sensei_hex_lighter( $color, $factor = 30 ) {
		$base  = sensei_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) :
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) :
				$new_hex_component = '0' . $new_hex_component;
			endif;
			$color .= $new_hex_component;
		endforeach;

		return $color;
	}
}

/**
 * Provides an interface to allow us to deprecate hooks while still allowing them
 * to work, but giving the developer an error message.
 *
 * @since 1.9.0
 *
 * @param $hook_tag
 * @param $version
 * @param $alternative
 * @param array       $args
 */
function sensei_do_deprecated_action( $hook_tag, $version, $alternative = '', $args = array() ) {

	if ( has_action( $hook_tag ) ) {

		// translators: Placeholders are the hook tag and the version which it was deprecated, respectively.
		$error_message = sprintf( __( "SENSEI: The hook '%1\$s', has been deprecated since '%2\$s'.", 'sensei-lms' ), $hook_tag, $version );

		if ( ! empty( $alternative ) ) {

			// translators: Placeholder is the alternative action name.
			$error_message .= sprintf( __( "Please use '%s' instead.", 'sensei-lms' ), $alternative );

		}

		trigger_error( esc_html( $error_message ) );
		do_action( $hook_tag, $args );

	}

}

/**
 * Check the given post or post type id is a of the
 * the course post type.
 *
 * @since 1.9.0
 *
 * @param $post_id
 * @return bool
 */
function sensei_is_a_course( $post ) {

	return 'course' == get_post_type( $post );

}

/**
 * Determine the login link
 * on the frontend.
 *
 * This function will return the my-courses page link
 * or the wp-login link.
 *
 * @since 1.9.0
 */
function sensei_user_login_url() {

	$my_courses_page_id = intval( Sensei()->settings->get( 'my_course_page' ) );
	$page               = get_post( $my_courses_page_id );

	if ( $my_courses_page_id && isset( $page->ID ) && 'page' == get_post_type( $page->ID ) ) {

		return get_permalink( $page->ID );

	} else {

		return wp_login_url();

	}

}

/**
 * Checks the settings to see
 * if a user must be logged in to view content
 *
 * duplicate of Sensei()->access_settings().
 *
 * @since 1.9.0
 * @since 3.5.2 Added hook to filter the return.
 * @return bool
 */
function sensei_is_login_required() {
	$course_id = Sensei_Utils::get_current_course();

	$login_required = isset( Sensei()->settings->settings['access_permission'] ) && ( true == Sensei()->settings->settings['access_permission'] );

	/**
	 * Filters the access_permission that says if the user must be logged
	 * to view the lesson content.
	 *
	 * @since 3.5.2
	 *
	 * @hook sensei_is_login_required
	 *
	 * @param {bool}     $must_be_logged_to_view_lesson True if user need to be logged to see the lesson.
	 * @param {int|null} $course_id                     Course post ID.
	 *
	 * @return {bool} Whether the user needs to be logged in to view content.
	 */
	return apply_filters( 'sensei_is_login_required', $login_required, $course_id );
}

/**
 * Checks if this theme supports Sensei templates.
 *
 * @since 1.12.0
 * @return bool
 */
function sensei_does_theme_support_templates() {
	$current_theme = wp_get_theme()->get_template();
	$themes        = Sensei()->theme_integration_loader->get_supported_themes();

	return in_array( $current_theme, $themes, true ) || current_theme_supports( 'sensei' );
}

if ( ! function_exists( 'sensei_check_woocommerce_version' ) ) {
	/**
	 * Check if WooCommerce version is greater than the one specified.
	 *
	 * @deprecated 2.0.0
	 *
	 * @param string $version Version to check against.
	 * @return boolean
	 */
	function sensei_check_woocommerce_version( $version = '2.1' ) {
		_deprecated_function( __FUNCTION__, '2.0.0' );

		if ( method_exists( 'Sensei_WC', 'is_woocommerce_active' ) && Sensei_WC::is_woocommerce_active() ) {
			global $woocommerce;
			if ( version_compare( $woocommerce->version, $version, '>=' ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * Track a Sensei event.
 *
 * @since 2.1.0
 *
 * @param string $event_name The name of the event, without the `sensei_` prefix.
 * @param array  $properties The event properties to be sent.
 */
function sensei_log_event( $event_name, $properties = [] ) {
	$properties = array_merge(
		Sensei_Usage_Tracking_Data::get_event_logging_base_fields(),
		$properties
	);

	/**
	 * Explicitly disable usage tracking from being sent.
	 *
	 * @since 2.1.0
	 *
	 * @param bool   $log_event    Whether we should log the event.
	 * @param string $event_name   The name of the event, without the `sensei_` prefix.
	 * @param array  $properties   The event properties to be sent.
	 */
	if ( false === apply_filters( 'sensei_log_event', true, $event_name, $properties ) ) {
		return;
	}

	Sensei_Usage_Tracking::get_instance()->send_event( $event_name, $properties );
}

/**
 * Track a Sensei event with Jetpack, when Jetpack is available.
 *
 * @since 3.7.0
 *
 * @param string $event_name The name of the event, without the `sensei_` prefix.
 * @param array  $properties The event properties to be sent.
 */
function sensei_log_jetpack_event( $event_name, $properties = [] ) {
	if ( ! class_exists( 'Automattic\Jetpack\Tracking' ) || ! Sensei()->usage_tracking->is_tracking_enabled() ) {
		return;
	}

	$jetpack_connection = Jetpack::connection();
	if ( $jetpack_connection->is_user_connected() ) {
		$tracking = new Automattic\Jetpack\Tracking( 'sensei', $jetpack_connection );
		$tracking->record_user_event( $event_name, $properties );
	}
}

<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Global Sensei functions
 */

function is_sensei() {
	global $post;

	$is_sensei = false;

	$post_types = array( 'lesson', 'course', 'quiz', 'question' );
	$taxonomies = array( 'course-category', 'quiz-type', 'question-type', 'lesson-tag', 'module' );

	if( is_post_type_archive( $post_types ) || is_singular( $post_types ) || is_tax( $taxonomies ) ) {

		$is_sensei = true;

	}

	if( is_object( $post ) && ! is_wp_error( $post ) ) {

		$course_page_id = intval( Sensei()->settings->settings[ 'course_page' ] );
		$my_courses_page_id = intval( Sensei()->settings->settings[ 'my_course_page' ] );

		if( in_array( $post->ID, array( $course_page_id, $my_courses_page_id ) ) ) {

			$is_sensei = true;

		}

	}

	return apply_filters( 'is_sensei', $is_sensei, $post );
}

/**
 * Determine if the current user is and admin that
 * can acess all of Sensei without restrictions
 *
 * @since 1.4.0
 * @return boolean
 */
function sensei_all_access() {

    $access = current_user_can( 'manage_sensei' ) || current_user_can( 'manage_sensei_grades' );

    /**
     * Filter sensei_all_access function result
     * which determinse if the current user
     * can access all of Sensei without restrictions
     *
     * @since 1.4.0
     * @param bool $access
     */
	return apply_filters( 'sensei_all_access', $access );

} // End sensei_all_access()

if ( ! function_exists( 'sensei_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark colour on a background colour
	 *
	 * @access public
	 * @param mixed $color
	 * @param string $dark (default: '#000000')
	 * @param string $light (default: '#FFFFFF')
	 * @return string
	 */
	function sensei_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {

	    $hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );
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

		$rgb['R'] = hexdec( $color{0}.$color{1} );
		$rgb['G'] = hexdec( $color{2}.$color{3} );
		$rgb['B'] = hexdec( $color{4}.$color{5} );
		return $rgb;
	}
}

if ( ! function_exists( 'sensei_hex_darker' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function sensei_hex_darker( $color, $factor = 30 ) {
		$base = sensei_rgb_from_hex( $color );
		$color = '#';

		foreach ($base as $k => $v) :
	        $amount = $v / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v - $amount;

	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
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
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function sensei_hex_lighter( $color, $factor = 30 ) {
		$base = sensei_rgb_from_hex( $color );
		$color = '#';

	    foreach ($base as $k => $v) :
	        $amount = 255 - $v;
	        $amount = $amount / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v + $amount;

	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
	   	endforeach;

	   	return $color;
	}
}

/**
 * WC Detection for backwards compatibility
 *
 * @since 1.9.0
 * @deprecated since 1.9.0 use  Sensei_WC::is_woocommerce_active()
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
    function is_woocommerce_active() {
        // calling is present instead of is active here
        // as this function can override other is_woocommerce_active
        // function in other woo plugins and Sensei_WC::is_woocommerce_active
        // also check the sensei settings for enable WooCommerce support, which
        // other plugins should not check against.
        return Sensei_WC::is_woocommerce_present();
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
 * @param array $args
 */
function sensei_do_deprecated_action( $hook_tag, $version, $alternative="" , $args = array()  ){

    if( has_action( $hook_tag ) ){

        $error_message = sprintf( __( "SENSEI: The hook '%s', has been deprecated since '%s'." , 'woothemes-sensei'), $hook_tag ,$version );

        if( !empty( $alternative ) ){

            $error_message .= sprintf( __("Please use '%s' instead.", 'woothemes-sensei'), $alternative ) ;

        }

        trigger_error( $error_message );
        do_action( $hook_tag , $args );

    }

}// end sensei_do_deprecated_action

/**
 * Check the given post or post type id is a of the
 * the course post type.
 *
 * @since 1.9.0
 *
 * @param $post_id
 * @return bool
 */
function sensei_is_a_course( $post ){

	return "course" == get_post_type( $post );

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
function sensei_user_login_url(){

    $my_courses_page_id = intval( Sensei()->settings->get( 'my_course_page' ) );
    $page = get_post( $my_courses_page_id );

    if ( $my_courses_page_id && isset( $page->ID ) && 'page' == get_post_type( $page->ID )  ){

        return get_permalink( $page->ID );

    } else {

        return wp_login_url();

    }

}// end sensei_user_login_link

/**
 * Checks the settings to see
 * if a user must be logged in to view content
 *
 * duplicate of Sensei()->access_settings().
 *
 * @since 1.9.0
 * @return bool
 */
function sensei_is_login_required(){

    $login_required = isset( Sensei()->settings->settings['access_permission'] ) && ( true == Sensei()->settings->settings['access_permission'] );

    return $login_required;

}
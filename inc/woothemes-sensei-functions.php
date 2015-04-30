<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Global Sensei functions
 */

function is_sensei() {
	global $post, $woothemes_sensei;

	$is_sensei = false;

	$post_types = array( 'lesson', 'course', 'quiz', 'question' );
	$taxonomies = array( 'course-category', 'quiz-type', 'question-type', 'lesson-tag' );

	if( is_post_type_archive( $post_types ) || is_singular( $post_types ) || is_tax( $taxonomies ) ) {
		$is_sensei = true;
	}

	if( is_object( $post ) && ! is_wp_error( $post ) ) {

		$course_page_id = intval( $woothemes_sensei->settings->settings[ 'course_page' ] );
		$my_courses_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );

		if( in_array( $post->ID, array( $course_page_id, $my_courses_page_id ) ) ) {
			$is_sensei = true;
		}
	}

	return apply_filters( 'is_sensei', $is_sensei, $post );
}

function sensei_all_access() {
	$access = false;

	if( current_user_can( 'manage_sensei' ) || current_user_can( 'manage_sensei_grades' ) ) {
		$access = true;
	}

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
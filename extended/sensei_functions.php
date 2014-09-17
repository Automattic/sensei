<?php
// This file contains material which is the pre-existing property of Psycle Interactive Limited.
// Copyright (c) 2014 Psycle Interactive. All rights reserved.

// Contains Functions specifically for Sensei 

/**
 * Check and verify if the current Quiz needs a password.
 * Very similar to WP post_password_required()
 * 
 * @param type $quiz_id
 * @return boolean
 */
function imperial_sensei_quiz_password_required( $post = null ) {
	$post = get_post( $post );

	$quiz_password = trim( get_post_meta( $post->ID, '_quiz_password', true ) );
	if ( empty( $quiz_password ) ) {
		return false;
	}
	if ( ! isset( $_COOKIE['wp-quizpass_' . COOKIEHASH] ) ) {
		return true;
	}
	require_once ABSPATH . 'wp-includes/class-phpass.php';
	$hasher = new PasswordHash( 8, true );

	$hash = wp_unslash( $_COOKIE[ 'wp-quizpass_' . COOKIEHASH ] );
	if ( 0 !== strpos( $hash, '$P$B' ) ) {
		return true;
	}
	return ! $hasher->CheckPassword( $quiz_password, $hash );
}

/**
 * Retrieve protected quiz password form content.
 *
 * @uses apply_filters() Calls 'sensei_the_quiz_password_form' filter on output.
 * @param int|WP_Post $post Optional. A post ID or post object.
 * @return string HTML content for password form for password protected post.
 */
function imperial_sensei_get_the_password_form( $post = 0 ) {
	$post = get_post( $post );
	$label = 'pwbox-' . ( empty($post->ID) ? rand() : $post->ID );
	$output  = '<form action="' . esc_url( get_the_permalink( $post->ID ) ) . '" class="post-password-form" method="post">';
	$output .= wp_nonce_field( 'quiz-password-'.$post->ID, '_wpnonce', true, false );
	if ( isset($_GET['message']) && $_GET['message'] ) {
		$output .= '<div class="sensei-message alert">' . __('Invalid Password', 'imperial') .'</div>';
	}
	$output .= '<p>' . __( 'This quiz is password protected. To view it please enter the password below:', 'imperial' ) . '</p>
	<p><label for="' . $label . '">' . __( 'Password:' ) . ' <input name="quiz_password" id="' . $label . '" type="password" size="20" /></label> <input type="submit" name="sensei_quiz_password_form" value="' . esc_attr__( 'Submit' ) . '" /></p></form>
	';

	return apply_filters( 'sensei_the_quiz_password_form', $output );
}


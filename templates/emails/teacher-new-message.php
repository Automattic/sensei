<?php
/**
 * Teacher new message email
 *
 * @author WooThemes
 * @package Sensei/Templates/Emails/HTML
 * @version 1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php

// Get data for email content
global $sensei_email_data;
extract( $sensei_email_data );

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$small = "text-align: center !important;";

$large = "text-align: center !important;font-size: 350% !important;line-height: 100% !important;";

?>

<?php do_action( 'sensei_before_email_content', $template ); ?>

<p style="<?php echo esc_attr( $small ); ?>"><?php _e( 'Your student', 'woothemes-sensei' ); ?></p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo $learner_name; ?></h2>

<p style="<?php echo esc_attr( $small ); ?>"><?php printf( __( 'has sent you a private message regarding the %1$s', 'woothemes-sensei' ), $content_type ); ?></p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo $content_title; ?></h2>

<hr/>

<?php echo wpautop( $message ); ?>

<hr/>

<p style="<?php echo esc_attr( $small ); ?>"><?php printf( __( 'You can reply to this message %1$shere%2$s.', 'woothemes-sensei' ), '<a href="' . get_permalink( $message_id ) . '">', '</a>' ); ?></p>

<?php do_action( 'sensei_after_email_content', $template ); ?>
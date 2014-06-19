<?php
/**
 * New message reply email
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

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo $commenter_name; ?></h2>

<p style="<?php echo esc_attr( $small ); ?>"><?php printf( __( 'has replied to your private message regarding the %1$s', 'woothemes-sensei' ), $content_type ); ?></p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo $content_title; ?></h2>

<hr/>

<?php echo wpautop( $message ); ?>

<hr/>

<p style="<?php echo esc_attr( $small ); ?>"><?php printf( __( 'You can view the message and reply %1$shere%2$s.', 'woothemes-sensei' ), '<a href="' . $comment_link . '">', '</a>' ); ?></p>

<?php do_action( 'sensei_after_email_content', $template ); ?>
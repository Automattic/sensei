<?php
/**
 * New message reply email
 *
 * @author  Automattic
 * @package Sensei/Templates/Emails/HTML
 * @version 2.0.0
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly ?>

<?php

// Get data for email content
global $sensei_email_data;
extract( $sensei_email_data );

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$small = 'text-align: center !important;';

$large = 'text-align: center !important;font-size: 350% !important;line-height: 100% !important;';

?>

<?php do_action( 'sensei_before_email_content', $template ); ?>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo esc_html( $commenter_name ); ?></h2>

<p style="<?php echo esc_attr( $small ); ?>">
<?php
// translators: Placeholder is the post type (e.g. course or lesson)
printf( esc_html__( 'has replied to your private message regarding the %1$s', 'sensei-lms' ), esc_html( $content_type ) );
?>
</p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo esc_html( $content_title ); ?></h2>

<hr/>

<?php echo wp_kses_post( wpautop( $message ) ); ?>

<hr/>

<p style="<?php echo esc_attr( $small ); ?>">
<?php
// translators: Placeholder is an opening an closing <a> tag linking to the comment.
printf( esc_html__( 'You can view the message and reply %1$shere%2$s.', 'sensei-lms' ), '<a href="' . esc_url( $comment_link ) . '">', '</a>' );
?>
</p>

<?php do_action( 'sensei_after_email_content', $template ); ?>

<?php
/**
 * Teacher new message email
 *
 * @author WooThemes
 * @package Sensei/Templates/Emails/HTML
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get data for email content
global $sensei_email_data;

// For Gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$style_small = "text-align: center !important;";

$style_large = "text-align: center !important;font-size: 350% !important;line-height: 100% !important;";

do_action( 'sensei_before_email_content', $template );
?>

<p style="<?php echo esc_attr( $style_small ); ?>">
    <?php _e( 'The Course', 'woothemes-sensei' ); ?>
</p>

<h2 style="<?php echo esc_attr( $style_large ); ?>">
    <?php echo $sensei_email_data['course_name']; ?>
</h2>

<p style="<?php echo esc_attr( $style_small ); ?>">
    <?php _e( 'has been assigned to you.', 'woothemes-sensei' ); ?>
</p>

<hr/>

<p style="<?php echo esc_attr( $style_small ); ?>">
    <?php

    echo __( 'You can edit the assigned course here: ', 'woothemes-sensei' ) . '<a href="' . esc_url( $sensei_email_data['course_edit_link'] ) . '">'. $sensei_email_data['course_name'] . '</a>';

    ?>
</p>

<?php

do_action( 'sensei_after_email_content', $sensei_email_data['template'] );

?>
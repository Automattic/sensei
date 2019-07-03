<?php
/**
 * Teacher new message email
 *
 * @author  Automattic
 * @package Sensei/Templates/Emails/HTML
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Get data for email content
global $sensei_email_data;

// For Gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$style_small = 'text-align: center !important;';

$style_large = 'text-align: center !important;font-size: 350% !important;line-height: 100% !important;';

// $template is provided by the calling code.
// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
do_action( 'sensei_before_email_content', $template );
?>

<p style="<?php echo esc_attr( $style_small ); ?>">
	<?php esc_html_e( 'The Course', 'sensei-lms' ); ?>
</p>

<h2 style="<?php echo esc_attr( $style_large ); ?>">
	<?php echo esc_html( $sensei_email_data['course_name'] ); ?>
</h2>

<p style="<?php echo esc_attr( $style_small ); ?>">
	<?php echo esc_html__( 'was submitted for review by ', 'sensei-lms' ) . esc_html( $sensei_email_data['teacher']->display_name ); ?>
</p>

<hr/>

<p style="<?php echo esc_attr( $style_small ); ?>">
	<?php

	echo esc_html__( 'You can review and publish the new course here:  ', 'sensei-lms' ) . '<a href="' . esc_url( $sensei_email_data['course_edit_link'] ) . '">' . esc_html( $sensei_email_data['course_name'] ) . '</a>';

	?>
</p>

<?php

do_action( 'sensei_after_email_content', $sensei_email_data['template'] );

?>

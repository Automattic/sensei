<?php
/**
 * Learner graded quiz email
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

<p style="<?php echo esc_attr( $small ); ?>">
<?php
// translators: Placeholder is the translated text for "passed" or "failed".
printf( esc_html__( 'You %1$s the lesson', 'sensei-lms' ), esc_html( $passed ) );
?>
</p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo esc_html( get_the_title( $lesson_id ) ); ?></h2>

<p style="<?php echo esc_attr( $small ); ?>"><?php esc_html_e( 'with a grade of', 'sensei-lms' ); ?></p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo esc_html( $grade ) . '%'; ?></h2>

<p style="<?php echo esc_attr( $small ); ?>">
<?php
// translators: Placeholder is the passmark as a percentage.
printf( esc_html__( 'The pass mark is %1$s', 'sensei-lms' ), esc_html( $passmark ) . '%' );
?>
</p>

<hr/>

<p style="<?php echo esc_attr( $small ); ?>">
<?php
// translators: Placeholders are an opening and closing <a> tag linking to the quiz permalink.
printf( esc_html__( 'You can review your grade and your answers %1$shere%2$s.', 'sensei-lms' ), '<a href="' . esc_url( get_permalink( $quiz_id ) ) . '">', '</a>' );
?>
</p>

<?php do_action( 'sensei_after_email_content', $template ); ?>

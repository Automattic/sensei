<?php
/**
 * Teacher started course email
 *
 * @author  Automattic
 * @package Sensei/Templates/Emails/HTML
 * @version 2.0.0
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// Get data for email content
global $sensei_email_data;
extract( $sensei_email_data );

// For gmail compatibility, including CSS styles in head/body are stripped out therefore styles need to be inline. These variables contain rules which are added to the template inline. !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
$small = 'text-align: center !important;';

$large = 'text-align: center !important;font-size: 350% !important;line-height: 100% !important;';

?>

<?php do_action( 'sensei_before_email_content', $template ); ?>

<p style="<?php echo esc_attr( $small ); ?>"><?php esc_html_e( 'Your student', 'sensei-lms' ); ?></p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo esc_html( $learner_name ); ?></h2>

<p style="<?php echo esc_attr( $small ); ?>"><?php esc_html_e( 'has started the course', 'sensei-lms' ); ?></p>

<h2 style="<?php echo esc_attr( $large ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></h2>

<hr/>

<p style="<?php echo esc_attr( $small ); ?>">
<?php
// translators: Placeholders are an opening and closing <a> tag linking to the course's learners page in wp-admin.
printf( esc_html__( 'Manage this course\'s learners %1$shere%2$s.', 'sensei-lms' ), '<a href="' . esc_url( admin_url( 'admin.php?page=sensei_learners&view=learners&course_id=' . $course_id ) ) . '">', '</a>' );
?>
</p>

<?php do_action( 'sensei_after_email_content', $template ); ?>

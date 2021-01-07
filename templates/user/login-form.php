<?php
/**
 * The Template for displaying the sensei login form
 *
 * Override this template by copying it to yourtheme/sensei/user/login-form.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 *  Executes before the Sensei Login form markup begins.
 *
 * @since 1.9.0
 */
do_action( 'sensei_login_form_before' );

?>

<h2><?php esc_html_e( 'Login', 'sensei-lms' ); ?></h2>

<?php wp_login_form( [ 'sensei-login' => true ] ); ?>

<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'sensei-lms' ); ?></a>

<?php
/**
 *  Executes after the Login form markup closes.
 *
 *  @since 1.9.0
 */
do_action( 'sensei_login_form_after' );
?>

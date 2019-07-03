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

<form method="post" name="sensi-login-form" id="loginform" class="login sensei">

	<?php
	/**
	 *  Executes inside the sensei login form before all the default fields.
	 *
	 * @since 1.6.2
	 */
		do_action( 'sensei_login_form_inside_before' );
	?>

	<p class="sensei-login-username form-row form-row-wide">

				<label for="sensei_user_login"><?php esc_html_e( 'Username or Email', 'sensei-lms' ); ?> </label>

				<input type="text" name="log" id="sensei_user_login" class="input" value="" size="20">

	</p>

	<p class="sensei-login-password form-row form-row-wide">

				<label for="sensei_user_pass"> <?php esc_html_e( 'Password', 'sensei-lms' ); ?>  </label>

				<input type="password" name="pwd" id="sensei_user_pass" class="input txt text" value="" size="20">

	</p>

	<?php
	/**
	 *  Executes inside the sensei login form after the password field.
	 *
	 *  You can use the action to add extra form login fields.
	 *
	 * @since 1.6.2
	 */
		do_action( 'sensei_login_form_inside_after_password_field' );
	?>

	<p class='sensei-login-submit'>

		<input type="submit" class="button" name="login" value="<?php esc_attr_e( 'Login', 'sensei-lms' ); ?>" />

		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'sensei-lms' ); ?></a>

	</p>

	<p class='remember_me' >

		<label for="rememberme" class="inline">

			<input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php esc_html_e( 'Remember me', 'sensei-lms' ); ?>

		</label>

	</p>

	<?php
	/**
	 *  Executes inside the sensei login form after all the default fields.
	 *
	 * @since 1.6.2
	 */
		do_action( 'sensei_login_form_inside_after' );
	?>

	<?php wp_nonce_field( 'sensei-login' ); ?>

	<input type="hidden" name="redirect" value="<?php echo esc_url_raw( sensei_get_current_page_url() ); ?>" />

	<input type="hidden" name="form" value="sensei-login" />

	<div class="clear"></div>

</form>

<?php
/**
 *  Executes after the Login form markup closes.
 *
 *  @since 1.9.0
 */
do_action( 'sensei_login_form_after' );
?>

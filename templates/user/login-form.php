<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The Template for displaying the sensei login form
 *
 * Override this template by copying it to yourtheme/sensei/user/login-form.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.6.2
 */

if ( is_user_logged_in() ) {
	return;
}

// get the current page url
global $wp;
$current_page_url =  home_url( $wp->request );

// get the referer url for redirecting after authentication

?>

<h2><?php _e( 'Login', 'woothemes-sensei' ); ?></h2>

<form method="post" name='sensi-login-form' action="<?php echo esc_url( $current_page_url ); ?>" id="loginform" class="login sensei">

<?php
/**
 *  Executes inside the sensei login form before all the default fields.
 *
 * @since 1.6.2
 */
 	do_action( 'sensei_login_form_inside_before' ); 
?> 	

	<p class="sensei-login-username form-row form-row-wide">
				<label for="sensei_user_login"><?php _e('Username or Email','woothemes-sensei')?> </label>
				<input type="text" name="log" id="sensei_user_login" class="input" value="" size="20">
	</p>
	
	<p class="sensei-login-password form-row form-row-wide">
				<label for="sensei_user_pass"> <?php _e('Password','woothemes-sensei')?>  </label>
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
		<input type="submit" class="button" name="login" value="<?php _e( 'Login', 'woothemes-sensei' ); ?>" />
		<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php _e( 'Lost your password?', 'woothemes-sensei' ); ?></a>
	</p>	
	<p class='remember_me' >
		<label for="rememberme" class="inline">
			<input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember me', 'woothemes-sensei' ); ?>
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

	<?php  //all hiddne field below : ?> 	
	<?php wp_nonce_field( 'sensei-login' ); ?>
	<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ) ?>" />
	<input type="hidden" name="form" value="sensei-login" />

	<div class="clear"></div>
</form>
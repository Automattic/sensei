<?php
/**
 * File containing the PHP template for the email list signup form.
 *
 * @package sensei
 * @since   2.0.0
 * @deprecated 3.1.0 Email signup flow moved to Setup Wizard
 */

?>
<div id="mc_embed_signup">
	<form
		action="https://senseilms.us19.list-manage.com/subscribe/post?u=<?php echo esc_attr( Sensei_Email_Signup_Form::MC_USER_ID ); ?>&amp;id=<?php echo esc_attr( Sensei_Email_Signup_Form::MC_LIST_ID ); ?>"
		method="post"
		id="mc-embedded-subscribe-form"
		name="mc-embedded-subscribe-form"
		class="validate"
		target="_blank"
		novalidate
	>
		<input type="hidden" name="SOURCE" value="PLUGIN">
		<div id="mc_embed_signup_scroll">
			<h2><?php esc_html_e( 'Join Sensei LMS\'s Mailing List!', 'sensei-lms' ); ?></h2>
			<p>
				<?php esc_html_e( "We're here for you — get tips, product updates, and inspiration straight to your mailbox.", 'sensei-lms' ); ?>
			</p>
			<div>
				<div class="gdpr-checkbox">
					<label class="checkbox subfield" for="gdpr_email">
						<input type="checkbox" id="gdpr_email" name="gdpr[<?php echo esc_attr( Sensei_Email_Signup_Form::GDPR_EMAIL_FIELD_ID ); ?>]" value="Y" class="av-checkbox ">
						<span><?php esc_html_e( 'Yes, please send me occasional emails about Sensei LMS', 'sensei-lms' ); ?></span>
					</label>
				</div>
			</div>
			<div class="email-input">
				<div class="mc-field-group">
					<input type="email" value="<?php echo esc_attr( get_option( 'admin_email', '' ) ); ?>" name="EMAIL" class="required email" id="mce-EMAIL" placeholder="<?php esc_attr_e( 'Enter your email address', 'sensei-lms' ); ?>">
				</div>
				<div class="gdpr-content">
					<p>
						<?php
						echo sprintf(
							wp_kses_post(
								// translators: placeholder is the URL to MailChimp's Legal page.
								__(
									"We use Mailchimp as our marketing platform. By clicking below to subscribe, you acknowledge that your information will be transferred to Mailchimp for processing. <a href=\"%s\" target=\"_blank\">Learn more about Mailchimp's privacy practices here.</a>",
									'sensei-lms'
								)
							),
							'https://mailchimp.com/legal/'
						);
						?>
					</p>
				</div>
			</div>
			<div class="buttons clear">
				<a href="#close" id="mc-embedded-cancel" class="button" rel="modal:close"><?php esc_html_e( 'Not Now', 'sensei-lms' ); ?></a>
				<input type="submit" value="<?php esc_attr_e( 'Yes, please!', 'sensei-lms' ); ?>" name="subscribe" id="mc-embedded-subscribe" class="button-primary" disabled>
			</div>
		</div>
	</form>
</div>

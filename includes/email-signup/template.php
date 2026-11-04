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
		action="<?php echo esc_url( Sensei_Setup_Wizard::NEWSLETTER_URL ); ?>"
		method="post"
		id="mc-embedded-subscribe-form"
		name="mc-embedded-subscribe-form"
		class="validate"
		target="_blank"
		novalidate
	>
		<div id="mc_embed_signup_scroll">
			<h2><?php esc_html_e( 'Join Sensei LMS\'s Mailing List!', 'sensei-lms' ); ?></h2>
			<p>
				<?php esc_html_e( "We're here for you — get tips, product updates, and inspiration straight to your mailbox.", 'sensei-lms' ); ?>
			</p>
			<div>
				<div class="gdpr-checkbox">
					<label class="checkbox subfield" for="gdpr_email">
						<input type="checkbox" id="gdpr_email" name="consent" value="1" class="av-checkbox " required>
						<span><?php esc_html_e( 'Yes, please send me occasional emails about Sensei LMS', 'sensei-lms' ); ?></span>
					</label>
				</div>
			</div>
			<div class="email-input">
				<div class="mc-field-group">
					<input type="email" value="<?php echo esc_attr( get_option( 'admin_email', '' ) ); ?>" name="email" class="required email" id="mce-EMAIL" placeholder="<?php esc_attr_e( 'Enter your email address', 'sensei-lms' ); ?>">
				</div>
				<div class="gdpr-content">
					<p>
						<?php
						echo sprintf(
							wp_kses_post(
								// translators: placeholder is the URL to Automattic's privacy policy.
								__(
									'We use MailPoet to send our newsletter. By subscribing, you agree to receive emails about Sensei LMS. <a href="%s" target="_blank">Read our privacy policy.</a>',
									'sensei-lms'
								)
							),
							'https://automattic.com/privacy/'
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

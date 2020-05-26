<?php
/**
 * File containing the PHP template for the deactivation survey form.
 *
 * @package sensei
 * @since   3.0.2
 */

?>
<div id="sensei_deactivation_form_wrapper">
	<!--
	WORK IN PROGRESS. TBD:
	- Action URL
	- Modal Title
	- Data to send
	- Format (color, arrangement, etc)
	-->
	<form
		action="https://SENSEI_URL_WITH_ENDPOINT_FOR_DEACTIVATION_SURVEY_FORM"
		method="post"
		id="sensei_deactivation_form"
		name="sensei_deactivation_form"
		class="validate"
		target="_blank"
		novalidate
	>

		<input type="hidden" name="ADMIN_EMAIL" value="<?php echo esc_attr( get_option( 'admin_email', '' ) ); ?>">
		<input type="hidden" name="SITE_URL" value="<?php echo esc_attr( get_option( 'siteurl', '' ) ); ?>">

		<div id="sensei_deactivation_form_scroll">
			<h2><?php esc_html_e( 'Sensei LMS Feedback', 'sensei-lms' ); ?></h2>
			<p>
				<?php esc_html_e( 'Please share why you are deactivating Sensei LMS.', 'sensei-lms' ); ?>
			</p>

			<!--
			WORK IN PROGRESS:
			- Data to send (checkboxes, textfields, etc...)
			-->

			<div class="buttons clear">
				<a href="#close" id="sensei_deactivation_form_cancel" class="button" rel="modal:close"><?php esc_html_e( 'Skip and Continue', 'sensei-lms' ); ?></a>
				<input type="submit" value="<?php esc_attr_e( 'Submit and Continue', 'sensei-lms' ); ?>" name="send_survey" id="sensei_deactivation_form_send_survey" class="button-primary">
			</div>
		</div>
	</form>
</div>

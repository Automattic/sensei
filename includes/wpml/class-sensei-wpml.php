<?php

class Sensei_WPML {

	public function __construct() {
		add_action('sensei_before_mail', array($this, 'sensei_before_mail'));
		add_action('sensei_after_sending_email', array($this, 'sensei_after_sending_email'));
	}
	
	public function sensei_before_mail($email_address) {
		do_action('wpml_switch_language_for_mailing', $email_address);
	}
	
	public function sensei_after_sending_email() {
		do_action('wpml_reset_language_after_mailing');
	}
}

<?php
class Sensei_WPML {
	public function __construct() {
		add_action('sensei_before_mail', array($this, 'sensei_before_mail'));
		add_action('sensei_after_sending_email', array($this, 'sensei_after_sending_email'));
	}
	
	public function sensei_before_mail($email_address) {
		/**
		* Switch language for email
		*
		* Allows WPML to switch current language to one preferred by email recipient. 
		* WPML checks language set in user preferences and applies it for email 
		* string localisation
		* It runs before any email string is obtained and localised
		*
		* @since 1.9.7
		*
		* @param string  $email_address Recipient's email address 
		*/
		do_action('wpml_switch_language_for_email', $email_address);
	}
	
	public function sensei_after_sending_email() {
		/**
		* Restore language after sending email
		*
		* Allows WPML to switch language to the last one before switching with 
		* action 'wpml_switch_language_for_email'
		* It runs just after wp_mail() call
		* No params
		*
		* @since 1.9.7
		*/
		do_action('wpml_restore_language_from_email');
	}
}
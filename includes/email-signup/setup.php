<?php

// Add actions for displaying the email signup modal.
add_action( 'admin_enqueue_scripts', 'sensei_email_signup_enqueue_scripts' );
add_action( 'admin_footer', 'sensei_email_signup_output_modal' );

function sensei_email_signup_enqueue_scripts() {
	wp_enqueue_script( 'sensei-modal-js',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js',
			false,
			'2.0.0',
			false );
	wp_enqueue_style( 'sensei-modal-css',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css',
			false,
			'2.0.0',
			false );
}

function sensei_email_signup_output_modal() {
	include dirname( __FILE__ ) . '/template.php';
}

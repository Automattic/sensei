<?php

function sensei_course_theme_register() {
	//wp_enqueue_style( 'sensei-course-theme-twentytwentyone', Sensei()->plugin_url . 'themes/sensei-course-theme/twentytwentyone.css' );

}

add_action( 'wp_enqueue_scripts', 'sensei_course_theme_register' );

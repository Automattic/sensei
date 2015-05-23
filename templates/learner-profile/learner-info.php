<?php
/**
 * The Template for displaying the learner profile page data.
 *
 * Override this template by copying it to yourtheme/sensei/learner-profile/learner-info.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $current_user, $wp_query, $learner_user;

// Get User Meta
get_currentuserinfo();

do_action( 'sensei_complete_course' );

	?>
	<article class="post">
		<section id="learner-info" class="learner-info entry fix">
			<?php

			do_action( 'sensei_frontend_messages' );

			do_action( 'sensei_learner_profile_info', $learner_user );

			if( isset( $woothemes_sensei->settings->settings[ 'learner_profile_show_courses' ] ) && $woothemes_sensei->settings->settings[ 'learner_profile_show_courses' ] ) {

				$manage = ( $learner_user->ID == $current_user->ID ) ? true : false;

				do_action( 'sensei_before_learner_course_content', $learner_user );

				echo Sensei()->course->load_user_courses_content( $learner_user, $manage );

				do_action( 'sensei_after_learner_course_content', $learner_user );
			}

			?>
		</section>
	</article>
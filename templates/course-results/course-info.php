<?php
/**
 * The Template for displaying the course results page data.
 *
 * Override this template by copying it to yourtheme/sensei/course-results/course-info.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $course, $current_user, $wp_query;

$course = get_page_by_path( $wp_query->query_vars['course_results'], OBJECT, 'course' );

// Get User Meta
get_currentuserinfo();

	?>
	<article <?php post_class( array( 'course', 'post' ) ); ?>>
		<section class="entry fix">
			<?php

			do_action( 'sensei_frontend_messages' );

			do_action( 'sensei_course_results_info' );

			?>
		</section>
	</article>
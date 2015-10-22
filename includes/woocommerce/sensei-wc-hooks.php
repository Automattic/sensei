<?php
/**
 * Hooks linking WooCommerce functionality into Sensei
 */

/**
 * show the WooCommerce course filter links above the courses
 * @since 1.9.0
 */
add_filter( 'sensei_archive_course_filter_by_options', array( 'Sensei_WC', 'add_course_archive_wc_filter_links' ) );

/**
 * filter the queries for paid and free course based on the users selection.
 * @since 1.9.0
 */
add_filter('pre_get_posts', array( 'Sensei_WC', 'course_archive_wc_filter_free'));
add_filter('pre_get_posts', array( 'Sensei_WC', 'course_archive_wc_filter_paid'));

/**
 * Add woocommerce action above single course the action
 * @since 1.9.0
 */
add_action('sensei_before_main_content', array('Sensei_WC', 'do_single_course_wc_single_product_action') ,50) ;

/***********************
 *
 * Single Lesson Hooks
 *
 ***********************/
add_filter( 'sensei_can_user_view_lesson', array( 'Sensei_WC','alter_can_user_view_lesson' ), 20, 3 );

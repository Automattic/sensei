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


/******************************
 *
 * Single Lesson Hooks
 *
 ******************************/
add_filter( 'sensei_can_user_view_lesson', array( 'Sensei_WC','alter_can_user_view_lesson' ), 20, 3 );

/**
 * Before my courses
 */
add_filter( 'pre_get_posts', array( 'Sensei_WC','assign_user_to_unassigned_purchased_courses' ) );


/******************************
 *
 * Order and checkout hooks
 *
 ******************************/
// add a notice on the checkout page to tell users about the course they've purchase
add_action( 'template_redirect', array( 'Sensei_WC','course_link_from_order' ) );

/******************************
 *
 * Single Course
 *
 ******************************/
//@since 1.9.0
//show a notice if the user has already added the current course to their cart
add_action( 'sensei_single_course_content_inside_before', array( 'Sensei_WC', 'course_in_cart_message' ), 20 );

/******************************
 *
 * No Permissions Template
 *
 ******************************/
//@since 1.9.0
// alter the no permissions message to show the woocommerce message instead
add_filter( 'sensei_the_no_permissions_message', array( 'Sensei_WC', 'alter_no_permissions_message' ), 20, 2 );

// @since 1.9.0
// add  woocommerce class to the the no permission body class to ensure WooCommerce elements are styled
add_filter( 'body_class', array( 'Sensei_WC', 'add_woocommerce_body_class' ), 20, 1);


/************************************
 *
 * Emails
 *
 ************************************/
// Add Email link to course orders
add_action( 'woocommerce_email_after_order_table', array( 'Sensei_WC', 'email_course_details' ), 10, 1 );

/************************************
 *
 * Checkout
 *
 ************************************/
add_action( 'woocommerce_order_status_completed',     array( 'Sensei_WC', 'complete_order' ) );
add_action( 'woocommerce_order_status_processing',     array( 'Sensei_WC', 'complete_order' ) );

add_action( 'woocommerce_order_status_cancelled',     array( 'Sensei_WC', 'cancel_order' ) );
// Disable guest checkout if a course is in the cart as we need a valid user to store data for
add_filter( 'pre_option_woocommerce_enable_guest_checkout', array( 'Sensei_WC', 'disable_guest_checkout' ) );
// Mark orders with virtual products as complete rather then stay processing
add_filter( 'woocommerce_payment_complete_order_status',    array( 'Sensei_WC', 'virtual_order_payment_complete' ), 10, 2 );

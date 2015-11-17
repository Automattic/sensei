<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check, don't load file outside WP

/**
 * Sensei WooCommerce class
 *
 * All functions needed to integrate Sensei and WooCommerce
 *
 * @package Sensei
 * @category WooCommerce
 * @since 1.9.0
 */

Class Sensei_WC{
    /**
     * Load the files needed for the woocommerce integration.
     *
     * @since 1.9.0
     */
    public static function load_woocommerce_integration_hooks(){

        require_once( 'sensei-wc-hooks.php' );

    }
    /**
     * check if WooCommerce plugin is loaded
     *
     * @since 1.9.0
     * @return bool
     */
    public static function is_woocommerce_active(){

        $active_plugins = (array) get_option( 'active_plugins', array() );

        if ( is_multisite() ){

            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

        }

        return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );

    } // end is_woocommerce_active

    /**
     * Find the order active number (completed or processing ) for a given user on a course. It will return the latest order.
     *
     * If multiple exist we will return the latest order.
     *
     * @param $user_id
     * @param $course_id
     * @return array $user_course_orders
     */
    public static function get_learner_course_active_order_id( $user_id, $course_id ){

        $course_product_id = get_post_meta( $course_id, '_course_woocommerce_product', true );

        $orders_query = new WP_Query( array(
            'post_type'   => 'shop_order',
            'posts_per_page' => -1,
            'post_status' => array( 'wc-processing', 'wc-completed' ),
            'meta_key'=> '_customer_user',
            'meta_value'=> $user_id,
        ) );

        if( $orders_query->post_count == 0 ){

            return false;

        }

        foreach( $orders_query->get_posts() as $order ){

            $order = new WC_Order( $order->ID );
            $items = $order->get_items();

            $user_orders =  array();

            foreach( $items as $item ){

                // if the product id on the order and the one given to this function
                // this order has been placed by the given user on the given course.
                $product = wc_get_product( $item['product_id'] );

                if ( $product->is_type( 'variable' )) {

                    $item_product_id = $item['variation_id'];

                } else {

                    $item_product_id =  $item['product_id'];

                }

                if( $course_product_id == $item_product_id ){

                    return $order->id;

                }


            }//end for each order item

        } // end for each order

        // if we reach this place we found no order
        return false;

    } // end get_learner_course_active_order_ids

    /**
     * Output WooCommerce specific course filters
     * Removing the paged argument
     *
     * @since 1.9.0
     * @param $filter_links
     * @return mixed
     */
    public static function add_course_archive_wc_filter_links( $filter_links ){

        $course_url = remove_query_arg('paged', WooThemes_Sensei_Utils::get_current_url() );

        $free_courses = self::get_free_courses();
        $paid_courses = self::get_paid_courses();

        if ( empty( $free_courses ) || empty( $paid_courses )  ){
            // do not show any WooCommerce filters if all courses are
            // free or if all courses are paid
            return $filter_links;

        }

        $filter_links[] = array(    'id'=>'paid' ,
                                    'url'=> add_query_arg('course_filter', 'paid', $course_url),
                                    'title'=>__( 'Paid', 'woothemes-sensei' )
        );

        $filter_links[] = array(    'id'=>'free',
                                    'url'=>add_query_arg('course_filter', 'free', $course_url),
                                    'title'=>__( 'Free', 'woothemes-sensei' )
        );

        return $filter_links;

    }// end add_course_archive_wc_filter_links

    /**
     * Apply the free filter the the course query
     * getting all course with no products or products with zero price
     *
     * hooked into pre_get_posts
     *
     * @since 1.9.0
     * @param WP_Query $query
     * @return WP_Query $query
     */
    public static function course_archive_wc_filter_free( $query ){

        if( isset( $_GET['course_filter'] ) && 'free' == $_GET['course_filter']
            && 'course' == $query->get( 'post_type') && $query->is_main_query()  ){

            // setup the course meta query
            $meta_query = self::get_free_courses_meta_query_args();

            // manipulate the query to return free courses
            $query->set('meta_query', $meta_query );

            // don't show any paid courses
            $courses = self::get_paid_courses();
            $ids = array();
            foreach( $courses as $course ){
                $ids[] = $course->ID;
            }
            $query->set( 'post__not_in', $ids );

        }// end if course_filter

        return $query;

    }// course_archive_wc_filter_free

    /**
     * Apply the paid filter to the course query on the courses page
     * will include all course with a product attached with a price
     * more than 0
     *
     * hooked into pre_get_posts
     *
     * @since 1.9.0
     * @param WP_Query $query
     * @return WP_Query $query
     */
    public static function course_archive_wc_filter_paid( $query ){

        if( isset( $_GET['course_filter'] ) && 'paid' == $_GET['course_filter']
            && 'course' == $query->get( 'post_type') && $query->is_main_query() ){

            // setup the course meta query
            $meta_query = self::get_paid_courses_meta_query_args();

            if( empty( $meta_query[0]['value'] ) ){
                $meta_query[0]['value']= '-1000'; // ensure no posts are shown
            }

            // manipulate the query to return free courses
            $query->set('meta_query', $meta_query );

        }

        return $query;

    }

    /**
     * Load the WooCommerce single product actions above
     * single courses if woocommerce is active allowing purchase
     * information and actions to be hooked from WooCommerce.
     */
    public static function do_single_course_wc_single_product_action(){

        /**
         * this hooks is documented within the WooCommerce plugin.
         */
        if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {

            do_action( 'woocommerce_before_single_product' );

        } // End If Statement

    }// end do_single_course_wc_single_product_action

    /**
     * Hooking into the single lesson page to alter the
     * user access permissions based on if they have purchased the
     * course the lesson belongs to.
     *
     * This function will only return false or the passed in user_access value.
     * It doesn't return true in order to avoid altering other options.
     *
     * @since 1.9.0
     *
     * @param $can_user_view_lesson
     * @param $lesson_id
     * @param $user_id
     * @return bool
     */
    public static function alter_can_user_view_lesson ( $can_user_view_lesson, $lesson_id, $user_id  ){

        // check if the course has a valid product attached to it
        // which the user should have purchased if they want to access
        // the current lesson
        $course_id = get_post_meta( $lesson_id , '_lesson_course', true);
        $wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
        $product = Sensei()->sensei_get_woocommerce_product_object($wc_post_id);
        if( isset ($product) && is_object($product) ){

            // valid product found
            $order_id = self::get_learner_course_active_order_id( $user_id, $course_id );

            // product has a successful order so this user may access the content
            // this function may only return false or the default
            // returning true may override other negatives which we don't want
            if( ! $order_id ){

                return false;

            }

        }

        // return the passed in value
        return $can_user_view_lesson;

    }

    /**
     * Add course link to order thank you and details pages.
     *
     * @since  1.4.5
     * @access public
     *
     * @return void
     */
    public static function course_link_from_order( ) {

        if( ! is_order_received_page() ){
            return;
        }

        $order_id = get_query_var( 'order-received' );
		$order = new WC_Order( $order_id );

		// exit early if not wc-completed or wc-processing
		if( 'wc-completed' != $order->post_status
            && 'wc-processing' != $order->post_status  ) {
            return;
        }

        $course_links = array(); // store the for links for courses purchased
		foreach ( $order->get_items() as $item ) {

            if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {

                // If item has variation_id then its a variation of the product
                $item_id = $item['variation_id'];

            } else {

                //If not its real product set its id to item_id
                $item_id = $item['product_id'];

            } // End If Statement

            $user_id = get_post_meta( $order->id, '_customer_user', true );

            if( $user_id ) {

                // Get all courses for product
                $args = Sensei_Course::get_default_query_args();
                $args['meta_query'] = array( array(
                            'key' => '_course_woocommerce_product',
                            'value' => $item_id
                        ) );
                $args['orderby'] = 'menu_order date';
                $args['order'] = 'ASC';

                // loop through courses
                $courses = get_posts( $args );
                if( $courses && count( $courses ) > 0 ) {

                    foreach( $courses as $course ) {

                        $title = $course->post_title;
                        $permalink = get_permalink( $course->ID );
                        $course_links[] .= '<a href="' . esc_url( $permalink ) . '" >' . $title . '</a> ';

                    } // end for each

                    // close the message div

                }// end if $courses check
            }
        }// end loop through orders

        // add the courses to the WooCommerce notice
        if( ! empty( $course_links) ){

            $courses_html = _nx(
                'You have purchased the following course:',
                'You have purchased the following courses:',
                count( $course_links ),
                'Purchase thank you note on Checkout page. The course link(s) will be show', 'woothemes-sensei'
            );

            foreach( $course_links as $link ){

                $courses_html .= '<li>' . $link . '</li>';

            }

            $courses_html .= ' </ul>';

            wc_add_notice( $courses_html, 'success' );
        }

	} // end course_link_order_form

    /**
     * Show the message that a user should complete
     * their purchase if the course is in the cart
     *
     * This should be used within the course loop or single course page
     *
     * @since 1.9.0
     */
    public static function course_in_cart_message(){

        global $post;

        if( self::is_course_in_cart( $post->ID ) ){ ?>

            <div class="sensei-message info">'
                <?php

                $cart_link =  '<a class="cart-complete" href="' . WC()->cart->get_checkout_url()
                              . '" title="' . __('complete purchase', 'woothemes-sensei') . '">'
                              . __('complete the purchase', 'woothemes-sensei') . '</a>';

                sprintf(  __('You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei'), $cart_link );

                ?>
            </div>
        <?php }

    } // End sensei_woocommerce_in_cart_message()

    /**
     * Checks the cart to see if a course is in the cart.
     *
     * @param $course_id
     * @return bool
     */
    public static function is_course_in_cart( $course_id ){

        $wc_post_id = absint( get_post_meta( $course_id, '_course_woocommerce_product', true ) );
        $user_course_status_id = WooThemes_Sensei_Utils::user_started_course( $course_id , get_current_user_id() );

        if ( 0 < intval( $wc_post_id ) && ! $user_course_status_id ) {

            if ( self::is_product_in_cart( $wc_post_id ) ) {

                return true;

            }

        }

        return false;

    }// is_course_in_cart

    /**
     * Check the cart to see if the product is in the cart
     *
     * @param $product_id
     * @return bool
     */
    public static function is_product_in_cart( $product_id ){

        if ( 0 < $product_id ) {

            $product = wc_get_product( $product_id );

            $parent_id = '';
            if( isset( $product->variation_id ) && 0 < intval( $product->variation_id ) ) {
                $wc_product_id = $product->parent->id;
            }
            foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {

                $cart_product = $values['data'];
                if( $product_id == $cart_product->id ) {

                    return true;

                }

            }
        } // End If Statement

        return false;

    } // end is_product_in_car

    /**
     * Get all free WooCommerce products
     *
     * @since 1.9.0
     *
     * @return array $free_products{
     *  @type int $wp_post_id
     * }
     */
    public static function get_free_product_ids(){

        return  get_posts( array(
            'post_type' => 'product',
            'posts_per_page' => '1000',
            'fields' => 'ids',
            'meta_query'=> array(
                'relation' => 'OR',
                array(
                    'key'=> '_regular_price',
                    'value' => 0,
                ),
                array(
                    'key'=> '_sale_price',
                    'value' => 0,
                ),
            ),
        ));

    }// end get free product query

    /**
     * The metat query for courses that are free
     *
     * @since 1.9.0
     * @return array $wp_meta_query_param
     */
    public static function get_free_courses_meta_query_args(){

        return array(
            'relation' => 'OR',
            array(
                'key'     => '_course_woocommerce_product',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_course_woocommerce_product',
                'value' => self::get_free_product_ids(),
                'compare' => 'IN',
            ),
        );

    }// get_free_courses_meta_query

    /**
     * The metat query for courses that are free
     *
     * @since 1.9.0
     * @return array $wp_query_meta_query_args_param
     */
    public static function get_paid_courses_meta_query_args(){

        return array(
            array(
                'key'     => '_course_woocommerce_product',
                'value' => self::get_paid_product_ids(),
                'compare' => 'IN',
            ),
        );

    }// get_free_courses_meta_query

    /**
     * The WordPress Query args
     * for paid products on sale
     *
     * @since 1.9.0
     * @return array $product_query_args
     */
    public static function get_paid_products_on_sale_query_args(){

        $args = array(
                   'post_type' 		=> 'product',
                   'posts_per_page' 		=> 1000,
                   'orderby'         	=> 'date',
                   'order'           	=> 'DESC',
                   'suppress_filters' 	=> 0
        );

        $args[ 'fields' ]     = 'ids';

        $args[ 'meta_query' ] = array(
            'relation' => 'AND',
            array(
                'key'=> '_regular_price',
                'compare' => '>',
                'value' => 0,
            ),
            array(
                'key'=> '_sale_price',
                'compare' => '>',
                'value' => 0,
            ),
        );

        return $args;

    } // get_paid_products_on_sale_query_args


    /**
     * Return the WordPress query args for
     * products not on sale but that is not a free
     *
     * @since 1.9.0
     *
     * @return array
     */
    public static function get_paid_products_not_on_sale_query_args(){

        $args = array(
            'post_type' 		=> 'product',
            'posts_per_page' 		=> 1000,
            'orderby'         	=> 'date',
            'order'           	=> 'DESC',
            'suppress_filters' 	=> 0
        );

        $args[ 'fields' ]     = 'ids';
        $args[ 'meta_query' ] = array(
            'relation' => 'AND',
            array(
                'key'=> '_regular_price',
                'compare' => '>',
                'value' => 0,
            ),
            array(
                'key'=> '_sale_price',
                'compare' => '=',
                'value' => '',
            ),
        );

        return $args;


    } // get_paid_courses_meta_query

    /**
     * Get all WooCommerce non-free product id's
     *
     * @since 1.9.0
     *
     * @return array $woocommerce_paid_product_ids
     */
    public static function get_paid_product_ids(){

        // get all the paid WooCommerce products that has regular
        // and sale price greater than 0
        // will be used later to check for course with the id as meta
        $paid_product_ids_with_sale =  get_posts( self::get_paid_products_on_sale_query_args() );

        // get all the paid WooCommerce products that has regular price
        // greater than 0 without a sale price
        // will be used later to check for course with the id as meta
        $paid_product_ids_without_sale = get_posts( self::get_paid_products_not_on_sale_query_args() );

        // combine products ID's with regular and sale price grater than zero and those without
        // sale but regular price greater than zero
        $woocommerce_paid_product_ids = array_merge( $paid_product_ids_with_sale, $paid_product_ids_without_sale );

        // if
        if( empty($woocommerce_paid_product_ids) ){
            return array( );
        }
        return $woocommerce_paid_product_ids;

    }

    /**
     * Get all free courses.
     *
     * This course that have a WC product attached
     * that has a price or sale price of zero and
     * other courses with no WooCommerce products
     * attached.
     *
     * @since 1.9.0
     *
     * @return array
     */
    public static function get_free_courses(){

        $free_course_query_args = Sensei_Course::get_default_query_args();
        $free_course_query_args[ 'meta_query' ] = self::get_free_courses_meta_query_args();

        // don't show any paid courses
        $courses = self::get_paid_courses();
        $ids = array();
        foreach( $courses as $course ){
            $ids[] = $course->ID;
        }
        $free_course_query_args[ 'post__not_in' ] =  $ids;

        return get_posts( $free_course_query_args );

    }

    /**
     * Return all products that are not free
     *
     * @since 1.9.0
     * @return array
     */
    public static function get_paid_courses(){

        $paid_course_query_args = Sensei_Course::get_default_query_args();

        $paid_course_query_args[ 'meta_query' ] = self::get_paid_courses_meta_query_args();

        if( empty( $paid_course_query_args[ 'meta_query' ][0]['value'] )   ){
            $paid_course_query_args[ 'meta_query' ][0]['value'] = '-1000'; // no courses should be returned
        }

        return get_posts(  $paid_course_query_args );
    }

}// end Sensei_WC
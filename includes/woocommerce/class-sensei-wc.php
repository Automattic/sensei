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

            // get all the free WooCommerce products
            // will be used later to check for course with the id as meta
            $woocommerce_free_product_ids = get_posts( array(
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

            // setup the course meta query
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key'     => '_course_woocommerce_product',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_course_woocommerce_product',
                    'value' => $woocommerce_free_product_ids,
                    'compare' => 'IN',
                ),
            );

            // manipulate the query to return free courses
            $query->set('meta_query', $meta_query );

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

            // get all the paid WooCommerce products that has regular
            // and sale price greater than 0
            // will be used later to check for course with the id as meta
            $paid_product_ids_with_sale = get_posts( array(
                'post_type' => 'product',
                'posts_per_page' => '1000',
                'fields' => 'ids',
                'meta_query'=> array(
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
                ),
            ));

            // get all the paid WooCommerce products that has regular price
            // greater than 0 without a sale price
            // will be used later to check for course with the id as meta
            $paid_product_ids_without_sale = get_posts( array(
                'post_type' => 'product',
                'posts_per_page' => '1000',
                'fields' => 'ids',
                'meta_query'=> array(
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
                ),
            ));

            // combine products ID's with regular and sale price grater than zero and those without
            // sale but regular price greater than zero
            $woocommerce_paid_product_ids = array_merge( $paid_product_ids_with_sale, $paid_product_ids_without_sale );

            // setup the course meta query
            $meta_query = array(
                array(
                    'key'     => '_course_woocommerce_product',
                    'value' => $woocommerce_paid_product_ids,
                    'compare' => 'IN',
                ),
            );

            // manipulate the query to return free courses
            $query->set('meta_query', $meta_query );

        }

        return $query;

    }

}// end Sensei_WC
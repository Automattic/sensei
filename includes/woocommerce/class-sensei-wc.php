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

}// end Sensei_WC
<?php

class Sensei_WC_Subscriptions {


    private static $default_subscription_types = array(
        'subscription',
        'subscription_variation',
        'variable-subscription'
    );

    public static function is_wc_subscriptions_active() {
        return Sensei_Utils::is_plugin_present_and_activated(
            'WC_Subscriptions',
            'woocommerce-subscriptions/woocommerce-subscriptions.php'
        );
    }

    /**
     * Load WC Subscriptions integration hooks if WC Subscriptions is active
     * @return void
     */
    public static function load_wc_subscriptions_integration_hooks() {
        if ( false === self::is_wc_subscriptions_active() ) {
            return;
        }

        add_action( 'woocommerce_subscription_status_pending_to_active', array( __CLASS__, 'activate_subscription' ), 50, 3 );
        // filter the user permission of the subscription is not valid
        add_filter( 'sensei_access_permissions', array( __CLASS__, 'get_subscription_permission' ), 10, 2 );
        //block user from accessing course when subscription is not valid
        add_filter( 'sensei_user_started_course', array( __CLASS__, 'get_subscription_user_started_course' ), 10, 3 );
    }

    public static function has_user_bought_subscription_but_cancelled( $course_id, $user_id ) {
        if ( !self::is_wc_subscriptions_active() ) {
            return false;
        }
        $product_id = Sensei_WC::get_course_product_id( $course_id );
        if ( empty( $product_id ) ) {
            return false;
        }

        $user_bought_subscription_but_cancelled = wcs_user_has_subscription( $user_id, $product_id, 'cancelled' );

        if ($user_bought_subscription_but_cancelled && self::is_user_eligible_for_access( $user_id, $product_id, $course_id ) ) {
            // we still need to make sure that the user has not bought this again,
            // thus having catually an active subscription too: use case user buys, user cancels, user buys again
            return false;
        }

        if ( !$user_bought_subscription_but_cancelled ) {
            return false;
        }

        // assume the user was refunded, so technically it is ok to display a buy product
        return true;
    }

    /**
     * Responds to when a subscription product is purchased
     *
     * @since  1.2.0
     * @since  1.9.0 move to class Sensei_WC
     * @since  1.9.12 move to class Sensei_WC_Subscriptions
     *
     * @param   WC_Order $order
     *
     * @return  void
     */
    public static function activate_subscription(  $order ) {

        $order_user = get_user_by('id', $order->user_id);
        $user['ID'] = $order_user->ID;
        $user['user_login'] = $order_user->user_login;
        $user['user_email'] = $order_user->user_email;
        $user['user_url'] = $order_user->user_url;

        // Run through each product ordered
        if ( ! sizeof($order->get_items() )>0 ) {

            return;

        }

        foreach($order->get_items() as $item) {

            $product_type = '';
            if (Sensei_WC_Utils::is_wc_item_variation($item)) {
                $product_type = 'subscription_variation';
            }

            $item_id = Sensei_WC_Utils::get_item_id_from_item($item);

            // Get courses that use the WC product
            $courses = array();

            if ( ! in_array( $product_type, self::get_subscription_types() ) ) {

                $courses = Sensei()->course->get_product_courses( $item_id );

            } // End If Statement

            // Loop and add the user to the course.
            foreach ( $courses as $course_item ){

                Sensei_Utils::user_start_course( intval( $user['ID'] ), $course_item->ID  );

            } // End For Loop

        } // End For Loop

    }

    /**
     * Determine if the user has and active subscription to give them access
     * to the requested resource.
     *
     * @since 1.9.12
     *
     * @param  boolean$user_access_permission
     * @param  integer $user_id
     * @return boolean $user_access_permission
     */
    public static function get_subscription_permission( $user_access_permission , $user_id ) {

        global $post;

        // ignore the current case if the following conditions are met
        if ( ! class_exists( 'WC_Subscriptions' ) || empty( $user_id )
            || ! in_array( $post->post_type, array( 'course','lesson','quiz' ) )
            || ! wcs_user_has_subscription( $user_id) ){

            return $user_access_permission;

        }

        // at this user has a subscription
        // is the subscription on the the current course?
        if ( 'course' == $post->post_type ){

            $course_id = $post->ID;

        } elseif ( 'lesson' == $post->post_type ) {

            $course_id = Sensei()->lesson->get_course_id( $post->ID );

        } else {

            $lesson_id =  Sensei()->quiz->get_lesson_id( $post->ID );
            $course_id = Sensei()->lesson->get_course_id( $lesson_id );

        }

        // if the course has no subscription WooCommerce product attached to return the permissions as is
        $product_id = Sensei_WC::get_course_product_id( $course_id );
        $product = wc_get_product( $product_id );
        if( ! in_array( $product->get_type(), Sensei_WC_Subscriptions::get_subscription_types()) ){

            return $user_access_permission;

        }

        if ( self::is_user_eligible_for_access( $user_id, $product_id, $course_id) ) {
            $user_access_permission = true;
        } else {

            $user_access_permission = false;
            // do not show the WC permissions message
            remove_filter( 'sensei_the_no_permissions_message', array( 'Sensei_WC', 'alter_no_permissions_message' ), 20 );
            Sensei()->permissions_message['title'] = __( 'No active subscription', 'woothemes-sensei' );
            Sensei()->permissions_message['message'] = __( 'Sorry, you do not have an access to this content without an active subscription.', 'woothemes-sensei' );
        }

        return $user_access_permission;

    } // end get_subscription_permission

    /**
     * @since 1.9.12
     *
     * @param $has_user_started_course
     * @param $course_id
     * @param $user_id
     *
     * @return bool $has_user_started_course
     */
    public static function get_subscription_user_started_course( $has_user_started_course, $course_id, $user_id ) {

        // avoid changing the filter value in the following cases
        if( empty( $course_id ) || empty( $user_id ) || ! is_user_logged_in() || is_admin()
            || isset( $_POST[ 'payment_method' ] ) || isset( $_POST['order_status']  ) ) {

            return $has_user_started_course;

        }

        // cached user course access for this process instance
        // also using temp cached data so we don't output the message again
        global $sensei_wc_subscription_access_store;

        if ( ! is_array( $sensei_wc_subscription_access_store ) ) {
            $sensei_wc_subscription_access_store = array();
        }

        $user_data_index_key = $course_id .'_' . $user_id;
        if ( isset( $sensei_wc_subscription_access_store[ $user_data_index_key  ] ) ) {
            return $sensei_wc_subscription_access_store[ $user_data_index_key ];
        }

        // if the course has no subscription WooCommerce product attached to return the permissions as is
        $product_id = Sensei_WC::get_course_product_id( $course_id );
        if ( ! $product_id ){
            return $has_user_started_course;
        }
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return $has_user_started_course;
        }

        if( ! is_object( $product ) || ! in_array( $product->get_type(), Sensei_WC_Subscriptions::get_subscription_types()) ){

            return $has_user_started_course;

        }

        if ( self::is_user_eligible_for_access( $user_id, $product_id, $course_id ) ) {

            $has_user_started_course = true;

        } else {
            $is_subscription_cancelled = wcs_user_has_subscription( $user_id, $product_id, 'cancelled' );
            if ( $is_subscription_cancelled ) {
//                $course_status =  Sensei_Utils::user_course_status( $course_id, $user_id );
                remove_filter( 'sensei_user_started_course', array( __CLASS__, 'get_subscription_user_started_course' ), 10 );
                if ( Sensei_Utils::user_started_course( $course_id, $user_id ) ) {
                    Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );
//					$product->
//                    $user_order = Sensei_WC::get_user_product_orders( $user_id, $product_id );
                }
                add_filter( 'sensei_user_started_course', array( __CLASS__, 'get_subscription_user_started_course' ), 10, 3 );
            }
            $has_user_started_course = false;

        }

        $sensei_wc_subscription_access_store[ $user_data_index_key ] = $has_user_started_course;
        return $has_user_started_course;
    }

    /**
     * Compare the user's subscriptions end date with the date
     * the user was added to the course. If the user was added after
     * the subscription ended they were manually added and this will return
     * true.
     *
     * Important to note that all subscriptions for the user is compared.
     *
     * @since 1.9.0
     *
     * @param $user_id
     * @param $product_id
     * @param $course_id
     *
     * @return bool
     */
    public static function was_user_added_without_subscription( $user_id, $product_id, $course_id ) {
        $was_user_added_without_subscription = false;

        // if user is not on the course they were not added
        remove_filter( 'sensei_user_started_course', array( __CLASS__, 'get_subscription_user_started_course' ), 10 );
        if( ! Sensei_Utils::user_started_course( $course_id, $user_id ) ){

            return false;

        }

        // if user doesn't have a subscription and is taking the course
        // they were added manually
        if ( ! wcs_user_has_subscription($user_id, $product_id)
            && Sensei_Utils::user_started_course( $course_id, get_current_user_id() )  ){

            return true;

        }

        add_filter( 'sensei_user_started_course',     array( 'Sensei_WC_Subscriptions', 'get_subscription_user_started_course' ), 10, 3 );

        $course_status =  Sensei_Utils::user_course_status( $course_id, $user_id );

        // comparing dates setup data
        $course_start_date = date_create( $course_status->comment_date );
        $subscriptions = wcs_get_users_subscriptions( $user_id );

        // comparing every subscription
        foreach( $subscriptions as $subscription ) {

            // for the following statuses we know the user was not added
            // manually
            $status = $subscription->get_status();
            if ( in_array( $status, array( 'pending-canceled', 'active', 'on-hold', 'pending' ) ) ) {

                continue;

            }

            $current_subscription_start_date = date_create( $subscription->modified_date );

            // is the last updated subscription date newer than course start date
            if (  $current_subscription_start_date > $course_start_date   ) {

                return false;

            }

        }

        return $was_user_added_without_subscription;
    }

    /**
     * Get all the valid subscription types.
     *
     * @since 1.9.0
     * @return array
     */
    public static function get_subscription_types() {
        return apply_filters('sensei_wc_subscriptions_get_subscription_types', self::$default_subscription_types);

    }

    /**
     * give access if user has active subscription on the product otherwise restrict it.
     * also check if the user was added to the course directly after the subscription started.
     * @param $user_id
     * @param $product_id
     * @param $course_id
     * @return bool
     */
    private static function is_user_eligible_for_access($user_id, $product_id, $course_id)
    {

        return wcs_user_has_subscription($user_id, $product_id, 'active')
        || wcs_user_has_subscription($user_id, $product_id, 'pending-cancel')
        || self::was_user_added_without_subscription($user_id, $product_id, $course_id);
    }
}
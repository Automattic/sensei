<?php
if ( ! defined( 'ABSPATH' ) ) exit; // security check, don't load file outside WP

/**
 * Sensei WooCommerce class
 *
 * All functions needed to integrate Sensei and WooCommerce
 *
 * @package Access-Management
 * @author Automattic
 * @since 1.9.0
 */

Class Sensei_WC{

	/**
	 * Load the files needed for the woocommerce integration.
	 *
	 * @since 1.9.0
	 */
	public static function load_woocommerce_integration_hooks(){

		if( ! Sensei_WC::is_woocommerce_active() ){
			return;
		}

		$woocommerce_hooks_file_path = Sensei()->plugin_path() . 'includes/hooks/woocommerce.php';
		require_once( $woocommerce_hooks_file_path );

	}
	/**
	 * check if WooCommerce plugin is loaded and allowed by Sensei
	 *
	 * @since 1.9.0
	 * @return bool
	 */
	public static function is_woocommerce_active(){

		$is_woocommerce_enabled_in_settings = isset( Sensei()->settings->settings['woocommerce_enabled'] ) && Sensei()->settings->settings['woocommerce_enabled'];
		return self::is_woocommerce_present() && $is_woocommerce_enabled_in_settings;

	} // end is_woocommerce_active

	/**
	 * Checks if the WooCommerce plugin is installed and activation.
	 *
	 * If you need to check if WooCommerce is activated use Sensei_Utils::is_woocommerce_active().
	 * This function does nott check to see if the Sensei setting for WooCommerce is enabled.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public static function is_woocommerce_present(){

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ){

			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

		}

		$is_woocommerce_plugin_present_and_activated = in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );

		return class_exists( 'Woocommerce' ) || $is_woocommerce_plugin_present_and_activated;

	}// end is_woocommerce_present

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

				if ( is_object( $product ) && $product->is_type( 'variable' )) {

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

		$free_courses = self::get_free_courses();
		$paid_courses = self::get_paid_courses();

		if ( empty( $free_courses ) || empty( $paid_courses )  ){
			// do not show any WooCommerce filters if all courses are
			// free or if all courses are paid
			return $filter_links;

		}

		$filter_links[] = array(
			'id'=>'paid' ,
			'url'=> add_query_arg( array( 'course_filter'=>'paid'), Sensei_Course::get_courses_page_url() ),
			'title'=>__( 'Paid', 'woothemes-sensei' )
		);

		$filter_links[] = array(
			'id'=>'free',
			'url'=> add_query_arg( array( 'course_filter'=>'free'), Sensei_Course::get_courses_page_url() ),
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
		if ( Sensei_WC::is_woocommerce_active() ) {

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

		// do not override access to admins
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		if ( sensei_all_access() || Sensei_Utils::is_preview_lesson( $lesson_id )
			 || Sensei_Utils::user_started_course( $course_id, $user_id )  ){

			return $can_user_view_lesson;

		}

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

			<div class="sensei-message info">
				<?php

				$cart_link =  '<a class="cart-complete" href="' . WC()->cart->get_checkout_url()
							  . '" title="' . __('complete purchase', 'woothemes-sensei') . '">'
							  . __('complete the purchase', 'woothemes-sensei') . '</a>';

				echo sprintf(  __('You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei'), $cart_link );

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
		$user_course_status_id = Sensei_Utils::user_started_course( $course_id , get_current_user_id() );

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
				'value' => '-',
				'compare' => '=',
			),
			array(
				'key'     => '_course_woocommerce_product',
				'value' => self::get_paid_product_ids(),
				'compare' => 'NOT IN',
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

		$paid_product_ids = self::get_paid_product_ids();

		return array(
			array(
				'key'     => '_course_woocommerce_product',
				// when empty we give a false post_id to ensure the caller doesn't get any courses for their
				// query
				'value' => empty( $paid_product_ids  )? '-1000' : $paid_product_ids,
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
			'posts_per_page' 	=> 1000,
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

		// get variable subscriptions (normal subscriptions are already included with paid products)
		if ( class_exists( 'WC_Subscriptions_Manager' ) ) {

			$variable_subscription_query_args = self::get_paid_products_not_on_sale_query_args();

			$variable_subscription_query_args[ 'meta_query' ] = array(
				array(
					'key'=> '_subscription_sign_up_fee',
					'compare' => 'EXISTS',
				),
			);

			$paid_product_ids_without_sale = array_merge( $paid_product_ids_without_sale, get_posts( $variable_subscription_query_args )  );

		}

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
	 * @param array $args
	 * @return array
	 */
	public static function get_free_courses( $args = array() ){

		$free_course_query_args = Sensei_Course::get_default_query_args();
		$free_course_query_args[ 'meta_query' ] = self::get_free_courses_meta_query_args();

		if( !empty( $args ) ){
			wp_parse_args( $args, $free_course_query_args  );
		}

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
	 * @param array $args override default arg values
	 *
	 * @return array
	 */
	public static function get_paid_courses( $args = array() ){

		$paid_course_query_args = Sensei_Course::get_default_query_args();

		$paid_course_query_args[ 'meta_query' ] = self::get_paid_courses_meta_query_args();

		if( !empty( $args ) ){
			wp_parse_args( $args, $paid_course_query_args  );
		}

		return get_posts(  $paid_course_query_args );
	}

	/**
	 * Show the WooCommerce add to cart button for the  current course
	 *
	 * The function will only show the button if
	 * 1- the user can buy the course
	 * 2- if they have completed their pre-requisite
	 * 3- if the course has a valid product attached
	 *
	 * @since 1.9.0
	 * @param int $course_id
	 * @return string $html markup for the button or nothing if user not allowed to buy
	 */
	public static function the_add_to_cart_button_html( $course_id ){

		if ( ! Sensei_Course::is_prerequisite_complete( $course_id ) || self::is_course_in_cart( $course_id ) ) {
			return '';
		}

		$wc_post_id = self::get_course_product_id( $course_id );

		// Check if customer purchased the product
		if ( self::has_customer_bought_product(  get_current_user_id(), $wc_post_id )
			|| empty( $wc_post_id ) ) {

			return '';

		}

		// based on simple.php in WC templates/single-product/add-to-cart/
		// Get the product
		$product = self::get_product_object( $wc_post_id );

		// do not show the button for invalid products, non purchasable products, out
		// of stock product or if course is already in cart
		if ( ! isset ( $product )
			|| ! is_object( $product )
			|| ! $product->is_purchasable()
			|| ! $product->is_in_stock()
			|| self::is_course_in_cart( $wc_post_id ) ) {

			return '';

		}

		//
		// button  output:
		//
		?>

		<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
			  class="cart"
			  method="post"
			  enctype="multipart/form-data">

			<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ); ?>" />

			<input type="hidden" name="quantity" value="1" />

			<?php if ( isset( $product->variation_id ) && 0 < intval( $product->variation_id ) ) { ?>

				<input type="hidden" name="variation_id" value="<?php echo $product->variation_id; ?>" />
				<?php if( isset( $product->variation_data ) && is_array( $product->variation_data ) && count( $product->variation_data ) > 0 ) { ?>

					<?php foreach( $product->variation_data as $att => $val ) { ?>

						<input type="hidden" name="<?php echo esc_attr( $att ); ?>" id="<?php echo esc_attr( str_replace( 'attribute_', '', $att ) ); ?>" value="<?php echo esc_attr( $val ); ?>" />

					<?php } ?>

				<?php } ?>

			<?php } ?>

			<button type="submit" class="single_add_to_cart_button button alt">
				<?php $button_text = $product->get_price_html() . ' - ' . __( 'Purchase this Course', 'woothemes-sensei' ); ?>
				<?php
				/**
				 * Filter Add to Cart button text
				 *
				 * @since 1.9.1
				 *
				 * @param string $button_text
				 */
				echo apply_filters( 'sensei_wc_single_add_to_cart_button_text', $button_text );
				?>
			</button>

		</form>

		<?php
	} // end the_add_to_cart_button_html

	/**
	 * Alter the no permissions message on the single course page
	 * Changes the message to a WooCommerce specific message.
	 *
	 * @since 1.9.0
	 *
	 * @param $message
	 * @param $post_id
	 *
	 * @return string $message
	 */
	public static function alter_no_permissions_message( $message, $post_id ){

		if( empty( $post_id ) || 'course'!=get_post_type( $post_id ) ){
			return  $message;
		}

		$product_id = self::get_course_product_id( $post_id );

		if( ! $product_id
			|| ! self::has_customer_bought_product( get_current_user_id(),$product_id ) ){

			return $message;

		}

		ob_start();
		self::the_course_no_permissions_message( $post_id );
		$woocommerce_course_no_permissions_message = ob_get_clean();

		return $woocommerce_course_no_permissions_message ;

	}
	/**
	 * Show the no permissions message when a user is logged in
	 * and have not yet purchased the current course
	 *
	 * @since 1.9.0
	 */
	public static function the_course_no_permissions_message( $course_id ){

		// login link
		$my_courses_page_id = intval( Sensei()->settings->settings[ 'my_course_page' ] );
		$login_link =  '<a href="' . esc_url( get_permalink( $my_courses_page_id ) ) . '">' . __( 'log in', 'woothemes-sensei' ) . '</a>';
		$wc_product_id =  self::get_course_product_id( $course_id );

		if ( self::is_product_in_cart( $wc_product_id ) ) {

			$cart_link = '<a href="' . wc_get_checkout_url() . '" title="' . __( 'Checkout','woocommerce' ) . '">' . __( 'checkout', 'woocommerce' ) . '</a>';

			$message = sprintf( __( 'This course is already in your cart, please proceed to %1$s, to gain access.', 'woothemes-sensei' ), $cart_link );
			?>
			<span class="add-to-cart-login">
					<?php echo $message; ?>
				</span>

			<?php

		} elseif ( is_user_logged_in() ) {

			?>
			<style>
				.sensei-message.alert {
					display: none;
				}
			</style>

			<?php

		} else {
			$message = sprintf( __( 'Or %1$s to access your purchased courses', 'woothemes-sensei' ), $login_link );
			?>
				<span class="add-to-cart-login">
					<?php echo $message; ?>
				</span>

			<?php
		}
	}

	/**
	 * Checks if a user has bought a product item.
	 *
	 * @since  1.9.0
	 *
	 * @param  int $user_id
	 * @param  int $product_id
	 *
	 * @return bool
	 */
	public static function has_customer_bought_product ( $user_id, $product_id ) {

		$product = wc_get_product( $product_id );

		// get variations parent
		if ( 'variation' == $product->get_type()  ) {

			$product_id = $product->parent->get_id();

		}

		$orders = self::get_user_product_orders( $user_id, $product_id );

		foreach ( $orders as $order_id ) {

			$order = new WC_Order( $order_id->ID );

			// wc-active is the subscriptions complete status
			if ( ! in_array( $order->post_status, array( 'wc-processing', 'wc-completed' ) )
				|| ! ( 0 < sizeof( $order->get_items() ) )  ){

				continue;

			}

			foreach( $order->get_items() as $item ) {

				// Check if user has bought product
				if ( $item['product_id'] == $product_id || $item['variation_id'] == $product_id ) {

					// Check if user has an active subscription for product
					if( class_exists( 'WC_Subscriptions_Manager' ) ) {
						$sub_key = wcs_get_subscription( $order );
						if( $sub_key ) {
							$sub = wcs_get_subscription( $sub_key );
							if( $sub && isset( $sub['status'] ) ) {
								if( 'active' == $sub['status'] ) {
									return true;
								} else {
									return false;
								}
							}
						}
					}

					// Customer has bought product
					return true;
				} // End If Statement

			} // End For each item

		} // End For each order

		// default is no order
		return false;

	} // end has customer bought product

	/**
	 * Return the product id for the given course
	 *
	 * @since 1.9.0
	 *
	 * @param int $course_id
	 *
	 * @return string $woocommerce_product_id or false if none exist
	 *
	 */
	public static function get_course_product_id( $course_id ){

		$product_id =  get_post_meta( $course_id, '_course_woocommerce_product', true );

		if( empty( $product_id ) ){
			return false;
		}

		$product = wc_get_product( $product_id );

		if( ! $product ){
			return false;
		}

		// handle variations
		if ( isset(  $product->variation_id ) ) {

			return $product->variation_id;

		}

		return $product->get_id();

	}

	/**
	 * Alter the body classes adding WooCommerce to the body
	 *
	 * Speciall cases where this is needed is template no-permissions.php
	 *
	 * @param array $classes
	 * @return array
	 */
	public static function add_woocommerce_body_class( $classes ){

		if( ! in_array( 'woocommerce', $classes ) && defined( 'SENSEI_NO_PERMISSION' ) && SENSEI_NO_PERMISSION ){

			$classes[] ='woocommerce';

		}

		return $classes;

	}

	/**
	 * Responds to when a subscription product is purchased
	 *
	 * @since   1.2.0
	 * @since  1.9.0 move to class Sensei_WC
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

			if (isset($item['variation_id']) && $item['variation_id'] > 0) {

				$item_id = $item['variation_id'];
				$product_type = 'subscription_variation';

			} else {

				$item_id = $item['product_id'];

			} // End If Statement

			$_product = self::get_product_object( $item_id, $product_type );

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

	} // End activate_subscription()

	/**
	 * Adds detail to to the WooCommerce order
	 *
	 * @since   1.4.5
	 * @since 1.9.0 function moved to class Sensei_WC and renamed from sensei_woocommerce_email_course_details to email_course_details
	 *
	 * @param   WC_Order $order
	 *
	 * @return  void
	 */
	public static function email_course_details(  $order ){

		global $woocommerce;

		// exit early if not wc-completed or wc-processing
		if( 'wc-completed' != $order->post_status
			&& 'wc-processing' != $order->post_status  ) {
			return;
		}

		$order_items = $order->get_items();
		$order_id = $order->id;

		//If object have items go through them all to find course
		if ( 0 < sizeof( $order_items ) ) {

			$course_details_html =  '<h2>' . __( 'Course details', 'woothemes-sensei' ) . '</h2>';
			$order_contains_courses = false;


			foreach ( $order_items as $item ) {

				$product_type = '';
				if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {
					// If item has variation_id then its from variation
					$item_id = $item['variation_id'];
					$product_type = 'variation';
				} else {
					// If not its real product set its id to item_id
					$item_id = $item['product_id'];
				} // End If Statement

				$user_id = get_post_meta( $order_id, '_customer_user', true );

				if( $user_id ) {

					// Get all courses for product
					$args = array(
						'posts_per_page' => -1,
						'post_type' => 'course',
						'meta_query' => array(
							array(
								'key' => '_course_woocommerce_product',
								'value' => $item_id
							)
						),
						'orderby' => 'menu_order date',
						'order' => 'ASC',
					);
					$courses = get_posts( $args );

					if( $courses && count( $courses ) > 0 ) {

						foreach( $courses as $course ) {

							$title = $course->post_title;
							$permalink = get_permalink( $course->ID );
							$order_contains_courses = true;
							$course_details_html .=  '<p><strong>' . sprintf( __( 'View course: %1$s', 'woothemes-sensei' ), '</strong><a href="' . esc_url( $permalink ) . '">' . $title . '</a>' ) . '</p>';
						}


					} // end if has courses

				} // end if $userPid

			} // end for each order item

			// Output Course details
			if( $order_contains_courses ){

				echo $course_details_html;

			}


		} // end if  order items not empty

	}// end email_course_details

	/**
	 * sensei_woocommerce_complete_order description
	 * @since   1.0.3
	 * @access  public
	 * @param   int $order_id WC order ID
	 * @return  void
	 */
	public static function complete_order ( $order_id = 0 ) {

		$order_user = array();

		// Check for WooCommerce
		if ( ! Sensei_WC::is_woocommerce_active() || empty( $order_id ) ) {
			return;
		}
		// Get order object
		$order = new WC_Order( $order_id );

		if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) {
			return;
		}

		$user = get_user_by( 'id', $order->get_user_id() );
		$order_user['ID'] = $user->ID;
		$order_user['user_login'] = $user->user_login;
		$order_user['user_email'] = $user->user_email;
		$order_user['user_url'] = $user->user_url;

		if ( 0 == sizeof( $order->get_items() ) ) {
			return;
		}

		// Run through each product ordered
		foreach( $order->get_items() as $item ) {

			$product_type = '';
			if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {

				$item_id = $item['variation_id'];
				$product_type = 'variation';

			} else {

				$item_id = $item['product_id'];

			} // End If Statement

			$_product = Sensei_WC::get_product_object( $item_id, $product_type );

			// Get courses that use the WC product
			$courses = Sensei()->course->get_product_courses( $_product->id );

			// Loop and update those courses
			foreach ( $courses as $course_item ) {

				$update_course = self::course_update( $course_item->ID, $order_user );

			} // End For Loop

		} // End For Loop
		// Add meta to indicate that payment has been completed successfully
		update_post_meta( $order_id, 'sensei_payment_complete', '1' );

	} // End sensei_woocommerce_complete_order()

	/**
	 * Responds to when an order is cancelled.
	 *
	 * @since   1.2.0
	 * @since   1.9.0 Move function to the Sensei_WC class
	 * @param   integer| WC_Order $order_id order ID
	 * @return  void
	 */
	public static function cancel_order ( $order_id ) {

		// Get order object
		if( is_object( $order_id ) ){

			$order = $order_id;

		}else{

			$order = new WC_Order( $order_id );
		}

		if ( ! in_array( $order->get_status(), array( 'cancelled', 'refunded' ) ) ) {

			return;

		}

		// Run through each product ordered
		if ( 0 < sizeof( $order->get_items() ) ) {

			// Get order user
			$user_id = $order->__get( 'user_id' );

			foreach( $order->get_items() as $item ) {

				$product_type = '';
				if ( isset( $item['variation_id'] ) && ( 0 < $item['variation_id'] ) ) {

					$item_id = $item['variation_id'];
					$product_type = 'variation';

				} else {

					$item_id = $item['product_id'];

				} // End If Statement

				$_product = Sensei_WC::get_product_object( $item_id, $product_type );

				// Get courses that use the WC product
				$courses = array();
				$courses = Sensei()->course->get_product_courses( $item_id );

				// Loop and update those courses
				foreach ($courses as $course_item){

					if( self::has_customer_bought_product( $user_id, $course_item->ID ) ){
						continue;
					}
					// Check and Remove course from courses user meta
					$dataset_changes = Sensei_Utils::sensei_remove_user_from_course( $course_item->ID, $user_id );

				} // End For Loop

			} // End For Loop

		} // End If Statement

	} // End sensei_woocommerce_cancel_order()

	/**
	 * Returns the WooCommerce Product Object
	 *
	 * The code caters for pre and post WooCommerce 2.2 installations.
	 *
	 * @since   1.1.1
	 * @access  public
	 * @param   integer $wc_product_id Product ID or Variation ID
	 * @param   string  $product_type  '' or 'variation'
	 * @return   WC_Product $wc_product_object
	 */
	public static function get_product_object ( $wc_product_id = 0, $product_type = '' ) {

		$wc_product_object = false;
		if ( 0 < intval( $wc_product_id ) ) {

			// Get the product
			if ( function_exists( 'wc_get_product' ) ) {

				$wc_product_object = wc_get_product( $wc_product_id ); // Post WC 2.3

			} elseif ( function_exists( 'get_product' ) ) {

				$wc_product_object = get_product( $wc_product_id ); // Post WC 2.0

			} else {

				// Pre WC 2.0
				if ( 'variation' == $product_type || 'subscription_variation' == $product_type ) {

					$wc_product_object = new WC_Product_Variation( $wc_product_id );

				} else {

					$wc_product_object = new WC_Product( $wc_product_id );

				} // End If Statement

			} // End If Statement

		} // End If Statement

		return $wc_product_object;

	} // End sensei_get_woocommerce_product_object()

	/**
	 * If customer has purchased the course, update Sensei to indicate that they are taking the course.
	 *
	 * @since  1.0.0
	 * @since 1.9.0 move to class Sensei_WC
	 *
	 * @param  int 			$course_id  (default: 0)
	 * @param  array/Object $order_user (default: array()) Specific user's data.
	 *
	 * @return bool|int
	 */
	public static function course_update ( $course_id = 0, $order_user = array()  ) {

		global $current_user;
		$has_valid_user_object = isset( $current_user->ID ) || isset( $order_user['ID'] );
		if( ! $has_valid_user_object ){
			return false;
		}

		$has_valid_user_id = ! empty( $current_user->ID ) || ! empty( $order_user['ID'] );
		if ( ! $has_valid_user_id ) {
			return false;
		}

		//setup user data
		if ( is_admin() ) {

			$user_login = $order_user['user_login'];
			$user_email = $order_user['user_email'];
			$user_url = $order_user['user_url'];
			$user_id = $order_user['ID'];

		} else {

			$user_id = empty( $current_user->ID ) ? $order_user['ID'] : $current_user->ID;
			$user = get_user_by( 'id', $user_id );

			if( ! $user ) {
				return false;
			}

			$user_login = $user->user_login;
			$user_email = $user->user_email;
			$user_url   = $user->user_url;

		}

		// Get the product ID
		$wc_post_id = get_post_meta( intval( $course_id ), '_course_woocommerce_product', true );

		// This doesn't appear to be purely WooCommerce related. Should it be in a separate function?
		$course_prerequisite_id = (int) get_post_meta( $course_id, '_course_prerequisite', true );
		if( 0 < absint( $course_prerequisite_id ) ) {

			$prereq_course_complete = Sensei_Utils::user_completed_course( $course_prerequisite_id, intval( $user_id ) );
			if ( ! $prereq_course_complete ) {

				// Remove all course user meta
				return Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );

			}
		}

		$is_user_taking_course = Sensei_Utils::user_started_course( intval( $course_id ), intval( $user_id ) );
		$currently_purchasing_course = isset( $_POST['payment_method'] );

		if ( ! $is_user_taking_course
			&& 0 < $wc_post_id
			&& ( Sensei_WC::has_customer_bought_product( $user_id, $wc_post_id ) || $currently_purchasing_course )  ) {

				$activity_logged = Sensei_Utils::user_start_course( intval( $user_id ), intval( $course_id ) );

				if ( true == $activity_logged ) {

					$is_user_taking_course = true;

				} // End If Statement

		}// end if is user taking course

		return $is_user_taking_course;

	} // End course_update()

	/**
	 * Disable guest checkout if a course product is in the cart
	 *
	 * @since 1.1.0
	 * @since 1.9.0 move to class Sensei_WC
	 *
	 * @param  boolean $guest_checkout Current guest checkout setting
	 *
	 * @return boolean                 Modified guest checkout setting
	 */
	public static function disable_guest_checkout( $guest_checkout ) {

		if( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			if( isset( WC()->cart->cart_contents ) && count( WC()->cart->cart_contents ) > 0 ) {

				foreach( WC()->cart->cart_contents as $cart_key => $product ) {
					if( isset( $product['product_id'] ) ) {

						$args = array(
							'posts_per_page' => -1,
							'post_type' => 'course',
							'meta_query' => array(
								array(
									'key' => '_course_woocommerce_product',
									'value' => $product['product_id']
								)
							)
						);

						$posts = get_posts( $args );

						if( $posts && count( $posts ) > 0 ) {

							foreach( $posts as $course ) {
								$guest_checkout = '';
								break;

							}
						}

					}

				}

			}
		}

		return $guest_checkout;

	}// end disable_guest_checkout

	/**
	 * Change order status with virtual products to completed
	 *
	 * @since  1.1.0
	 * @since 1.9.0 move to class Sensei_WC
	 *
	 * @param string $order_status
	 * @param int $order_id
	 *
	 * @return string
	 **/
	public static function virtual_order_payment_complete( $order_status, $order_id ) {

		$order = new WC_Order( $order_id );

		if ( ! isset ( $order ) ) return '';

		if ( $order_status == 'wc-processing' && ( $order->post_status == 'wc-on-hold' || $order->post_status == 'wc-pending' || $order->post_status == 'wc-failed' ) ) {

			$virtual_order = true;

			if ( count( $order->get_items() ) > 0 ) {

				foreach( $order->get_items() as $item ) {

					if ( $item['product_id'] > 0 ) {
						$_product = $order->get_product_from_item( $item );
						if ( ! $_product->is_virtual() ) {

							$virtual_order = false;
							break;

						} // End If Statement

					} // End If Statement

				} // End For Loop

			} // End If Statement

			// virtual order, mark as completed
			if ( $virtual_order ) {

				return 'completed';

			} // End If Statement

		} // End If Statement

		return $order_status;

	}// end virtual_order_payment_complete


	/**
	 * Determine if the user has and active subscription to give them access
	 * to the requested resource.
	 *
	 * @since 1.9.0
	 *
	 * @param  boolean$user_access_permission
	 * @param  integer $user_id
	 * @return boolean $user_access_permission
	 */
	public static function get_subscription_permission( $user_access_permission , $user_id ){

		global $post;

		// ignore the current case if the following conditions are met
		if ( ! class_exists( 'WC_Subscriptions' ) || empty( $user_id )
			|| ! in_array( $post->post_type, array( 'course','lesson','quiz' ) )
			|| ! wcs_user_has_subscription( $user_id) ){

			return $user_access_permission;

		}

		// at this user has a subscription
		// is the subscription on the the current course?

		$course_id = 0;
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
		if( ! in_array( $product->get_type(), self::get_subscription_types() ) ){

			return $user_access_permission;

		}

		// give access if user has active subscription on the product otherwise restrict it.
		// also check if the user was added to the course directly after the subscription started.
		if( wcs_user_has_subscription( $user_id, $product_id, 'active'  )
			|| wcs_user_has_subscription( $user_id, $product_id, 'pending-cancel'  )
			|| self::was_user_added_without_subscription( $user_id, $product_id, $course_id  ) ){

			$user_access_permission = true;

		}else{

			$user_access_permission = false;
			// do not show the WC permissions message
			remove_filter( 'sensei_the_no_permissions_message', array( 'Sensei_WC', 'alter_no_permissions_message' ), 20, 2 );
			Sensei()->permissions_message['title'] = __( 'No active subscription', 'woothemes-sensei' );
			Sensei()->permissions_message['message'] = __( 'Sorry, you do not have an access to this content without an active subscription.', 'woothemes-sensei' );
		}

		return $user_access_permission;

	} // end get_subscription_permission

	/**
	 * @since 1.9.0
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

		if( ! is_object( $product ) || ! in_array( $product->get_type(), self::get_subscription_types() ) ){

			return $has_user_started_course;

		}

		// give access if user has active subscription on the product otherwise restrict it.
		// also check if the user was added to the course directly after the subscription started.
		if ( wcs_user_has_subscription( $user_id, $product_id, 'active' )
			|| wcs_user_has_subscription( $user_id, $product_id, 'pending-cancel' )
			|| self::was_user_added_without_subscription( $user_id, $product_id, $course_id  )  ){

			$has_user_started_course = true;

		} else {

			$has_user_started_course = false;

		}

		$sensei_wc_subscription_access_store[ $user_data_index_key ] = $has_user_started_course;
		return $has_user_started_course;
	}

	/**
	 * Get all the valid subscription types.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	public static function get_subscription_types(){

		return array( 'subscription','subscription_variation','variable-subscription' );

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
	public static function was_user_added_without_subscription($user_id, $product_id, $course_id ){

		$course_start_date = '';
		$subscription_start_date = '';
		$is_a_subscription ='';
		$was_user_added_without_subscription = false;

		// if user is not on the course they were not added
		remove_filter( 'sensei_user_started_course',     array( 'Sensei_WC', 'get_subscription_user_started_course' ), 10, 3 );
		if( ! Sensei_Utils::user_started_course( $course_id, $user_id ) ){

			return false;

		}

		// if user doesn't have a subscription and is taking the course
		// they were added manually
		if ( ! wcs_user_has_subscription($user_id, $product_id)
			&& Sensei_Utils::user_started_course( $course_id, get_current_user_id() )  ){

			return true;

		}

		add_filter( 'sensei_user_started_course',     array( 'Sensei_WC', 'get_subscription_user_started_course' ), 10, 3 );

		$course_status =  Sensei_Utils::user_course_status( $course_id, $user_id );

		// comparing dates setup data
		$course_start_date = date_create( $course_status->comment_date );
		$subscriptions = wcs_get_users_subscriptions( $user_id );

		// comparing every subscription
		foreach( $subscriptions as $subscription ){

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
	 * Get all the orders for a specific user and product combination
	 *
	 * @param int $user_id
	 * @param $product_id
	 *
	 * @return array $orders
	 */
	public static function get_user_product_orders( $user_id =  0, $product_id ) {

		if ( empty( $user_id ) ) {
			return array();
		}

		$args = array(
			'posts_per_page' => 5000,
			'post_type' => 'shop_order',
			'meta_key'    => '_customer_user',
			'meta_value'  => intval( $user_id ),
			'post_status' => array('wc-completed', 'wc-processing'),
		);

		return get_posts( $args );

	}

	/**
	 * Determine if a course can be purchased. Purchasable
	 * courses have valid products attached. These can also be products
	 * with price of Zero.
	 *
	 *
	 * @since 1.9.0
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public static function is_course_purchasable( $course_id = 0 ){

		if( ! self::is_woocommerce_active() ){
			return false;
		}

		$course_product_id = self::get_course_product_id( $course_id );

		if ( ! $course_product_id )
			return false;

		$course_product = wc_get_product( $course_product_id );

		return $course_product->is_purchasable();

	}

}// end Sensei_WC

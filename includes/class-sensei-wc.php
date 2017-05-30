<?php
/**
 * Sensei WooCommerce Integration
 *
 * @package Access-Management
 * @author Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // End if().

/**
 * Sensei WooCommerce class
 *
 * All functions needed to integrate Sensei and WooCommerce
 *
 * @package Access-Management
 * @author Automattic
 * @since 1.9.0
 */
class Sensei_WC {

	/**
	 * Load the files needed for the woocommerce integration.
	 *
	 * @since 1.9.0
	 */
	public static function load_woocommerce_integration_hooks() {

		if ( ! self::is_woocommerce_active() ) {
			return;
		}

		$woocommerce_hooks_file_path = Sensei()->plugin_path() . 'includes/hooks/woocommerce.php';
		require_once( $woocommerce_hooks_file_path );

	}
	/**
	 * Check if WooCommerce plugin is loaded and allowed by Sensei.
	 *
	 * @since 1.9.0
	 * @return bool
	 */
	public static function is_woocommerce_active() {

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
	public static function is_woocommerce_present() {
		return Sensei_Utils::is_plugin_present_and_activated( 'Woocommerce', 'woocommerce/woocommerce.php' );
	}//end is_woocommerce_present()

	/**
	 * Find the order active number (completed or processing ) for a given user on a course. It will return the latest order.
	 *
	 * If multiple exist we will return the latest order.
	 *
	 * @param int  $user_id User ID.
	 * @param int  $course_id Course ID.
	 * @param bool $check_parent_products Check Parent Products.
	 *
	 * @return array $user_course_orders
	 */
	public static function get_learner_course_active_order_id( $user_id, $course_id, $check_parent_products = false ) {

		$course_product_id = get_post_meta( $course_id, '_course_woocommerce_product', true );

		$orders_query = new WP_Query( array(
			'post_type'   => 'shop_order',
			'posts_per_page' => -1,
			'post_status' => array( 'wc-processing', 'wc-completed' ),
			'meta_key' => '_customer_user',
			'meta_value' => $user_id,
		) );

		if ( 0 === $orders_query->post_count ) {
			return false;
		}

		foreach ( $orders_query->get_posts() as $order ) {
			$order = new WC_Order( $order->ID );
			$items = $order->get_items();

			foreach ( $items as $item ) {

				// if the product id on the order and the one given to this function
				// this order has been placed by the given user on the given course.
				$item_product_id = Sensei_WC_Utils::get_item_id_from_item( $item );
				$parent_product_id = Sensei_WC_Utils::get_item_id_from_item( $item, true );
				if ( $course_product_id == $item_product_id || $check_parent_products && $parent_product_id == $course_product_id ) {
					return Sensei_WC_Utils::get_order_id( $order );
				}
			}
		}

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
	public static function add_course_archive_wc_filter_links( $filter_links ) {

		$free_courses = self::get_free_courses();
		$paid_courses = self::get_paid_courses();

		if ( empty( $free_courses ) || empty( $paid_courses ) ) {
			// do not show any WooCommerce filters if all courses are
			// free or if all courses are paid
			return $filter_links;

		}

		$filter_links[] = array(
			'id' => 'paid',
			'url' => add_query_arg( array(
				'course_filter' => 'paid',
			), Sensei_Course::get_courses_page_url() ),
			'title' => __( 'Paid', 'woothemes-sensei' ),
		);

		$filter_links[] = array(
			'id' => 'free',
			'url' => add_query_arg( array(
				'course_filter' => 'free',
			), Sensei_Course::get_courses_page_url() ),
			'title' => __( 'Free', 'woothemes-sensei' ),
		);

		return $filter_links;

	}//end add_course_archive_wc_filter_links()

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
	public static function course_archive_wc_filter_free( $query ) {

		if ( isset( $_GET['course_filter'] ) && 'free' == $_GET['course_filter']
			&& 'course' == $query->get( 'post_type' ) && $query->is_main_query() ) {

			// setup the course meta query
			$meta_query = self::get_free_courses_meta_query_args();

			// manipulate the query to return free courses
			$query->set( 'meta_query', $meta_query );

			// don't show any paid courses
			$courses = self::get_paid_courses();
			$ids = array();
			foreach ( $courses as $course ) {
				$ids[] = $course->ID;
			}
			$query->set( 'post__not_in', $ids );

		}// End if().

		return $query;

	}//end course_archive_wc_filter_free()

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
	public static function course_archive_wc_filter_paid( $query ) {

		if ( isset( $_GET['course_filter'] ) && 'paid' == $_GET['course_filter']
			&& 'course' == $query->get( 'post_type' ) && $query->is_main_query() ) {

			// setup the course meta query
			$meta_query = self::get_paid_courses_meta_query_args();

			// manipulate the query to return free courses
			$query->set( 'meta_query', $meta_query );

		}

		return $query;

	}

	/**
	 * Load the WooCommerce single product actions above
	 * single courses if woocommerce is active allowing purchase
	 * information and actions to be hooked from WooCommerce.
	 *
	 * Only triggers on single courses when there is a product associated with them.
	 * Sets the product global to the course product when empty
	 */
	public static function do_single_course_wc_single_product_action() {
		global $wp_query, $product;

		if ( false === Sensei_WC::is_woocommerce_active() ) {
			return;
		}

		if ( empty( $wp_query ) || false === $wp_query->is_single() ) {
			return;
		}

		$course = $wp_query->get_queried_object();
		if ( empty( $course ) || 'course' !== $course->post_type ) {
			return;
		}

		$course_product_id = Sensei_WC::get_course_product_id( absint( $course->ID ) );

		if ( empty( $course_product_id ) ) {
			// no need to proceed, as no product is related to this course
			return;
		}

		if ( empty( $product ) ) {
			// product is not defined, set it to be the course product to mitigate fatals from wc hooks triggered
			// expecting it to be set
			$product = wc_get_product( absint( $course_product_id ) );
		}

		/**
		 * this hooks is documented within the WooCommerce plugin.
		 */
		do_action( 'woocommerce_before_single_product' );

	}//end do_single_course_wc_single_product_action()

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
	public static function alter_can_user_view_lesson( $can_user_view_lesson, $lesson_id, $user_id ) {

		// do not override access to admins
		$course_id = Sensei()->lesson->get_course_id( $lesson_id );
		if ( sensei_all_access() || Sensei_Utils::is_preview_lesson( $lesson_id )
			 || Sensei_Utils::user_started_course( $course_id, $user_id ) ) {

			return $can_user_view_lesson;

		}

		// check if the course has a valid product attached to it
		// which the user should have purchased if they want to access
		// the current lesson
		$course_id = get_post_meta( $lesson_id , '_lesson_course', true );
		$wc_post_id = get_post_meta( $course_id, '_course_woocommerce_product', true );
		$product = self::get_product_object( $wc_post_id );
		if ( isset( $product ) && is_object( $product ) ) {

			// valid product found
			$order_id = self::get_learner_course_active_order_id( $user_id, $course_id );

			// product has a successful order so this user may access the content
			// this function may only return false or the default
			// returning true may override other negatives which we don't want
			if ( ! $order_id ) {

				return false;

			}
		}

		// return the passed in value
		return $can_user_view_lesson;

	}

	/**
	 * @param WP_Query $query
	 */
	public static function assign_user_to_unassigned_purchased_courses( $query ) {
		if ( is_admin() || false === self::is_woocommerce_active() || ! $query->is_main_query() ) {
			return;
		}

		$in_my_courses = self::is_my_courses_page( $query );
		$in_learner_profile = isset( $query->query_vars ) && isset( $query->query_vars['learner_profile'] );

		if ( ! $in_learner_profile && ! $in_my_courses ) {
			return;
		}

		$user_id = $in_learner_profile ? self::user_id_from_query( $query ) : ($in_my_courses ? self::current_user_id() : null);

		if ( ! $user_id ) {
			return;
		}

		remove_action( 'pre_get_posts', array( __CLASS__, __FUNCTION__ ) );

		self::start_purchased_courses_for_user( $user_id );
	}

	/**
	 * @param $query WP_Query
	 * @return bool
	 */
	private static function is_my_courses_page( $query ) {
		if ( ! $query->is_page() ) {
			return false;
		}

		$queried_object = $query->get_queried_object();

		if ( ! $queried_object ) {
			return false;
		}

		$object_id = absint( $queried_object->ID );
		$my_courses_page = Sensei()->settings->get( 'my_course_page' );
		if ( false === $my_courses_page ) {
			return false;
		}
		$my_courses_page_id = absint( $my_courses_page );

		if ( $object_id !== $my_courses_page_id ) {
			return false;
		}

		return true;
	}

	private static function user_id_from_query( $query ) {
		$user = get_user_by( 'login', esc_html( $query->query_vars['learner_profile'] ) );
		if ( ! $user ) {
			return false;
		}
		return $user->ID;
	}

	private static function current_user_id() {
		global $current_user;

		if ( empty( $current_user ) ) {
			$current_user = wp_get_current_user();
		}

		if ( ! ( $current_user instanceof WP_User ) || $current_user->ID == 0 ) {
			// return in case of anonymous user or no user
			return false;
		}

		return $current_user->ID;

	}

	/**
	 * Add course link to order thank you and details pages.
	 *
	 * @since  1.4.5
	 * @access public
	 *
	 * @return void
	 */
	public static function course_link_from_order() {

		if ( ! is_order_received_page() ) {
			return;
		}

		$order_id = get_query_var( 'order-received' );
		$order = new WC_Order( $order_id );
		$status = Sensei_WC_Utils::get_order_status( $order );

		// exit early if not wc-completed or wc-processing
		if ( ! in_array( $status, array( 'wc-completed', 'wc-processing' ) ) ) {
			return;
		}

		$course_links = array(); // store the for links for courses purchased
		foreach ( $order->get_items() as $item ) {
			$item_id = Sensei_WC_Utils::get_item_id_from_item( $item );

			$user_id = get_post_meta( Sensei_WC_Utils::get_order_id( $order ), '_customer_user', true );

			if ( $user_id ) {

				// Get all courses for product
				$args = Sensei_Course::get_default_query_args();
				$args['meta_query'] = array( array(
							'key' => '_course_woocommerce_product',
							'value' => $item_id,
						),
				);
				$args['orderby'] = 'menu_order date';
				$args['order'] = 'ASC';

				// loop through courses
				$courses = get_posts( $args );
				if ( $courses && count( $courses ) > 0 ) {

					foreach ( $courses as $course ) {

						$title = $course->post_title;
						$permalink = get_permalink( $course->ID );
						$course_links[] .= '<a href="' . esc_url( $permalink ) . '" >' . $title . '</a> ';

					}
				}// End if().
			}
		}// End foreach().

		// add the courses to the WooCommerce notice
		if ( ! empty( $course_links ) ) {

			$courses_html = _nx(
				'You have purchased the following course:',
				'You have purchased the following courses:',
				count( $course_links ),
				'Purchase thank you note on Checkout page. The course link(s) will be show', 'woothemes-sensei'
			);

			foreach ( $course_links as $link ) {

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
	public static function course_in_cart_message() {

		global $post;

		if ( self::is_course_in_cart( $post->ID ) ) { ?>

			<div class="sensei-message info">
				<?php
				$checkout_url = Sensei_WC_Utils::get_checkout_url();
				$cart_link = '<a class="cart-complete" href="' . esc_url( $checkout_url )
							  . '" title="' . __( 'complete purchase', 'woothemes-sensei' ) . '">'
							  . __( 'complete the purchase', 'woothemes-sensei' ) . '</a>';

				echo sprintf( __( 'You have already added this Course to your cart. Please %1$s to access the course.', 'woothemes-sensei' ), $cart_link );

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
	public static function is_course_in_cart( $course_id ) {

		$wc_post_id = absint( get_post_meta( $course_id, '_course_woocommerce_product', true ) );
		$user_course_status_id = Sensei_Utils::user_started_course( $course_id , get_current_user_id() );

		if ( 0 < intval( $wc_post_id ) && ! $user_course_status_id ) {

			if ( self::is_product_in_cart( $wc_post_id ) ) {

				return true;

			}
		}

		return false;

	}//end is_course_in_cart()

	/**
	 * Check the cart to see if the product is in the cart
	 *
	 * @param $product_id
	 * @return bool
	 */
	public static function is_product_in_cart( $product_id ) {
		if ( false === Sensei_Utils::is_request( 'frontend' ) ) {
			// WC Cart is not loaded when we are on Admin or doing a Cronjob.
			// see https://github.com/Automattic/sensei/issues/1622.
			return false;
		}

		if ( 0 < $product_id ) {

			$product = wc_get_product( $product_id );

			if ( ! is_object( $product ) ) {
				return false;
			}

			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

				$cart_product = $values['data'];
				$cart_product_id = Sensei_WC_Utils::get_product_id( $cart_product );
				if ( $product_id == $cart_product_id ) {

					return true;

				}
			}
		} // End if().

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
	public static function get_free_product_ids() {

		return  get_posts( array(
			'post_type' => 'product',
			'posts_per_page' => '1000',
			'fields' => 'ids',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_regular_price',
					'value' => 0,
				),
				array(
					'key' => '_sale_price',
					'value' => 0,
				),
			),
		));

	}//end get_free_product_ids()

	/**
	 * The metat query for courses that are free
	 *
	 * @since 1.9.0
	 * @return array $wp_meta_query_param
	 */
	public static function get_free_courses_meta_query_args() {

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

	}//end get_free_courses_meta_query_args()

	/**
	 * The metat query for courses that are free
	 *
	 * @since 1.9.0
	 * @return array $wp_query_meta_query_args_param
	 */
	public static function get_paid_courses_meta_query_args() {

		$paid_product_ids = self::get_paid_product_ids();

		return array(
			array(
				'key'     => '_course_woocommerce_product',
				// when empty we give a false post_id to ensure the caller doesn't get any courses for their
				// query
				'value' => empty( $paid_product_ids )? '-1000' : $paid_product_ids,
				'compare' => 'IN',
			),
		);

	}//end get_paid_courses_meta_query_args()

	/**
	 * The WordPress Query args
	 * for paid products on sale
	 *
	 * @since 1.9.0
	 * @return array $product_query_args
	 */
	public static function get_paid_products_on_sale_query_args() {

		$args = array(
				   'post_type' 		=> 'product',
				   'posts_per_page' 		=> 1000,
				   'orderby'         	=> 'date',
				   'order'           	=> 'DESC',
				   'suppress_filters' 	=> 0,
		);

		$args['fields']     = 'ids';

		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key' => '_regular_price',
				'compare' => '>',
				'value' => 0,
			),
			array(
				'key' => '_sale_price',
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
	public static function get_paid_products_not_on_sale_query_args() {

		$args = array(
			'post_type' 		=> 'product',
			'posts_per_page' 	=> 1000,
			'orderby'         	=> 'date',
			'order'           	=> 'DESC',
			'suppress_filters' 	=> 0,
		);

		$args['fields']     = 'ids';
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key' => '_regular_price',
				'compare' => '>',
				'value' => 0,
			),
			array(
				'key' => '_sale_price',
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
	public static function get_paid_product_ids() {

		// get all the paid WooCommerce products that has regular
		// and sale price greater than 0
		// will be used later to check for course with the id as meta
		$paid_product_ids_with_sale = get_posts( self::get_paid_products_on_sale_query_args() );

		// get all the paid WooCommerce products that has regular price
		// greater than 0 without a sale price
		// will be used later to check for course with the id as meta
		$paid_product_ids_without_sale = get_posts( self::get_paid_products_not_on_sale_query_args() );

		// get variable subscriptions (normal subscriptions are already included with paid products)
		if ( Sensei_WC_Subscriptions::is_wc_subscriptions_active() ) {

			$variable_subscription_query_args = self::get_paid_products_not_on_sale_query_args();

			$variable_subscription_query_args['meta_query'] = array(
				array(
					'key' => '_subscription_sign_up_fee',
					'compare' => 'EXISTS',
				),
			);

			$paid_product_ids_without_sale = array_merge( $paid_product_ids_without_sale, get_posts( $variable_subscription_query_args ) );

		}

		// combine products ID's with regular and sale price grater than zero and those without
		// sale but regular price greater than zero
		$woocommerce_paid_product_ids = array_merge( $paid_product_ids_with_sale, $paid_product_ids_without_sale );

		// if
		if ( empty( $woocommerce_paid_product_ids ) ) {
			return array();
		}
		return $woocommerce_paid_product_ids;

	}

	public static function is_wc_subscriptions_active() {
		return Sensei_WC_Subscriptions::is_wc_subscriptions_active();
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
	public static function get_free_courses( $args = array() ) {

		$free_course_query_args = Sensei_Course::get_default_query_args();
		$free_course_query_args['meta_query'] = self::get_free_courses_meta_query_args();

		if ( ! empty( $args ) ) {
			wp_parse_args( $args, $free_course_query_args );
		}

		// don't show any paid courses
		$courses = self::get_paid_courses();
		$ids = array();
		foreach ( $courses as $course ) {
			$ids[] = $course->ID;
		}
		$free_course_query_args['post__not_in'] = $ids;

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
	public static function get_paid_courses( $args = array() ) {

		$paid_course_query_args = Sensei_Course::get_default_query_args();

		$paid_course_query_args['meta_query'] = self::get_paid_courses_meta_query_args();

		if ( ! empty( $args ) ) {
			wp_parse_args( $args, $paid_course_query_args );
		}

		return get_posts( $paid_course_query_args );
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
	public static function the_add_to_cart_button_html( $course_id ) {

		if ( ! Sensei_Course::is_prerequisite_complete( $course_id ) || self::is_course_in_cart( $course_id ) ) {
			return '';
		}

		$wc_post_id = self::get_course_product_id( $course_id );

		// Check if customer purchased the product
		if ( self::has_customer_bought_product( get_current_user_id(), $wc_post_id )
			|| empty( $wc_post_id ) ) {

			return '';

		}

		// based on simple.php in WC templates/single-product/add-to-cart/
		// Get the product
		$product = self::get_product_object( $wc_post_id );

		// do not show the button for invalid products, non purchasable products, out
		// of stock product or if course is already in cart
		if ( ! isset( $product )
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

			<input type="hidden" name="product_id" value="<?php echo esc_attr( Sensei_WC_Utils::get_product_id( $product ) ); ?>" />

			<input type="hidden" name="quantity" value="1" />

			<?php if ( Sensei_WC_Utils::is_product_variation( $product ) ) {
				$variation_data = Sensei_WC_Utils::get_product_variation_data( $product );
				?>

				<input type="hidden" name="variation_id" value="<?php echo Sensei_WC_Utils::get_product_variation_id( $product ); ?>" />
				<?php if ( is_array( $variation_data ) && count( $variation_data ) > 0 ) { ?>

					<?php foreach ( $variation_data as $att => $val ) { ?>

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
	public static function alter_no_permissions_message( $message, $post_id ) {

		if ( empty( $post_id ) || 'course' != get_post_type( $post_id ) ) {
			return  $message;
		}

		$product_id = self::get_course_product_id( $post_id );

		if ( ! $product_id
			|| ! self::has_customer_bought_product( get_current_user_id(),$product_id ) ) {

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
	public static function the_course_no_permissions_message( $course_id ) {

		// login link
		$my_courses_page_id = intval( Sensei()->settings->settings['my_course_page'] );
		$login_link = '<a href="' . esc_url( get_permalink( $my_courses_page_id ) ) . '">' . __( 'log in', 'woothemes-sensei' ) . '</a>';
		$wc_product_id = self::get_course_product_id( $course_id );

		if ( self::is_product_in_cart( $wc_product_id ) ) {

			$cart_link = '<a href="' . wc_get_checkout_url() . '" title="' . __( 'Checkout', 'woocommerce' ) . '">' . __( 'checkout', 'woocommerce' ) . '</a>';

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
	public static function has_customer_bought_product( $user_id, $product_id ) {

		$product = wc_get_product( $product_id );

		if ( false === $product ) {
			return false;
		}

		// get variations parent
		if ( Sensei_WC_Utils::is_product_variation( $product ) ) {
			$product_id = Sensei_WC_Utils::get_product_id( $product );
		}

		$orders = self::get_user_product_orders( $user_id, $product_id );

		foreach ( $orders as $order_id ) {

			$order = new WC_Order( $order_id->ID );

			// wc-active is the subscriptions complete status
			$status = 'wc-' . $order->get_status();
			if ( ! in_array( $status, array( 'wc-processing', 'wc-completed' ) )
				|| ! ( 0 < sizeof( $order->get_items() ) ) ) {

				continue;

			}

			foreach ( $order->get_items() as $item ) {

				// Check if user has bought product
				if ( Sensei_WC_Utils::has_user_bought_product( $product_id, $item ) ) {

					// Check if user has an active subscription for product
					if ( Sensei_WC_Subscriptions::is_wc_subscriptions_active() ) {
						$user_bought_subscription_but_cancelled = wcs_user_has_subscription( $user_id, $product_id, 'cancelled' );
						if ( $user_bought_subscription_but_cancelled ) {
							// assume the user was refunded, so technically it is ok to display a buy product
							return false;
						}
						$sub_key = wcs_get_subscription( $order );
						if ( $sub_key ) {
							$sub = wcs_get_subscription( $sub_key );
							if ( $sub && isset( $sub['status'] ) ) {
								if ( 'active' == $sub['status'] ) {
									return true;
								} else {
									return false;
								}
							}
						}
					}

					// Customer has bought product
					return true;
				}
			}
		} // End foreach().

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
	 */
	public static function get_course_product_id( $course_id ) {

		$product_id = get_post_meta( $course_id, '_course_woocommerce_product', true );

		if ( empty( $product_id ) ) {
			return false;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		if ( Sensei_WC_Utils::is_product_variation( $product ) ) {
			return Sensei_WC_Utils::get_product_variation_id( $product );
		}

		return Sensei_WC_Utils::get_product_id( $product );

	}

	/**
	 * Alter the body classes adding WooCommerce to the body
	 *
	 * Speciall cases where this is needed is template no-permissions.php
	 *
	 * @param array $classes
	 * @return array
	 */
	public static function add_woocommerce_body_class( $classes ) {

		if ( ! in_array( 'woocommerce', $classes ) && defined( 'SENSEI_NO_PERMISSION' ) && SENSEI_NO_PERMISSION ) {

			$classes[] = 'woocommerce';

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
	public static function activate_subscription( $order ) {
		return Sensei_WC_Subscriptions::activate_subscription( $order );
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
	public static function email_course_details( $order ) {

		global $woocommerce;
		$post_status = Sensei_WC_Utils::get_order_status( $order );

		// exit early if not wc-completed or wc-processing
		if ( 'wc-completed' != $post_status
			&& 'wc-processing' != $post_status ) {
			return;
		}

		$order_items = $order->get_items();
		$order_id = Sensei_WC_Utils::get_order_id( $order );

		// If object have items go through them all to find course
		if ( 0 < sizeof( $order_items ) ) {

			$course_details_html = '<h2>' . __( 'Course details', 'woothemes-sensei' ) . '</h2>';
			$order_contains_courses = false;

			foreach ( $order_items as $item ) {
				$item_id = Sensei_WC_Utils::get_item_id_from_item( $item );

				$user_id = get_post_meta( $order_id, '_customer_user', true );

				if ( $user_id ) {

					// Get all courses for product
					$args = array(
						'posts_per_page' => -1,
						'post_type' => 'course',
						'meta_query' => array(
							array(
								'key' => '_course_woocommerce_product',
								'value' => $item_id,
							),
						),
						'orderby' => 'menu_order date',
						'order' => 'ASC',
					);
					$courses = get_posts( $args );

					if ( $courses && count( $courses ) > 0 ) {

						foreach ( $courses as $course ) {

							$title = $course->post_title;
							$permalink = get_permalink( $course->ID );
							$order_contains_courses = true;
							$course_details_html .= '<p><strong>' . sprintf( __( 'View course: %1$s', 'woothemes-sensei' ), '</strong><a href="' . esc_url( $permalink ) . '">' . $title . '</a>' ) . '</p>';
						}
					}
				}
			} // End foreach().

			// Output Course details
			if ( $order_contains_courses ) {

				echo $course_details_html;

			}
		} // End if().

	}//end email_course_details()

	/**
	 * sensei_woocommerce_complete_order description
	 *
	 * @since   1.0.3
	 * @access  public
	 * @param   int $order_id WC order ID
	 * @return  void
	 */
	public static function complete_order( $order_id = 0 ) {

		$order_user = array();

		// Check for WooCommerce
		if ( ! Sensei_WC::is_woocommerce_active() || empty( $order_id ) ) {
			return;
		}

		// Get order object
		$order = new WC_Order( $order_id );
		$order_status = Sensei_WC_Utils::get_order_status( $order );

		if ( ! in_array( $order_status, array( 'wc-completed', 'wc-processing' ) ) ) {
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

		Sensei_WC_Utils::log( 'Sensei_WC::complete_order: order_id = ' . $order_id );

		// Run through each product ordered
		foreach ( $order->get_items() as $item ) {

			$product_type = '';
			if ( Sensei_WC_Utils::is_wc_item_variation( $item ) ) {
				$product_type = 'variation';
			}

			$item_id = Sensei_WC_Utils::get_item_id_from_item( $item );

			$_product = Sensei_WC::get_product_object( $item_id, $product_type );

			if ( ! $_product ) {
				continue;
			}

			$_product_id = Sensei_WC_Utils::get_product_id( $_product );

			// Get courses that use the WC product
			$courses = Sensei()->course->get_product_courses( $_product_id );
			Sensei_WC_Utils::log( 'Sensei_WC::complete_order: Got (' . count( $courses ) . ') course(s), order_id ' . $order_id . ', product_id ' . $_product_id );

			// Loop and update those courses
			foreach ( $courses as $course_item ) {
				Sensei_WC_Utils::log( 'Sensei_WC::complete_order: Update course_id ' . $course_item->ID . ' for user_id ' . $order_user['ID'] );
				$update_course = self::course_update( $course_item->ID, $order_user, $order );
				if ( false === $update_course ) {
					Sensei_WC_Utils::log( 'Sensei_WC::complete_order: FAILED course_update course_id ' . $course_item->ID . ' for user_id ' . $order_user['ID'] );
				}
			}
		} // End foreach().
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
	public static function cancel_order( $order_id ) {

		// Get order object
		if ( is_object( $order_id ) ) {

			$order = $order_id;

		} else {

			$order = new WC_Order( $order_id );
		}

		if ( ! in_array( $order->get_status(), array( 'cancelled', 'refunded' ) ) ) {

			return;

		}

		// Run through each product ordered
		if ( 0 < sizeof( $order->get_items() ) ) {

			// Get order user
			$user_id = $order->__get( 'user_id' );

			foreach ( $order->get_items() as $item ) {

				$item_id = Sensei_WC_Utils::get_item_id_from_item( $item ); // End If Statement

				if ( self::has_customer_bought_product( $user_id, $item_id ) ) {

					// Get courses that use the WC product
					$courses = Sensei()->course->get_product_courses( $item_id );

					// Loop and update those courses
					foreach ( $courses as $course_item ) {

						// Check and Remove course from courses user meta
						Sensei_Utils::sensei_remove_user_from_course( $course_item->ID, $user_id );

					}
				}
			}
		} // End if().

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
	public static function get_product_object( $wc_product_id = 0, $product_type = '' ) {

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

				}
			}
		} // End if().

		return $wc_product_object;

	} // End sensei_get_woocommerce_product_object()

	/**
	 * If customer has purchased the course, update Sensei to indicate that they are taking the course.
	 *
	 * @since  1.0.0
	 * @since 1.9.0 move to class Sensei_WC
	 *
	 * @param  int 			 $course_id  (default: 0)
	 * @param  array/Object  $order_user (default: array()) Specific user's data.
	 * @param  WC_Order|null $order The Order.
	 *
	 * @return bool|int
	 */
	public static function course_update( $course_id = 0, $order_user = array(), $order = null ) {

		global $current_user;
		$has_valid_user_object = isset( $current_user->ID ) || isset( $order_user['ID'] );
		if ( ! $has_valid_user_object ) {
			return false;
		}

		$has_valid_user_id = ! empty( $current_user->ID ) || ! empty( $order_user['ID'] );
		if ( ! $has_valid_user_id ) {
			return false;
		}

		// setup user data.
		if ( is_admin() ) {
			$user_id = $order_user['ID'];
		} else {
			$user_id = empty( $current_user->ID ) ? $order_user['ID'] : $current_user->ID;
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return false;
			}
		}

		Sensei_WC_Utils::log( 'Sensei_WC::course_update: course_id ' . $course_id . ', user_id ' . $user_id );

		// Get the product ID.
		$wc_post_id = get_post_meta( intval( $course_id ), '_course_woocommerce_product', true );
		Sensei_WC_Utils::log( 'Sensei_WC::course_update: product_id ' . $wc_post_id );

		// This doesn't appear to be purely WooCommerce related. Should it be in a separate function?
		$course_prerequisite_id = (int) get_post_meta( $course_id, '_course_prerequisite', true );

		if ( 0 < absint( $course_prerequisite_id ) ) {
			Sensei_WC_Utils::log( 'Sensei_WC::course_update: course_prerequisite_id ' . $course_prerequisite_id );
			$prereq_course_complete = Sensei_Utils::user_completed_course( $course_prerequisite_id, intval( $user_id ) );
			if ( ! $prereq_course_complete ) {
				// Remove all course user meta.
				return Sensei_Utils::sensei_remove_user_from_course( $course_id, $user_id );

			}
		}

		$has_payment_method = isset( $_POST['payment_method'] );
		$payment_method = $has_payment_method ? sanitize_text_field( $_POST['payment_method'] ) : '';
		$is_user_taking_course = Sensei_Utils::user_started_course( intval( $course_id ), intval( $user_id ) );
		$currently_purchasing_course = $has_payment_method || ( null !== $order && is_a( $order, 'WC_Order' ) );
		Sensei_WC_Utils::log( 'Sensei_WC::course_update: user_taking_course: ' . ( $is_user_taking_course ? 'yes' : 'no' ) );
		if ( $has_payment_method ) {
			Sensei_WC_Utils::log( 'Sensei_WC::course_update: user purchasing course via ' . $payment_method );
		}

		if ( ! $is_user_taking_course
			&& 0 < $wc_post_id
			&& ( self::has_customer_bought_product( $user_id, $wc_post_id ) || $currently_purchasing_course ) ) {

			$activity_logged = Sensei_Utils::user_start_course( intval( $user_id ), intval( $course_id ) );
			Sensei_WC_Utils::log( 'Sensei_WC::course_update: activity_logged: ' . $activity_logged );
			$is_user_taking_course = ( false !== $activity_logged );
		}// End if().

		Sensei_WC_Utils::log( 'Sensei_WC::course_update: user taking course after update: ' . ( $is_user_taking_course ? 'yes' : 'NO' ) );

		return $is_user_taking_course;

	} // End course_update()

	/**
	 * Disable guest checkout if a course product is in the cart
	 *
	 * @since 1.1.0
	 * @since 1.9.0 move to class Sensei_WC
	 *
	 * @param  boolean $guest_checkout Current guest checkout setting.
	 *
	 * @return boolean                 Modified guest checkout setting
	 */
	public static function disable_guest_checkout( $guest_checkout ) {

		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			if ( isset( WC()->cart->cart_contents ) && count( WC()->cart->cart_contents ) > 0 ) {

				foreach ( WC()->cart->cart_contents as $cart_key => $product ) {
					if ( isset( $product['product_id'] ) ) {

						$args = array(
							'posts_per_page' => -1,
							'post_type' => 'course',
							'meta_query' => array(
								array(
									'key' => '_course_woocommerce_product',
									'value' => $product['product_id'],
								),
							),
						);

						$posts = get_posts( $args );

						if ( $posts && count( $posts ) > 0 ) {

							foreach ( $posts as $course ) {
								$guest_checkout = '';
								break;

							}
						}
					}
				}
			}
		}

		return $guest_checkout;

	}//end disable_guest_checkout()

	/**
	 * Change order status with virtual products to completed
	 *
	 * @since  1.1.0
	 * @since 1.9.0 move to class Sensei_WC
	 *
	 * @param string $order_status Order Status.
	 * @param int    $order_id Order ID.
	 *
	 * @return string
	 **/
	public static function virtual_order_payment_complete( $order_status, $order_id ) {

		$order = new WC_Order( $order_id );

		if ( ! isset( $order ) ) {
			return '';
		}

		if ( 'wc-processing' === $order_status  && in_array( $order->post_status, array( 'wc-on-hold', 'wc-pending', 'wc-failed' ), true ) ) {

			$virtual_order = true;

			if ( count( $order->get_items() ) > 0 ) {

				foreach ( $order->get_items() as $item ) {
					$_product = Sensei_WC_Utils::get_product_from_item( $item, $order );
					if ( false === $_product ) {
						continue;
					}

					if ( ! $_product->is_virtual() ) {
						$virtual_order = false;
						break;
					}
				}
			} // End if().

			// virtual order, mark as completed.
			if ( $virtual_order ) {

				return 'completed';

			}
		} // End if().

		return $order_status;

	}

	/**
	 * Determine if the user has and active subscription to give them access
	 * to the requested resource.
	 *
	 * @since 1.9.0
	 *
	 * @param  bool $user_access_permission Access Permission.
	 * @param  int  $user_id User ID.
	 *
	 * @return bool $user_access_permission
	 */
	public static function get_subscription_permission( $user_access_permission, $user_id ) {
		_deprecated_function( __FUNCTION__, esc_html( Sensei()->version ), 'Sensei_WC_Subscriptions::get_subscription_permission' );
		return Sensei_WC_Subscriptions::get_subscription_permission( $user_access_permission , $user_id );
	}

	/**
	 * Get_subscription_user_started_course
	 *
	 * @since 1.9.0
	 *
	 * @param bool $has_user_started_course Has Started.
	 * @param int  $course_id Course ID.
	 * @param int  $user_id User ID.
	 *
	 * @return bool $has_user_started_course
	 */
	public static function get_subscription_user_started_course( $has_user_started_course, $course_id, $user_id ) {
		_deprecated_function( __FUNCTION__, esc_html( Sensei()->version ), 'Sensei_WC_Subscriptions::get_subscription_user_started_course' );
		return Sensei_WC_Subscriptions::get_subscription_user_started_course( $has_user_started_course, $course_id, $user_id );
	}

	/**
	 * Compare the user's subscriptions end date with the date
	 * the user was added to the course. If the user was added after
	 * the subscription ended they were manually added and this will return
	 * true.
	 *
	 * Important to note that all subscriptions for the user is compared.
	 *
	 * @deprecated 1.9.12
	 * @since 1.9.0
	 *
	 * @param int $user_id User ID.
	 * @param int $product_id Product ID.
	 * @param int $course_id Course ID.
	 *
	 * @return bool
	 */
	public static function was_user_added_without_subscription( $user_id, $product_id, $course_id ) {
		_deprecated_function( __FUNCTION__, esc_html( Sensei()->version ), 'Sensei_WC_Subscriptions::was_user_added_without_subscription' );
		return Sensei_WC_Subscriptions::was_user_added_without_subscription( $user_id, $product_id, $course_id );
	}

	/**
	 * Get all the orders for a specific user and product combination
	 *
	 * @param int $user_id The user id.
	 * @param int $product_id The product id.
	 *
	 * @return array $orders
	 */
	public static function get_user_product_orders( $user_id = 0, $product_id ) {

		if ( empty( $user_id ) ) {
			return array();
		}

		$args = array(
			'posts_per_page' => 5000,
			'post_type' => 'shop_order',
			'meta_key'    => '_customer_user',
			'meta_value'  => intval( $user_id ),
			'post_status' => array( 'wc-completed', 'wc-processing' ),
		);

		return get_posts( $args );

	}

	/**
	 * Determine if a course can be purchased. Purchasable
	 * courses have valid products attached. These can also be products
	 * with price of Zero.
	 *
	 * @since 1.9.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return bool
	 */
	public static function is_course_purchasable( $course_id = 0 ) {

		if ( ! self::is_woocommerce_active() ) {
			return false;
		}

		$course_product_id = self::get_course_product_id( $course_id );

		if ( ! $course_product_id ) {
			return false;
		}

		$course_product = wc_get_product( $course_product_id );

		return $course_product->is_purchasable();

	}

	/**
	 * Get_courses_from_product_id
	 *
	 * @param int $item_id Item id.
	 * @return array
	 */
	private static function get_courses_from_product_id( $item_id ) {
		$product = self::get_product_object( $item_id );
		if ( ! is_object( $product ) ) {
			return array();
		}

		$product_courses = Sensei()->course->get_product_courses( $product->get_id() );
		return $product_courses;
	}

	/**
	 * WC start_purchased_courses_for_user
	 *
	 * @param int $user_id The user ID.
	 */
	private static function start_purchased_courses_for_user( $user_id ) {
		// get current user's active courses.
		$active_courses = Sensei_Utils::sensei_check_for_activity( array(
			'user_id' => $user_id,
			'type' => 'sensei_course_status',
		), true );

		if ( empty( $active_courses ) ) {
			$active_courses = array();
		}

		if ( ! is_array( $active_courses ) ) {
			$active_courses = array( $active_courses );
		}

		$active_course_ids = array();

		foreach ( $active_courses as $c ) {
			$active_course_ids[] = $c->comment_post_ID;
		}

		$orders_query = new WP_Query(array(
			'post_type' => 'shop_order',
			'posts_per_page' => -1,
			'post_status' => array( 'wc-processing', 'wc-completed' ),
			'meta_key' => '_customer_user',
			'meta_value' => $user_id,
			'fields' => 'ids',
		));

		// get user's processing and completed orders.
		$user_order_ids = $orders_query->get_posts();

		if ( empty( $user_order_ids ) ) {
			$user_order_ids = array();
		}

		if ( ! is_array( $user_order_ids ) ) {
			$user_order_ids = array( $user_order_ids );
		}

		$user_orders = array();

		foreach ( $user_order_ids as $order_data ) {
			$user_orders[] = new WC_Order( $order_data );
		}

		foreach ( $user_orders as $user_order ) {
			foreach ( $user_order->get_items() as $item ) {
				$item_id = Sensei_WC_Utils::get_item_id_from_item( $item );

				$product_courses = self::get_courses_from_product_id( $item_id );
				$is_variation = Sensei_WC_Utils::is_wc_item_variation( $item );
				$is_course_linked_to_parent_product = false;

				if ( empty( $product_courses ) && $is_variation ) {
					// if we get no products from a variable sub course.
					// check if there are any courses linked to the parent product id.
					$item_id = Sensei_WC_Utils::get_item_id_from_item( $item, true );
					$product_courses = self::get_courses_from_product_id( $item_id );
					$is_course_linked_to_parent_product = ! empty( $product_courses );
				}

				foreach ( $product_courses as $course ) {
					$course_id = $course->ID;
					$order_id = self::get_learner_course_active_order_id( $user_id, $course_id, $is_course_linked_to_parent_product );

					if ( in_array( $order_id, $user_order_ids, true ) &&
						! in_array( $course_id, $active_course_ids, true )
					) {
						if ( Sensei_WC_Subscriptions::has_user_bought_subscription_but_cancelled( $course_id, $user_id ) ) {
							continue;
						}
						// user ordered a course and not assigned to it. Fix this by assigning them now.
						Sensei_Utils::start_user_on_course( $user_id, $course_id );
					}
				}
			}
		}
	}

}//end class

<?php
/**
 * WooCommerce Utility/Compatibility
 *
 * @package Access-Management
 * @author Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_WC_Utils
 */
class Sensei_WC_Utils {

	/**
	 * Logger.
	 *
	 * @var WC_Logger
	 */
	private static $logger = null;

	/**
	 * WC get_order_status
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public static function get_order_status( $order ) {
		return self::wc_version_less_than( '2.7.0' ) ? $order->post_status : 'wc-' . $order->get_status();
	}

	/**
	 * WC wc_version_less_than
	 *
	 * @param string $str Version String.
	 * @return mixed
	 */
	public static function wc_version_less_than( $str ) {
		return version_compare( WC()->version, $str, '<' );
	}

	/**
	 * WC has_user_bought_product
	 *
	 * @param int                         $product_id Product.
	 * @param array|WC_Order_Item_Product $item Item.
	 * @return bool
	 */
	public static function has_user_bought_product( $product_id, $item ) {
		$product_id = absint( $product_id );
		if ( self::wc_version_less_than( '2.7.0' ) ) {
			return absint( $item['product_id'] === $product_id ) || absint( $item['variation_id'] ) === $product_id;
		}
		return $product_id === $item->get_variation_id() || $product_id === $item->get_product_id();
	}

	/**
	 * WC is_wc_item_variation
	 *
	 * @param array|WC_Order_Item_Product $item Item.
	 * @return bool
	 */
	public static function is_wc_item_variation( $item ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $item->get_variation_id() ? true : false;
		}
		return isset( $item['variation_id'] ) && ! empty( $item['variation_id'] );
	}

	/**
	 * WC is_product_variation
	 *
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function is_product_variation( $product ) {
		if ( self::wc_version_less_than( '2.7.0' ) ) {
			return isset( $product->variation_id ) && 0 < intval( $product->variation_id );
		}
		return $product->is_type( 'variation' );
	}

	/**
	 * WC get_order_id
	 *
	 * @param WC_Order $order Order.
	 * @return mixed
	 */
	public static function get_order_id( $order ) {
		return self::wc_version_less_than( '2.7.0' ) ? $order->id : $order->get_id();
	}

	/**
	 * Get the product id. Always return parent id in variations
	 *
	 * @param WC_Product $product The Product.
	 * @return int
	 */
	public static function get_product_id( $product ) {
		if ( self::wc_version_less_than( '2.7.0' ) ) {
			return $product->id;
		}
		return self::is_product_variation( $product ) ? $product->get_parent_id() : $product->get_id();
	}

	/**
	 * WC get_product_variation_id
	 *
	 * @param WC_Product $product Product.
	 * @return int|null
	 */
	public static function get_product_variation_id( $product ) {
		if ( ! self::is_product_variation( $product ) ) {
			return null;
		}
		return self::wc_version_less_than( '2.7.0' ) ? $product->variation_id : $product->get_id();
	}

	/**
	 * WC get_item_id_from_item.
	 *
	 * @param array|WC_Order_Item_Product $item Item.
	 * @param bool                        $always_return_parent_product_id Return Parent.
	 * @return mixed
	 */
	public static function get_item_id_from_item( $item, $always_return_parent_product_id = false ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			// 2.7: we get a WC_Order_Item_Product.
			$variation_id = $item->get_variation_id();
			$product_id = $item->get_product_id();
		} else {
			// pre 2.7: we get an array.
			$variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : null;
			$product_id = $item['product_id'];
		}
		if ( false === $always_return_parent_product_id
			&& $variation_id && 0 < $variation_id
		) {
			return $variation_id;
		}

		return $product_id;
	}

	/**
	 * Get Product
	 *
	 * @param WP_Post|int $post_or_id Post Or ID.
	 * @return null|WC_Product
	 */
	public static function get_product( $post_or_id ) {
		return self::wc_version_less_than( '2.7' ) ? get_product( $post_or_id ) : wc_get_product( $post_or_id );
	}

	/**
	 * Get_parent_product
	 *
	 * @param WC_Product $product Product.
	 * @return null|WC_Product
	 */
	public static function get_parent_product( $product ) {
		return self::get_product( self::get_product_id( $product ) );
	}

	/**
	 * Get_variation_data
	 *
	 * @param WC_Abstract_Legacy_Product $product The product.
	 * @return mixed
	 */
	public static function get_variation_data( $product ) {
		if ( self::wc_version_less_than( '2.7' ) ) {
			return $product->variation_data;
		}
		return $product->is_type( 'variation' ) ? wc_get_product_variation_attributes( $product->get_id() ) : '';
	}

	/**
	 * Get_formatted_variation
	 *
	 * @param string $variation The variation name.
	 * @param bool   $flat Flat.
	 * @return string
	 */
	public static function get_formatted_variation( $variation = '', $flat = false ) {
		if ( self::wc_version_less_than( '2.7' ) ) {
			return woocommerce_get_formatted_variation( $variation, $flat );
		}

		return wc_get_formatted_variation( $variation, $flat );
	}

	/**
	 * Get Product Variation Data.
	 *
	 * @param WC_Product|WC_Abstract_Legacy_Product $product The product.
	 * @return array|mixed|string
	 */
	public static function get_product_variation_data( $product ) {
		if ( self::wc_version_less_than( '3.0.0' ) ) {
			return ( isset( $product->variation_data ) && is_array( $product->variation_data ) ) ? $product->variation_data : array();
		}

		return self::is_product_variation( $product ) ? wc_get_product_variation_attributes( $product->get_id() ) : '';
	}

	/**
	 * Lazy-load our logger.
	 *
	 * @return WC_Logger
	 */
	private static function get_logger() {
		if ( null === self::$logger ) {
			self::$logger = new WC_Logger();
		}

		return self::$logger;
	}

	/**
	 * Log this
	 *
	 * @param string $message What to log.
	 */
	public static function log( $message ) {
		if ( false === Sensei_WC::is_woocommerce_active() ) {
			return;
		}
		$debugging_enabled = (bool) Sensei()->settings->get( 'woocommerce_enable_sensei_debugging' );
		if ( ! $debugging_enabled ) {
			return;
		}
		self::get_logger()->log( 'notice', $message, array(
			'source' => 'woothemes_sensei_core',
		) );
	}

	/**
	 * Get Product From item.
	 *
	 * @param array|WC_Order_Item_Product $item The item.
	 * @param WC_Order                    $order The order.
	 *
	 * @return bool|WC_Product
	 */
	public static function get_product_from_item( $item, $order ) {
		if ( self::wc_version_less_than( '3.0.0' ) ) {
			return ( $item['product_id'] > 0 ) ? $order->get_product_from_item( $item ) : false;
		}

		return $item->get_product();
	}

	/**
	 * Get Checkout URL
	 *
	 * @return string
	 */
	public static function get_checkout_url() {
		if ( self::wc_version_less_than( '2.5.0' ) ) {
			return WC()->cart->get_checkout_url();
		}
		return wc_get_checkout_url();
	}
}

<?php
/**
 * The Template for displaying the cart for WC.
 *
 * Override this template by copying it to yourtheme/sensei/woocommerce/add-to-cart.php
 *
 * @author      WooThemes
 * @package     Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post, $current_user, $woocommerce, $woothemes_sensei;

$wc_post_id = get_post_meta( $post->ID, '_course_woocommerce_product', true );

// Get User Meta
get_currentuserinfo();
// Check if customer purchased the product
if ( WooThemes_Sensei_Utils::sensei_customer_bought_product( $current_user->user_email, $current_user->ID, $wc_post_id ) ) { ?>
    <div class="sensei-message tick"><?php _e( 'You are currently taking this course.', 'woothemes-sensei' ); ?></div>
<?php } else {
    // based on simple.php in WC templates/single-product/add-to-cart/
    if ( 0 < $wc_post_id ) {
        // Get the product
        $product = $woothemes_sensei->sensei_get_woocommerce_product_object( $wc_post_id );
        if ( ! isset ( $product ) || ! is_object( $product ) ) return;
        if ( $product->is_purchasable() ) {
            // Check Product Availability
            $availability = $product->get_availability();
            if ($availability['availability']) {
                echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'.$availability['availability'].'</p>', $availability['availability'] );
            } // End If Statement
            // Check for stock
            if ( $product->is_in_stock() ) { ?>
                <?php if (! sensei_check_if_product_is_in_cart( $wc_post_id ) ) { ?>
                    <form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart" method="post" enctype="multipart/form-data">
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
                        <button type="submit" class="single_add_to_cart_button button alt"><?php echo $product->get_price_html(); ?> - <?php echo apply_filters('single_add_to_cart_text', __('Purchase this Course', 'woothemes-sensei'), $product->product_type); ?></button>
                    </form>
                <?php } // End If Statement ?>
             <?php } // End If Statement
        } // End If Statement
    } // End If Statement
} // End If Statement

if ( !is_user_logged_in() ) {
    $my_courses_page_id = intval( $woothemes_sensei->settings->settings[ 'my_course_page' ] );
    $login_link =  '<a href="' . esc_url( get_permalink( $my_courses_page_id ) ) . '">' . __( 'log in', 'woothemes-sensei' ) . '</a>'; ?>
    <p class="add-to-cart-login">
        <?php echo sprintf( __( 'Or %1$s to access your purchased courses', 'woothemes-sensei' ), $login_link ); ?>
    </p>
<?php } ?>

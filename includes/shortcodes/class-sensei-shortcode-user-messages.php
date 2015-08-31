<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_user_messages] shortcode. The current users messages.
 * If none exists nothing will be shown.
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Teachers
 * @since 1.9.0
 * @package Sensei
 * @category Shortcodes
 * @author 	WooThemes
 */
class Sensei_Shortcode_User_Messages implements Sensei_Shortcode_Interface {

    /**
     * @var array $messages{
     *     @type WP_Post
     * }
     * messages for the current user
     */
    protected $messages_query;

    /**
     * Setup the shortcode object
     *
     * @since 1.9.0
     * @param array $attributes
     * @param string $content
     * @param string $shortcode the shortcode that was called for this instance
     */
    public function __construct( $attributes, $content, $shortcode ){

        if( is_user_logged_in() ){

            $this->setup_messages_query();

        }

    }

    /**
     * create the messages query .
     *
     * @return mixed
     */
    public function setup_messages_query(){

        $user = wp_get_current_user();

        $args = array(
            'post_type' => 'sensei_message',
            'posts_per_page' => 500,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key'     => '_sender',
                    'value'   => $user->user_login,
                    'compare' => '=',
                ),
            ),
        );

        $this->messages_query  = new WP_Query( $args );
    }

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        if( empty( $this->messages_query ) ){

            return '';

        }

        //set the wp_query to the current messages query
        global $wp_query;
        $wp_query = $this->messages_query;

        ob_start();
        Sensei()->frontend->sensei_get_template_part('loop', 'message');
        $shortcode_output = ob_get_clean();

        // set back the global query
        wp_reset_query();

        return $shortcode_output;

    }// end render

}// end class
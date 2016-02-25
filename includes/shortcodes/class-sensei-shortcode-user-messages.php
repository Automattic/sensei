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
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
 */
class Sensei_Shortcode_User_Messages implements Sensei_Shortcode_Interface {

    /**
     * @var WP_Query
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

        $this->setup_messages_query();

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

        if( !is_user_logged_in() ){

            Sensei()->notices->add_notice( __('Please login to view your messages.','woothemes-sensei') , 'alert'  );

        } elseif( 0 == $this->messages_query->post_count ){

            Sensei()->notices->add_notice( __( 'You do not have any messages.', 'woothemes-sensei') , 'alert'  );
        }

        $messages_disabled_in_settings =  ! ( ! isset( Sensei()->settings->settings['messages_disable'] )
                                            || ! Sensei()->settings->settings['messages_disable'] ) ;

        // don't show anything if messages are disable
        if( $messages_disabled_in_settings ){
            return '';
        }

        //set the wp_query to the current messages query
        global $wp_query;
        $wp_query = $this->messages_query;

        ob_start();
        Sensei()->notices->maybe_print_notices();
        Sensei_Templates::get_part('loop', 'message');
        $messages_html = ob_get_clean();

        // set back the global query
        wp_reset_query();

        return $messages_html;

    }// end render

}// end class
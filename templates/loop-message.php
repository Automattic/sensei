<?php
/**
 * The Template for outputting Message Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-message.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei;
?>

    <?php if ( have_posts() ) { ?>
		<section id="main-sensei_message" class="sensei_message-container">

        	    <?php do_action( 'sensei_message_archive_header' ); ?>

        	    <?php while ( have_posts() ) { the_post();
        			// Meta data
        			$post_id = get_the_ID(); ?>

                    <?php
                    $html = '<article class="' . esc_attr( join( ' ', get_post_class( array( 'lesson', 'course', 'post', 'sensei_message' ), $post_id ) ) ) . '">';

                        $html .= '<header>';

                            $content_post_id = get_post_meta( $post_id, '_post', true );
                            if( $content_post_id ) {
                                $title = sprintf( __( 'Re: %1$s', 'woothemes-sensei' ), get_the_title( $content_post_id ) );
                            } else {
                                $title = get_the_title( $post_id );
                            }

                            $html .= '<h2><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . $title . '</a></h2>';

                            $html .= '<p class="lesson-meta"><small><em>';

                                $sender_username = get_post_meta( $post_id, '_sender', true );
                                if( $sender_username ) {
                                    $sender = get_user_by( 'login', $sender_username );
                                    $html .= sprintf( __( 'Sent by %1$s on %2$s.', 'woothemes-sensei' ), $sender->display_name, get_the_date() );
                                }

                            $html .= '</em></small></p>';

                        $html .= '</header>';

                        $html .= '<section class="entry">';

                            $html .= get_the_excerpt();

                        $html .= '</section>';

                    $html .= '</article>';

                    echo $html;

                    ?>

        		<?php } // End While Loop ?>
        </section>
    <?php } ?>
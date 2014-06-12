<?php
/**
 * The template for displaying product content in the single-quiz.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-single-quiz.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post;

?>
        	<article <?php post_class(); ?>>

                <?php do_action( 'sensei_message_single_title' ); ?>

                <section class="entry">
                	<?php
                	$sender_username = get_post_meta( $post->ID, '_sender', true );
                	if( $sender_username ) {
                		$sender = get_user_by( 'login', $sender_username );
	                	?>
	                	<p class="message-meta"><small><em><?php printf( __( 'Sent by %1$s on %2$s.', 'woothemes-sensei' ), $sender->display_name, get_the_date() ); ?></em></small></p>
                	<?php } ?>
                	<?php the_content(); ?>
				</section>

            </article><!-- .post -->
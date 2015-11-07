<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for outputting Message Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-message.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php
/**
 * This runs before the the message loop items in the loop-message.php template. It runs
 * only only for the message post type. This loop will not run if the current wp_query
 * has no posts.
 *
 * @since 1.9.0
 */
do_action( 'sensei_loop_message_before' );
?>

<div class="message-container" >

    <?php
    /**
     * This runs before the post type items in the loop.php template. It
     * runs within the message loop <ul> tag.
     *
     * @since 1.9.0
     */
    do_action( 'sensei_loop_message_inside_before' );
    ?>

    <?php
    // loop through all messages
    while ( have_posts() ) { the_post();

        sensei_load_template_part('content','message');

    }
    ?>

    <?php
    /**
     * This runs after the post type items in the loop.php template. It runs
     * only for the specified post type
     *
     * @since 1.9.0
     */
    do_action( 'sensei_loop_message_inside_after' );
    ?>

</div>

<?php
/**
 * This runs after the post type items in the loop.php template. It runs
 * only for the specified post type
 *
 * @since 1.9.0
 */
do_action( 'sensei_loop_message_after' );
?>

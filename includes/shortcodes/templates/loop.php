<?php
/**
 * The Template for outputting Lists of any Sensei content type.
 *
 * This template expects the global wp_query to setup and ready for the loop
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.9.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_query;

// exit the template early if there are no posts to load
if( ! have_posts() ){

    return;

}

?>

<section class="<?php echo $wp_query->get('post_type'); ?>-container" >
<?php
/**
 * sensei_loop_before
 *
 * This runs before the post type items in the loop.php template. For all post types
 *
 * @since 1.9
 *
 * @param WP_Query
 */
do_action( 'sensei_loop_before', $wp_query );

/**
 * sensei_loop_{$post_type}_before
 *
 * This runs before the post type items in the loop.php template. It runs
 * only for the specified post type
 *
 * @since 1.9
 * @param WP_Query
 */
do_action( 'sensei_loop_' . get_post_type() . '_before', $wp_query );

/*
 * Loop through all posts
 */
while ( have_posts() ) { the_post();

    include('content.php');

}

/**
 * sensei_loop_after
 *
 * This runs after the post type items in the loop.php template, this runs for all post types.
 *
 * @since 1.9
 *
 * @param WP_Query
 */
do_action( 'sensei_loop_after', $wp_query );

/**
 * sensei_loop_{$post_type}_after
 *
 * This runs after the post type items in the loop.php template. It runs
 * only for the specified post type
 *
 * @since 1.9
 * @param WP_Query
 */
do_action( 'sensei_loop_' . get_post_type() . '_after', $wp_query );

?>

</section>
<?php
/**
 * The template for displaying product content in the single-course.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-single-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $current_user;

// Get User Meta
get_currentuserinfo();
// Check if the user is taking the course
$is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post->ID, $current_user->ID );

// Content Access Permissions
$access_permission = false;
if ( ( isset( $woothemes_sensei->settings->settings['access_permission'] ) && ! $woothemes_sensei->settings->settings['access_permission'] ) || sensei_all_access() ) {
	$access_permission = true;
} // End If Statement
?>
	<?php
	/**
	 * woocommerce_before_single_product hook
	 *
	 * @hooked woocommerce_show_messages - 10
	 */
	if ( WooThemes_Sensei_Utils::sensei_is_woocommerce_activated() ) {
		do_action( 'woocommerce_before_single_product' );
	} // End If Statement
	?>

        	<article <?php post_class( array( 'course', 'post' ) ); ?>>

				<?php do_action( 'sensei_course_image', $post->ID ); ?>

                <?php do_action( 'sensei_course_single_title' ); ?>

                <?php do_action( 'sensei_course_single_meta' ); ?>

                <section class="entry fix">
                	<?php if ( ( is_user_logged_in() && $is_user_taking_course ) || $access_permission || 'full' == $woothemes_sensei->settings->settings[ 'course_single_content_display' ] ) { the_content(); } else { echo '<p class="course-excerpt">' . $post->post_excerpt . '</p>'; } ?>
                </section>

                <?php do_action( 'sensei_course_single_lessons' ); ?>

            </article><!-- .post -->

	        <?php do_action('sensei_pagination'); ?>
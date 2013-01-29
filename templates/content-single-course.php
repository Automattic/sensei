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
global $woothemes_sensei, $post, $current_user;
	 	
// Get User Meta
get_currentuserinfo();
// Check if the user is taking the course
$is_user_taking_course = WooThemes_Sensei_Utils::sensei_check_for_activity( array( 'post_id' => $post->ID, 'user_id' => $current_user->ID, 'type' => 'sensei_course_start' ) );
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
				
				<?php
    			// Image
    			echo  $woothemes_sensei->post_types->course->course_image( $post->ID );
    			?>
    					
                <header>
                
	                <h1><?php the_title(); ?></h1>
	                
                </header>
                
                <section class="entry fix">
                	<?php if ( is_user_logged_in() && $is_user_taking_course ) { the_content(); } else { echo '<p>' . $post->post_excerpt . '</p>'; } ?>
                </section>
					
				<?php course_single_meta(); ?>
	 			<?php course_single_lessons(); ?>
	 			                
            </article><!-- .post -->

	        <nav id="post-entries" class="fix">
	            <div class="nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav">&larr;</span> %title' ); ?></div>
	            <div class="nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav">&rarr;</span>' ); ?></div>
	        </nav><!-- #post-entries -->
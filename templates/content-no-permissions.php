<?php
/**
 * The template for displaying product content in the no-permissions.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-no-permissions.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */
global $woothemes_sensei;
?>
        	<article <?php post_class(); ?>>

                <?php 
                if ( is_singular( 'course' ) ) { ?>
                	<article <?php post_class( array( 'course', 'post' ) ); ?>>
						<?php
    					// Image
    					echo  $woothemes_sensei->post_types->course->course_image( $post->ID );
    					?>
    							
            		    <header>
                
	                		<h1><?php echo $woothemes_sensei->permissions_message['title']; ?></h1>
	                
               			</header>
            		    
            		    <section class="entry fix">
            		    	<div class="woo-sc-box alert"><?php echo $woothemes_sensei->permissions_message['message']; ?></div>
            		    	<?php the_content(); ?>
            		    </section>
							
						<?php course_single_meta(); ?>
	 					<?php course_single_lessons(); ?>
	 					                
            		</article><!-- .post -->
                <?php } else { ?>
                	<header>
                		<h1><?php echo $woothemes_sensei->permissions_message['title']; ?></h1>
	                </header>
                
                	<section class="entry fix">
                		<div class="woo-sc-box alert"><?php echo $woothemes_sensei->permissions_message['message']; ?></div>
                	</section>
                <?php } // End If Statement ?>
                				                
            </article><!-- .post -->

	        <nav id="post-entries" class="fix">
	            <div class="nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav">&larr;</span> %title' ); ?></div>
	            <div class="nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav">&rarr;</span>' ); ?></div>
	        </nav><!-- #post-entries -->
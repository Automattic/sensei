<?php
/**
 * The template for displaying product content in the single-lessons.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-single-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */
 global $woothemes_sensei, $post;
?>
        	<article <?php post_class( array( 'lesson', 'post' ) ); ?>>
				
				<?php
				// Image
    			echo $woothemes_sensei->post_types->lesson->lesson_image( $post->ID );
    			?>
    			
                <header>
                
	                <h1><?php the_title(); ?></h1>
	                
                </header>
                
                <section class="entry fix">
                	<?php the_content(); ?>
				</section>
				
				<?php lesson_single_meta(); ?>
				                
            </article><!-- .post -->

	        <nav id="post-entries" class="fix">
	            <div class="nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav">&larr;</span> %title' ); ?></div>
	            <div class="nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav">&rarr;</span>' ); ?></div>
	        </nav><!-- #post-entries -->
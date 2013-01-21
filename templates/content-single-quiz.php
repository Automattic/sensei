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
?>
        	<article <?php post_class(); ?>>

                <header>
                
	                <h1><?php the_title(); ?></h1>
	                
                </header>
                
                <section class="entry fix">
                	<?php the_content(); ?>
                	<?php quiz_questions(); ?>
				</section>
				                
            </article><!-- .post -->

	        
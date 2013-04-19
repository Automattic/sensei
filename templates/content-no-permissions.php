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

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei;
?>
        	<article <?php post_class(); ?>>

                <?php
                if ( is_singular( 'course' ) ) { ?>
                	<article <?php post_class( array( 'course', 'post' ) ); ?>>

                        <?php do_action( 'sensei_course_image', $post->ID ); ?>

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

	        <?php do_action('sensei_pagination'); ?>
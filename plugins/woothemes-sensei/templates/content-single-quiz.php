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

?>
        	<article <?php post_class(); ?>>

                <?php do_action( 'sensei_quiz_single_title' ); ?>

                <section class="entry">
                	<?php the_content(); ?>
                	<?php do_action( 'sensei_quiz_questions' ); ?>
				</section>

            </article><!-- .post -->

            <?php do_action('sensei_pagination'); ?>
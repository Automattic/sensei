<?php
/**
 * The Template for outputting Lesson Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $wp_query;
?>

    <?php if ( have_posts() ) { ?>

		<section id="main-course" class="course-container">

    	    <?php do_action( 'sensei_lesson_archive_header' ); ?>

    	    <div class="fix"></div>

    	    <?php while ( have_posts() ) { the_post();
    			// Meta data
    			$post_id = get_the_ID(); ?>
                <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ) ); ?>">

                    <?php do_action( 'sensei_lesson_image', $post_id ); ?>
                    <?php do_action( 'sensei_lesson_archive_lesson_title' ); ?>
                    <?php do_action( 'sensei_lesson_meta', $post_id ); ?>

                </article>

    		<div class="fix"></div>

    		<?php } // End While Loop ?>

    	</section>
    <?php } ?>
<?php
/**
 * The Template for displaying all access restriction error messages.
 *
 * Override this template by copying it to yourtheme/sensei/no-permissions.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/**
 * sensei_before_main_content hook
 *
 * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action('sensei_before_main_content');

?>

<article <?php array( get_post_type(), 'post' ) ?>>

    <header>

        <h1><?php echo $woothemes_sensei->permissions_message['title']; ?></h1>

    </header>

    <?php  if ( is_singular( 'course' ) ) { ?>

            <section class="entry fix">

                <div class="sensei-message alert"

                    <?php echo Sensei()->permissions_message['message']; ?>

                </div>

                <?php if ( 'full' == Sensei()->settings->settings[ 'course_single_content_display' ] ) {

                    the_content();

                } else {

                    echo '<p class="course-excerpt">' . sensei_get_excerpt( $post ) . '</p>';

                }
                ?>
            </section>

            <?php course_single_meta(); ?>
            <?php do_action( 'sensei_course_single_lessons' ); ?>

    <?php } else { ?>


        <section class="entry fix">

            <?php if ( is_singular( 'lesson' ) ) {

                echo Woothemes_Sensei_Lesson::lesson_excerpt( $post );

            } ?>

            <div class="sensei-message alert"><?php echo $woothemes_sensei->permissions_message['message']; ?></div>

        </section>

    <?php } // End If Statement ?>



</article><!-- .post -->

<?php do_action('sensei_pagination'); ?>

<?php
/**
 * sensei_after_main_content hook
 *
 * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('sensei_after_main_content');
?>

<?
/**
 * sensei_sidebar hook
 *
 * @hooked sensei_get_sidebar - 10
 */
do_action('sensei_sidebar');

get_footer();
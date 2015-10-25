<?php
/**
 * The Template for displaying all single messages.
 *
 * Override this template by copying it to yourtheme/sensei/single-message.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

/**
 * sensei_before_main_content hook
 *
 * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action( 'sensei_before_main_content' );

?>

<article <?php post_class(); ?> >

    <?php
    /**
     * Action inside the single message template before the content
     *
     * @since 1.9.0
     *
     * @hooked WooThemes_Sensei_Messages::the_title                 - 20
     * @hooked WooThemes_Sensei_Messages::the_message_sent_by_title - 40
     */
    do_action( 'sensei_single_message_content_inside_before');
    ?>

    <section class="entry">

        <?php the_content(); ?>

    </section>

    <?php

    /**
     * action inside the single message template after the content
     * @since 1.9.0
     *
     * @hooked
     */
    do_action( 'sensei_single_message_content_inside_after');

    ?>
</article><!-- .post -->

<?php
/**
* Sensei comments hook on the single message page
*/
do_action( 'sensei_comments' );

/**
 * sensei_after_main_content hook
 *
 * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'sensei_after_main_content' );

/**
 * sensei_sidebar hook
 *
 * @hooked sensei_get_sidebar - 10
 */
do_action( 'sensei_sidebar' );

get_footer();
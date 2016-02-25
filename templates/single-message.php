<?php
/**
 * The Template for displaying all single messages.
 *
 * Override this template by copying it to yourtheme/sensei/single-message.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php  get_sensei_header();  ?>

<article <?php post_class(); ?> >

    <?php
    /**
     * Action inside the single message template before the content
     *
     * @since 1.9.0
     *
     * @param integer $message_id
     *
     * @hooked WooThemes_Sensei_Messages::the_title                 - 20
     * @hooked WooThemes_Sensei_Messages::the_message_sent_by_title - 40
     */
    do_action( 'sensei_single_message_content_inside_before', get_the_ID());
    ?>

    <section class="entry">

        <?php the_content(); ?>

    </section>

    <?php

    /**
     * action inside the single message template after the content
     * @since 1.9.0
     *
     * @param integer $message_id
     */
    do_action( 'sensei_single_message_content_inside_after', get_the_ID());

    ?>
</article><!-- .post -->

<?php get_sensei_footer(); ?>

<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Content-message.php template file
 *
 * responsible for content on archive like pages. Only shows the message excerpt.
 *
 * For single message content please see single-message.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<article <?php post_class( array( 'post','sensei_message'), get_the_ID() ); ?>>

    <section class="message-content">

        <?php
        /**
         * action that runs before the sensei {post_type} content. It runs inside the sensei
         * content.php template. This applies to the specific post type that you've targeted.
         *
         * @since 1.9
         * @param string $message_id
         *
         * @hooked Sensei_Messages::the_message_title - 10
         * @hooked Sensei_Messages::the_message_sender - 20
         */
        do_action( 'sensei_content_message_before', get_the_ID() );
        ?>

        <section class="entry">

            <?php
            /**
             * Fires just before the post content in the content-message.php file.
             *
             * @since 1.9
             *
             * @param string $message_id
             */
            do_action('sensei_content_message_inside_before', get_the_ID());
            ?>

            <p class="message-excerpt">

                <?php the_excerpt();?>

            </p>

            <?php
            /**
             * Fires just after the post content in the message-content.php file.
             *
             * @since 1.9
             *
             * @param string $message_id
             */
            do_action('sensei_content_message_inside_after', get_the_ID());
            ?>

        </section> <!-- section .entry -->

        <?php
        /**
         * This action runs after the sensei message content. It runs inside the sensei
         * message-content.php template.
         *
         * @since 1.9
         * @param string $message_id
         */
        do_action( 'sensei_content_message_after', get_the_ID() );
        ?>

    </section> <!-- article .message-content -->

</article> <!-- article .(<?php echo esc_attr( join( ' ', get_post_class( array( 'sensei_message', 'post' ) ) ) ); ?>  -->

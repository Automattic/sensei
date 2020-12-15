<?php
/**
 * The Template for displaying all single messages.
 *
 * Override this template by copying it to yourtheme/sensei/single-message.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_sensei_header();
the_post();

/**
 * Action inside the single message template before the content
 *
 * @param integer $message_id
 *
 * @hooked Sensei_Messages::the_title                 - 20
 * @hooked Sensei_Messages::the_message_sent_by_title - 40
 * @since  1.9.0
 */
do_action( 'sensei_single_message_content_inside_before', get_the_ID() );

the_content();

/**
 * Action inside the single message template after the content
 *
 * @param integer $message_id
 *
 * @since 1.9.0
 */
do_action( 'sensei_single_message_content_inside_after', get_the_ID() );

get_sensei_footer();

<?php
/**
 * The Template for displaying message archives.
 *
 * Override this template by copying it to yourtheme/sensei/archive-message.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php  get_sensei_header();  ?>

<?php
/**
 * sensei_message_archive_main_content hook
 *
 * @hooked sensei_message_archive_main_content - 10 (outputs main message archive content loop)
 */
do_action( 'sensei_message_archive_main_content' );
?>

<?php get_sensei_footer(); ?>
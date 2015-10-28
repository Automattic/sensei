<?php
/**
 * The Template for displaying lesson archives for the lesson tags taxonomy terms.
 *
 * Override this template by copying it to yourtheme/sensei/taxonomy-lesson-tag.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.7.2
 */
?>

<?php  get_sensei_header();  ?>

<?php

/**
 * sensei_lesson_tag_main_content hook
 *
 * @hooked sensei_lesson_tag_main_content - 10 (outputs main content loop)
 */
do_action('sensei_lesson_tag_main_content');

?>

<?php get_sensei_footer(); ?>

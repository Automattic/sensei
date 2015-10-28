<?php
/**
 * The Template for displaying course archives for the course category taxonomy terms.
 *
 * Override this template by copying it to yourtheme/sensei/taxonomy-course-category.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.1.0
 */
?>

<?php  get_sensei_header();  ?>

<?php
/**
 * sensei_course_category_main_content hook
 *
 * @hooked sensei_course_category_main_content - 10 (outputs main content loop)
 */
do_action('sensei_course_category_main_content');
?>

<?php get_sensei_footer(); ?>



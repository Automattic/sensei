<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */
global $woothemes_sensei;
get_header(); ?>

		<?php
		/**
		 * sensei_before_main_content hook
		 *
		 */
		do_action('sensei_before_main_content');
		?>
	
		<?php while ( have_posts() ) : the_post(); ?>

			<?php $woothemes_sensei->frontend->sensei_get_template_part( 'content', 'single-course' ); ?>

		<?php endwhile; // end of the loop. ?>

		<?php
		/**
		 * sensei_after_main_content hook
		 *
		 */
		do_action('sensei_after_main_content');
		?>
	
		<?php
		/**
		 * sensei_sidebar hook
		 *
		 * @hooked sensei_get_sidebar - 10
		 */
		do_action('sensei_sidebar');
		?>
	
<?php get_footer(); ?>
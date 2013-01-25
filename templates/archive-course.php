<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to yourtheme/sensei/archive-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

global $woothemes_sensei, $wp_query;
get_header(); ?>

	<?php
		/**
		 * sensei_before_main_content hook
		 *
		 * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked sensei_breadcrumb - 20
		 */
		do_action('sensei_before_main_content');
	?>
		
		<?php 
			
			if ( have_posts() ) {
				// Handle pagiation
				$paged = $wp_query->get( 'paged' );
				if ( ! $paged || $paged < 2 ) {
					// This is not a paginated page (or it's simply the first page of a paginated page/post)
					do_shortcode( '[newcourses]' ); 
					do_shortcode( '[featuredcourses]' ); 
					do_shortcode( '[freecourses]' ); 
					do_shortcode( '[paidcourses]' ); 
				} else {
					// This is a paginated page.
					$woothemes_sensei->frontend->sensei_get_template( 'loop-course.php' );
				} // End If Statement
				
			} else { ?>

			<p><?php _e( 'No courses found which match your selection.', 'woothemes-sensei' ); ?></p>
			
		<?php } ?>
		
		<?php do_action('sensei_pagination'); ?>
		
	<?php
		/**
		 * sensei_after_main_content hook
		 *
		 * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
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
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

			if ( have_posts() ) { ?>
				<section id="main-course" class="course-container">

		    	    <header class="archive-header">

		    	    	<?php echo sensei_course_archive_header(); ?>

		    	    </header>

		    	    <div class="fix"></div>

		    	    <?php while ( have_posts() ) { the_post();
		    			// Meta data
		    			$post_id = get_the_ID();
		    			$post_title = get_the_title();
		    			$author_display_name = get_the_author();
		    			$author_id = get_the_author_meta('ID');
		    			$category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
		 			?>

					<article class="<?php echo join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ); ?>">
		    			<?php
		    			// Image
		    			echo  $woothemes_sensei->post_types->course->course_image( $post_id );
		    			?>
		    			<header>
		    				<h2><a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo $post_title; ?></a></h2>
		    			</header>

		    			<section class="entry">
		                	<p class="sensei-course-meta">
                           	<?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
    					   	<span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
    					   	<?php } // End If Statement ?>
    					   	<span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_author_lesson_count( $author_id, $post_id ) . '&nbsp;' . __( 'Lectures', 'woothemes-sensei' ); ?></span>
    					   	<?php if ( '' != $category_output ) { ?>
    					   	<span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
    					   	<?php } // End If Statement ?>
    					   	<?php sensei_simple_course_price( $post_id ); ?>
                        	</p>
                        	<p><?php echo apply_filters( 'get_the_excerpt', $post_item->post_excerpt ); ?></p>
		    			</section>
		    		</article>

		    		<div class="fix"></div>

		    		<?php } // End While Loop ?>

		    	</section>

			<?php } else { ?>

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
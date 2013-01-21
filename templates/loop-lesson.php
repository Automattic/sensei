<?php
/**
 * The Template for outputting Lesson Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

global $woothemes_sensei, $post, $wp_query; 
?>
    
    <?php if ( have_posts() ) { ?>
	
		<section id="main-course" class="course-container">
    	    
    	    <header class="archive-header">
    	    	
    	    	<h1><?php _e( 'Lessons Archive', 'woothemes-sensei' ); ?></h1>
    	    
    	    </header>
    	    
    	    <div class="fix"></div>
    	      
    	    <?php while ( have_posts() ) { the_post();
    			// Meta data
    			$post_id = get_the_ID();
    			$post_title = get_the_title();
    			$author_display_name = get_the_author();
    			$author_id = get_the_author_meta('ID');
                $lesson_course_id = get_post_meta( $post_id, '_lesson_course', true );
 			?>
    		
			<article class="<?php echo join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ); ?>">
    			<?php
    			// Image
    			echo  $woothemes_sensei->post_types->lesson->lesson_image( $post_id );
    			?>
    			<header>
    				<h2><a href="<?php echo get_permalink( $post_id ); ?>" title="<?php echo esc_attr( $post_title ); ?>"><?php echo $post_title; ?></a></h2>
    			</header>
    			
    			<section class="entry">
                    <p class="sensei-course-meta">
    				    <?php if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) { ?>
    				    <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
    				    <?php } ?>
                        <?php if ( 0 < $lesson_course_id ) { ?>
                        <span class="lesson-course"><?php echo '&nbsp;' . sprintf( __( 'Part of: %s', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_course_id ) ) . '" title="' . esc_attr( __( 'View course', 'woothemes-sensei' ) ) . '"><em>' . get_the_title( $lesson_course_id ) . '</em></a>' ); ?></span>
                        <?php } ?>
                    </p>
                    <p><?php the_excerpt(); ?></p>			
    			</section>
    		</article>
    		
    		<div class="fix"></div>
    	
    		<?php } // End While Loop ?>
    	    
    	</section>
    <?php } ?>
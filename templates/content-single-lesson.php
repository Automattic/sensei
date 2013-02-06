<?php
/**
 * The template for displaying product content in the single-lessons.php template
 *
 * Override this template by copying it to yourtheme/sensei/content-single-lesson.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */
 global $woothemes_sensei, $post;
?>
        	<article <?php post_class( array( 'lesson', 'post' ) ); ?>>
				
				<?php
				// Image
    			echo $woothemes_sensei->post_types->lesson->lesson_image( $post->ID );
    			?>
    			
                <header>
                
	                <h1><?php the_title(); ?></h1>
	                
                </header>

                <?php
                $lesson_prerequisite = get_post_meta( $post->ID, '_lesson_prerequisite', true );
                // Check for prerequisite lesson completions
				$user_prerequisite_lesson_end =  WooThemes_Sensei_Utils::sensei_get_activity_value( array( 'post_id' => $lesson_prerequisite, 'user_id' => $current_user->ID, 'type' => 'sensei_lesson_end', 'field' => 'comment_content' ) );
				$user_lesson_prerequisite_complete = false;
				if ( '' != $user_prerequisite_lesson_end ) {
				    $user_lesson_prerequisite_complete = true;
				}
				
				if ( $lesson_prerequisite > 0) {
                    if ( isset( $user_lesson_prerequisite_complete ) && $user_lesson_prerequisite_complete ) {
                ?>
                
                <section class="entry fix">
                	<?php the_content(); ?>
				</section>

				<?php lesson_single_meta(); ?>

				<?php } else {
						echo sprintf( __( 'You must first complete %1$s before viewing this Lesson', 'woothemes-sensei' ), '<a href="' . esc_url( get_permalink( $lesson_prerequisite ) ) . '" title="' . esc_attr(  sprintf( __( 'You must first complete: %1$s', 'woothemes-sensei' ), get_the_title( $lesson_prerequisite ) ) ) . '">' . get_the_title( $lesson_prerequisite ). '</a>' );
					}
				} ?>
				                
            </article><!-- .post -->

	        <nav id="post-entries" class="fix">
	            <div class="nav-prev fl"><?php previous_post_link( '%link', '<span class="meta-nav">&larr;</span> %title' ); ?></div>
	            <div class="nav-next fr"><?php next_post_link( '%link', '%title <span class="meta-nav">&rarr;</span>' ); ?></div>
	        </nav><!-- #post-entries -->
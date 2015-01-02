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

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $wp_query, $current_user;

wp_get_current_user();
$lesson_count = 1;

if ( have_posts() ) { ?>
	<section id="main-course" class="course-container">
		<section class="module-lessons">

			<?php do_action( 'sensei_lesson_archive_header' ); ?>

			<?php while ( have_posts() ) { the_post();
				// Meta data
				$post_id = get_the_ID(); 

				$user_lesson_status = false;
				if ( is_user_logged_in() ) {
					// Check if Lesson is complete
					$user_lesson_status = WooThemes_Sensei_Utils::user_completed_lesson( $post_id, $current_user->ID );
				} // End If Statement
				// Get Lesson data
				$complexity_array = $woothemes_sensei->frontend->lesson->lesson_complexities();
				$lesson_length = get_post_meta( $post_id, '_lesson_length', true );
				$lesson_complexity = get_post_meta( $post_id, '_lesson_complexity', true );
				if ( '' != $lesson_complexity ) { $lesson_complexity = $complexity_array[$lesson_complexity]; }
				$user_info = get_userdata( absint( get_the_author_meta( 'ID' ) ) );

				$html = '<article class="' . esc_attr( join( ' ', get_post_class( array( 'lesson', 'course', 'post' ), $post_id ) ) ) . '">';

					$html .= '<header>';

						$html .= '<h2><a href="' . esc_url( get_permalink( $post_id ) ) . '" title="' . esc_attr( sprintf( __( 'Start %s', 'woothemes-sensei' ), get_the_title() ) ) . '">';

						if( apply_filters( 'sensei_show_lesson_numbers', false ) ) {
							$html .= '<span class="lesson-number">' . $lesson_count . '. </span>';
						}

						$html .= esc_html( sprintf( __( '%s', 'woothemes-sensei' ), get_the_title() ) ) . '</a></h2>';

						$html .= '<p class="lesson-meta">';

							// Show length of lesson
							if ( '' != $lesson_length ) { 
								$html .= '<span class="lesson-length">' . apply_filters( 'sensei_length_text', __( 'Time: ', 'woothemes-sensei' ) ) . $lesson_length . __( ' minutes', 'woothemes-sensei' ) . '</span>'; 
							}

							// Show Author of lesson
//							if ( isset( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) && ( $woothemes_sensei->settings->settings[ 'lesson_author' ] ) ) {
//								$html .= '<span class="lesson-author">' . apply_filters( 'sensei_author_text', __( 'Author: ', 'woothemes-sensei' ) ) . '<a href="' . get_author_posts_url( absint( get_the_author_meta( 'ID' ) ) ) . '" title="' . esc_attr( $user_info->display_name ) . '">' . esc_html( $user_info->display_name ) . '</a></span>';
//							} // End If Statement

							// Lesson complexity (standard etc)
							if ( '' != $lesson_complexity ) { 
								$html .= '<span class="lesson-complexity">' . apply_filters( 'sensei_complexity_text', __( 'Complexity: ', 'woothemes-sensei' ) ) . $lesson_complexity .'</span>'; 
							}

							// Show the lesson Tags
							$tags = wp_get_post_terms( $post_id, 'lesson-tag' );
							if( $tags && count( $tags ) > 0 ) {
								$tag_list = '';
								foreach( $tags as $tag ) {
									$tag_link = get_term_link( $tag, 'lesson-tag' );
									if( ! is_wp_error( $tag_link ) ) {
//										if( $tag_list ) { 
//											$tag_list .= ', '; 
//										}
//										$tag_list .= '<a href="' . $tag_link . '" class="tag">' . $tag->name . '</a>';
										$tag_list .= '<span class="tag">' . $tag->name . '</span>';
									}
								}
								if( $tag_list ) {
//									$html .= '<span class="lesson-tags">' . sprintf( __( 'Lesson tags: %1$s', 'woothemes-sensei' ), $tag_list ) . '</span>';
									$html .= '<span class="lesson-tags">' . $tag_list . '</span>';
								}
							}

							// Mark if complete or not
							if ( $user_lesson_status ) {
								$html .= '<span class="lesson-status complete">' . apply_filters( 'sensei_complete_text', __( 'Complete', 'woothemes-sensei' ) ) .'</span>';
							} 
							elseif ( false != $user_lesson_status ) {
								$html .= '<span class="lesson-status in-progress">' . apply_filters( 'sensei_in_progress_text', __( 'In Progress', 'woothemes-sensei' ) ) .'</span>';
							}

						$html .= '</p>';

					$html .= '</header>';

					// Image
					$html .=  $woothemes_sensei->post_types->lesson->lesson_image( $post_id );

					$html .= '<section class="entry">';

						$html .= Woothemes_Sensei_Lesson::lesson_excerpt( $post );

					$html .= '</section>';

				$html .= '</article>';

				echo $html;

				?>

			<?php } // End While Loop ?>

		</section><!-- .module-lessons -->
	</section><!-- .course-container -->
<?php } 

<?php
/**
 * The Template for outputting Course Archive items
 *
 * Override this template by copying it to yourtheme/sensei/loop-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $woothemes_sensei, $post, $wp_query, $shortcode_override, $course_excludes, $current_user;
// Handle Query Type
$query_type = '';
if ( isset( $_GET[ 'action' ] ) && ( '' != esc_html( $_GET[ 'action' ] ) ) ) {
    $query_type = esc_html( $_GET[ 'action' ] );
} // End If Statement
if ( '' != $shortcode_override ) {
	$query_type = $shortcode_override;
} // End If Statement
if ( !is_array( $course_excludes ) ) { $course_excludes = array(); } ?>
<?php
// Check that query returns results
// Handle Pagination
$paged = $wp_query->get( 'paged' );
if ( ! $paged || $paged < 2 ) {
    // Check for pagination settings
    if ( isset( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) && ( 0 < absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] ) ) ) {
    	$amount = absint( $woothemes_sensei->settings->settings[ 'course_archive_amount' ] );
    } else {
    	$amount = $wp_query->get( 'posts_per_page' );
    } // End If Statement
    // This is not a paginated page (or it's simply the first page of a paginated page/post)
    $course_includes = array();
    $posts_array = $woothemes_sensei->post_types->course->course_query( $amount, $query_type, $course_includes, $course_excludes );
    if ( count( $posts_array ) > 0 ) { ?>

    	<section id="main-course" class="course-container">

    	    <?php do_action( 'sensei_course_archive_header', $query_type ); ?>

    	    <?php foreach ($posts_array as $post_item){
    			// Make sure the other loops dont include the same post twice!
    			array_push( $course_excludes, $post_item->ID );
    			// Get meta data
    			$post_id = absint( $post_item->ID );
    			$post_title = $post_item->post_title;
    			$user_info = get_userdata( absint( $post_item->post_author ) );
    			$author_link = get_author_posts_url( absint( $post_item->post_author ) );
    			$author_display_name = $user_info->display_name;
    			$author_id = $post_item->post_author;
                $category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
                $preview_lesson_count = intval( $woothemes_sensei->post_types->course->course_lesson_preview_count( $post_id ) );
				$is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post_id, $current_user->ID );
    			?>
    			<article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $post_id ) ) ); ?>">

                    <?php do_action( 'sensei_course_image', $post_id ); ?>

    				<?php do_action( 'sensei_course_archive_course_title', $post_item ); ?>

    				<section class="entry">
    					<p class="sensei-course-meta">
                           <?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
    					   <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><a href="<?php echo $author_link; ?>" title="<?php echo esc_attr( $author_display_name ); ?>"><?php echo esc_html( $author_display_name   ); ?></a></span>
    					   <?php } // End If Statement ?>
    					   <span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
                           <?php if ( '' != $category_output ) { ?>
                           <span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
                           <?php } // End If Statement ?>
    					   <?php sensei_simple_course_price( $post_id ); ?>
                        </p>
                        <p class="course-excerpt"><?php echo sensei_get_excerpt( $post_item ); ?></p>
                        <?php if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {

                            $preview_lessons = sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ); ?>
                            <p class="sensei-free-lessons"><a href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Preview this course', 'woothemes-sensei' ) ?></a> - <?php echo $preview_lessons; ?></p>

                        <?php } ?>
    				</section>
    			</article>
    			<?php

    		} // End For Loop

    		if ( '' != $shortcode_override && ( $amount <= count( $posts_array ) ) ) {
    			echo sensei_course_archive_next_link( $query_type );
    		} // End If Statement ?>

    	</section>

    <?php } // End If Statement
} else {
    // This is a paginated page.
    // V2 - refactor this into a filter
	if ( !is_post_type_archive( 'course' ) ) {
		$query_args = $woothemes_sensei->post_types->course->get_archive_query_args( $query_type );
		query_posts( $query_args );
	} // End If Statement
	if ( have_posts() ) { ?>

		<section id="main-course" class="course-container">

    	    <?php do_action( 'sensei_course_archive_header', $query_type ); ?>

    	    <?php while ( have_posts() ) { the_post();
    			// Meta data
    			$post_id = get_the_ID();
    			$author_display_name = get_the_author();
    			$author_id = get_the_author_meta('ID');
                $category_output = get_the_term_list( $post_id, 'course-category', '', ', ', '' );
                $preview_lesson_count = intval( $woothemes_sensei->post_types->course->course_lesson_preview_count( $post_id ) );
				$is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( $post_id, $current_user->ID );
 			?>

			<article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ) ); ?>">

                <?php do_action( 'sensei_course_image', $post_id ); ?>

    			<?php do_action( 'sensei_course_archive_course_title', $post ); ?>

    			<section class="entry">
                    <p class="sensei-course-meta">
    				    <?php if ( isset( $woothemes_sensei->settings->settings[ 'course_author' ] ) && ( $woothemes_sensei->settings->settings[ 'course_author' ] ) ) { ?>
    				    <span class="course-author"><?php _e( 'by ', 'woothemes-sensei' ); ?><?php the_author_link(); ?></span>
    				    <?php } ?>
    				    <span class="course-lesson-count"><?php echo $woothemes_sensei->post_types->course->course_lesson_count( $post_id ) . '&nbsp;' . apply_filters( 'sensei_lessons_text', __( 'Lessons', 'woothemes-sensei' ) ); ?></span>
    				    <?php if ( '' != $category_output ) { ?>
                        <span class="course-category"><?php echo sprintf( __( 'in %s', 'woothemes-sensei' ), $category_output ); ?></span>
                        <?php } // End If Statement ?>
                        <?php sensei_simple_course_price( $post_id ); ?>
                    </p>

                    <p class="course-excerpt"><?php echo sensei_get_excerpt( $post ) ?></p>
                    <?php if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {
                            $preview_lessons = sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ); ?>
                            <p class="sensei-free-lessons"><a href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Preview this course', 'woothemes-sensei' ) ?></a> - <?php echo $preview_lessons; ?></p>
                    <?php } ?>

    			</section>
    		</article>

    		<?php } // End While Loop ?>

    	</section>

	<?php } // End If Statement

} // End If Statement
?>
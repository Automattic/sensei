<?php
/**
 * The Template for displaying all single lessons.
 *
 * Override this template by copying it to yourtheme/sensei/single-lesson.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

get_sensei_header();

if ( have_posts() ) {
	the_post();
}
?>

<?php

/**
 * Hook inside the single lesson above the content
 *
 * @param integer $lesson_id
 *
 * @hooked deprecated_lesson_image_hook - 10
 * @hooked Sensei_Lesson::lesson_image() -  17
 * @hooked deprecate_lesson_single_main_content_hook - 20
 * @since  1.9.0
 */
do_action( 'sensei_single_lesson_content_inside_before', get_the_ID() );

if ( sensei_can_user_view_lesson() ) {

	/**
	 * Filter video position.
	 *
	 * @hook sensei_video_position
	 *
	 * @param {string} $position The video position.
	 * @param {int}    $post_id  The post ID.
	 * @return {string} Filtered video position.
	 */
	$sensei_video_position = apply_filters( 'sensei_video_position', 'top', $post->ID );

	if ( 'top' === $sensei_video_position ) {

		/**
		 * Fire action when a lesson video expected.
		 *
		 * @hook sensei_lesson_video
		 *
		 * @param {int} $post_id The post ID.
		 */
		do_action( 'sensei_lesson_video', $post->ID );

	}

	the_content();

} else {
	?>

	<p>

		<?php echo wp_kses_post( get_the_excerpt() ); ?>

	</p>

	<?php
}

/**
 * Hook inside the single lesson template after the content
 *
 * @param integer $lesson_id
 *
 * @hooked Sensei()->frontend->sensei_breadcrumb   - 30
 * @since  1.9.0
 */
do_action( 'sensei_single_lesson_content_inside_after', get_the_ID() );

get_sensei_footer();

<?php
/**
 * The Template for displaying all single lessons.
 *
 * Override this template by copying it to yourtheme/sensei/single-lesson.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     1.12.2
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

<article <?php post_class( array( 'lesson', 'post' ) ); ?>>

	<?php

		/**
		 * Hook inside the single lesson above the content
		 *
		 * @since 1.9.0
		 *
		 * @param integer $lesson_id
		 *
		 * @hooked deprecated_lesson_image_hook - 10
		 * @hooked Sensei_Lesson::maybe_start_lesson - 10
		 * @hooked Sensei_Lesson::the_title - 15
		 * @hooked Sensei_Lesson::lesson_image -  17
		 * @hooked Sensei_Lesson::user_lesson_quiz_status_message - 20
		 * @hooked Sensei_Lesson::prerequisite_complete_message - 20
		 * @hooked deprecate_lesson_single_main_content_hook - 20
		 * @hooked Sensei_Lesson::course_signup_link - 30
		 * @hooked Sensei_Lesson::login_notice - 30
		 * @hooked Sensei_Messages::send_message_link - 30
		 * @hooked Sensei_Notices::maybe_print_notices 40
		 */
		do_action( 'sensei_single_lesson_content_inside_before', get_the_ID() );

	?>

	<section class="entry fix">

		<?php

		if ( sensei_can_user_view_lesson() ) {

			if ( apply_filters( 'sensei_video_position', 'top', $post->ID ) == 'top' ) {

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

		?>

	</section>

	<?php

		/**
		 * Hook inside the single lesson template after the content
		 *
		 * @since 1.9.0
		 *
		 * @param integer $lesson_id
		 *
		 * @hooked Sensei()->frontend->sensei_breadcrumb   - 30
		 */
		do_action( 'sensei_single_lesson_content_inside_after', get_the_ID() );

	?>

</article><!-- .post -->

<?php get_sensei_footer(); ?>

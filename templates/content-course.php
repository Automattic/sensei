<?php
/**
 * Content-course.php template file
 *
 * responsible for content on archive like pages. Only shows the course excerpt.
 *
 * For single course content please see single-course.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<li <?php post_class( Sensei_Course::get_course_loop_content_class() ); ?> >

	<?php
	/**
	 * This action runs before the sensei course content. It runs inside the sensei
	 * content-course.php template.
	 *
	 * @since 1.9
	 *
	 * @param integer $course_id
	 */
	do_action( 'sensei_course_content_before', get_the_ID() );
	?>

	<section class="course-content">

		<section class="entry">

			<?php
			/**
			 * Fires just before the course content in the content-course.php file.
			 *
			 * @since 1.9
			 *
			 * @param integer $course_id
			 *
			 * @hooked Sensei_Templates::the_title          - 5
			 * @hooked Sensei()->course->course_image       - 10
			 * @hooked  Sensei()->course->the_course_meta   - 20
			 */
			do_action( 'sensei_course_content_inside_before', get_the_ID() );
			?>

			<?php if ( get_the_excerpt() ) : ?>

			<p class="course-excerpt">

				<?php echo wp_kses_post( get_the_excerpt() ); ?>

			</p>

			<?php endif; ?>

			<?php
			/**
			 * Fires just after the course content in the content-course.php file.
			 *
			 * @since 1.9
			 *
			 * @param integer $course_id
			 *
			 * @hooked  Sensei()->course->the_course_free_lesson_preview - 20
			 */
			do_action( 'sensei_course_content_inside_after', get_the_ID() );
			?>

		</section> <!-- section .entry -->

	</section> <!-- section .course-content -->

	<?php
	/**
	 * Fires after the course block in the content-course.php file.
	 *
	 * @since 1.9
	 *
	 * @param integer $course_id
	 *
	 * @hooked  Sensei()->course->the_course_free_lesson_preview - 20
	 */
	do_action( 'sensei_course_content_after', get_the_ID() );
	?>


</li>

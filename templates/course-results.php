<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to yourtheme/sensei/archive-course.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_sensei_header();

/**
 * This hook fire inside learner-profile.php before the content
 *
 * @since 1.9.0
 *
 * @hooked Sensei_Course_Results::deprecate_sensei_course_results_content_hook() - 20
 */
do_action( 'sensei_course_results_content_before' );
?>

<?php
global $course, $wp_query;
$course = get_page_by_path( $wp_query->query_vars['course_results'], OBJECT, 'course' );
?>

<div class="course course-results">


		<?php
		/**
		 * This hook fire inside learner-profile.php inside directly before the content
		 *
		 * @since 1.9.0
		 *
		 * @param integer $course_id
		 */
		do_action( 'sensei_course_results_content_inside_before', $course->ID );
		?>

		<header>

			<h1>
				<?php echo esc_html( $course->post_title ); ?>
			</h1>

		</header>

		<?php if ( is_user_logged_in() ) : ?>

			<?php
			/**
			 * This hook fire inside learner-profile.php inside directly before the content
			 *
			 * @since 1.9.0
			 *
			 * @param integer $course_id
			 *
			 * @hooked Sensei_Course_Results::course_info() - 20
			 */
			do_action( 'sensei_course_results_content_inside_before_lessons', $course->ID );
			?>


			<section class="course-results-lessons">
				<?php
				if ( Sensei_Utils::has_started_course( $course->ID, get_current_user_id() ) ) {

					sensei_the_course_results_lessons();

				}
				?>
			</section>

		<?php endif; ?>

		<?php
		/**
		 * This hook fire inside learner-profile.php inside directly after the content
		 *
		 * @since 1.9.0
		 *
		 * @param integer $course_id
		 *
		 * @hooked Sensei()->course_results->course_info - 20
		 */
		do_action( 'sensei_course_results_content_inside_after', $course->ID );
		?>

</div>

<?php
/**
 * This hook fire inside course-results.php before the content
 *
 * @since 1.9.0
 */
do_action( 'sensei_course_results_content_after' );

get_sensei_footer();

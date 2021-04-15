<?php
/**
 * The Template for displaying all course lessons on the course results page.
 *
 * Override this template by copying it to yourtheme/sensei/course-results/course-lessons.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $course;
?>

<?php if ( is_user_logged_in() ) : ?>

	<?php
	/**
	 * Fires inside course-results/lessons.php after the
	 * is uer logged check, just above the lessons header.
	 *
	 * @since 1.4.0
	 */
	do_action( 'sensei_course_results_before_lessons', $course->ID );
	?>

	<header>

		<h2>
			<?php
			if ( 1 === Sensei()->course->course_lesson_count( $course->ID ) ) {
				esc_html_e( 'Lesson', 'sensei-lms' );
			} else {
				esc_html_e( 'Lessons', 'sensei-lms' );
			}
			?>
		</h2>

	</header>

	<div class="lesson-result">

		<?php

		$displayed_lessons = array();
		$modules           = Sensei()->modules->get_course_modules( intval( $course->ID ) );

		// List modules with lessons
		$course_has_lessons_in_modules = false;
		foreach ( $modules as $module ) {

			$lessons_query = Sensei()->modules->get_lessons_query( $course->ID, $module->term_id );
			$lessons       = $lessons_query->get_posts();

			if ( count( $lessons ) > 0 ) {

				$course_has_lessons_in_modules = true;

				?>

				<h3> <?php echo esc_html( $module->name ); ?></h3>

				<?php
				$count = 0;
				foreach ( $lessons as $lesson ) {

					$lesson_grade  = 'n/a';
					$has_questions = Sensei_Lesson::lesson_quiz_has_questions( $lesson->ID );
					if ( $has_questions ) {
						$lesson_status = Sensei_Utils::user_lesson_status( $lesson->ID, get_current_user_id() );
						if ( $lesson_status ) {
							// Get user quiz grade
							$lesson_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
							if ( $lesson_grade ) {
								$lesson_grade .= '%';
							}
						}
					}
					?>
					<h4>

						<a href="<?php echo esc_url_raw( get_permalink( $lesson->ID ) ); ?>"
						   title="
						   <?php
							// translators: Placeholder is the lesson title.
							echo esc_attr( sprintf( __( 'Start %s', 'sensei-lms' ), $lesson->post_title ) );
							?>
						 ">

							<?php echo esc_html( $lesson->post_title ); ?>

						</a>

						<span class="lesson-grade">
							<?php echo esc_html( $lesson_grade ); ?>
						</span>

					</h4>

					<?php

				}
			}
		}
		?>

		<?php

		$lessons = Sensei()->modules->get_none_module_lessons( $course->ID );
		if ( 0 < count( $lessons ) ) :
			?>

			<?php
			// lesson title will already appear above
			if ( $course_has_lessons_in_modules ) :
				?>
				<h2><?php esc_html_e( 'Other Lessons', 'sensei-lms' ); ?></h2>
			<?php endif; ?>

			<?php foreach ( $lessons as $lesson ) : ?>

				<?php
				$lesson_grade  = 'n/a';
				$has_questions = Sensei_Lesson::lesson_quiz_has_questions( $lesson->ID );
				if ( $has_questions ) {
					$lesson_status = Sensei_Utils::user_lesson_status( $lesson->ID, get_current_user_id() );
					// Get user quiz grade
					$lesson_grade = '';
					if ( ! empty( $lesson_status ) ) {
						$lesson_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
						if ( $lesson_grade ) {
							$lesson_grade .= '%';
						}
					}
				}
				?>

				<h3>

					<a href="<?php echo esc_url_raw( get_permalink( $lesson->ID ) ); ?>" title="
						<?php
						// translators: Placeholder is the lesson title.
						esc_attr( sprintf( __( 'Start %s', 'sensei-lms' ), $lesson->post_title ) )
						?>
					" >

						<?php echo esc_html( $lesson->post_title ); ?>

					</a>

					<span class="lesson-grade"><?php echo esc_html( $lesson_grade ); ?></span>

				</h3>

			<?php endforeach; // lessons ?>

		<?php endif; // lessons count > 0 ?>


		<h2 class="total-grade">

			<?php esc_html_e( 'Total Grade', 'sensei-lms' ); ?>
			<span class="lesson-grade">

				<?php

					$course_user_grade = Sensei_Utils::sensei_course_user_grade( $course->ID, get_current_user_id() );
					echo esc_html( $course_user_grade ) . '%';

				?>

			</span>

		</h2>

	</div>

	<?php
	/**
	 * Fires inside course-results/lessons.php after the
	 * is uer logged check, at the bottom of all lessons.
	 *
	 * @since 1.4.0
	 */
	do_action( 'sensei_course_results_after_lessons', $course->ID );
	?>

<?php endif; // user logged in ?>

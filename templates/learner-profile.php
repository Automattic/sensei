<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to yourtheme/sensei/archive-course.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     2.0.0
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
 * @hooked Sensei_Learner_Profiles::deprecate_sensei_learner_profile_content_hook   - 10
 * @hooked Sensei_Templates::fire_sensei_complete_course_hook                      - 20
 */
do_action( 'sensei_learner_profile_content_before' );
?>

<article class="post">

	<section id="learner-info" class="learner-info entry fix">

		<?php
		/**
		 * This hook fire inside learner-profile.php inside directly before the content
		 *
		 * @since 1.9.0
		 *
		 * @hooked  Sensei_Templates::fire_frontend_messages_hook
		 */
		do_action( 'sensei_learner_profile_inside_content_before' );
		?>

		<?php $learner_user = Sensei_Learner::find_by_query_var( get_query_var( 'learner_profile' ) ); ?>

		<?php if ( is_a( $learner_user, 'WP_User' ) ) { ?>

			<?php

			// show the user information
			Sensei_Learner_Profiles::user_info( $learner_user );

			?>

			<?php

			// show the user courses
			Sensei()->course->load_user_courses_content( $learner_user );

			?>

		<?php } else { ?>

			<p class="sensei-message">

				<?php esc_html_e( 'The user requested does not exist.', 'sensei-lms' ); ?>

			</p>

		<?php } ?>

		<?php
		/**
		 * This hook fire inside learner-profile.php inside directly after the content
		 *
		 * @since 1.9.0
		 */
		do_action( 'sensei_learner_profile_inside_content_after' );
		?>

	</section>

</article>

<?php
/**
 * This hook fire inside learner-profile.php after the content
 *
 * @since 1.9.0
 */
do_action( 'sensei_learner_profile_content_after' );
?>

<?php get_sensei_footer(); ?>

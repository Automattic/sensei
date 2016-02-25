<?php
/**
 * The Template for displaying course archives, including the course page template.
 *
 * Override this template by copying it to yourtheme/sensei/archive-course.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php  get_sensei_header();  ?>

<?php
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
global $course;
$course = get_page_by_path( $wp_query->query_vars['course_results'], OBJECT, 'course' );
?>

<article <?php post_class( array( 'course', 'post','course-results' ) ); ?> >

    <section class="entry fix">

        <?php
        /**
         * This hook fire inside learner-profile.php inside directly before the content
         *
         * @since 1.9.0
         *
         * @param integer $course_id
         *
         */
        do_action( 'sensei_course_results_content_inside_before', $course->ID );
        ?>

        <header>

            <h1>
                <?php echo $course->post_title; ?>
            </h1>

        </header>

        <?php if ( is_user_logged_in() ):?>

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
                $started_course = Sensei_Utils::user_started_course( $course->ID, get_current_user_id() );
                if( $started_course ) {

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

    </section>

</article>

<?php
/**
 * This hook fire inside course-results.php before the content
 *
 * @since 1.9.0
 *
 */
do_action( 'sensei_course_results_content_after' );
?>


<?php get_sensei_footer(); ?>
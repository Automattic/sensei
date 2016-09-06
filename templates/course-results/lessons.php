<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying all course lessons on the course results page.
 *
 * Override this template by copying it to yourtheme/sensei/course-results/course-lessons.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */

global $course;
?>

<?php if ( is_user_logged_in() ): ?>

    <?php
    /**
     * Fires inside course-results/lessons.php after the
     * is uer logged check, just above the lessons header.
     * @since 1.4.0
     */
    do_action( 'sensei_course_results_before_lessons', $course->ID );
    ?>

    <header>

        <h2>  <?php _e( 'Lessons', 'woothemes-sensei' );  ?> </h2>

    </header>

    <article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), $course->ID ) ) ); ?> ">

        <?php

		$displayed_lessons = array();
        $modules = Sensei()->modules->get_course_modules( intval( $course->ID ) );

        // List modules with lessons
        $course_has_lessons_in_modules = false;
        foreach( $modules as $module ) {

            $lessons_query = Sensei()->modules->get_lessons_query( $course->ID, $module->term_id );
            $lessons = $lessons_query->get_posts();

            if( count( $lessons ) > 0 ) {

	            $course_has_lessons_in_modules = true;

	            ?>

                <h3> <?php echo $module->name; ?></h3>

                <?php
                $count = 0;
                foreach( $lessons as $lesson ) {

                    $lesson_grade = 'n/a';
                    $has_questions = get_post_meta( $lesson->ID, '_quiz_has_questions', true );
                    if ( $has_questions ) {
                        $lesson_status = Sensei_Utils::user_lesson_status( $lesson->ID, get_current_user_id() );
                        // Get user quiz grade
                        $lesson_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
                        if ( $lesson_grade ) {
                            $lesson_grade .= '%';
                        }
                    }
                    ?>
                    <h2>

                        <a href="<?php echo esc_url_raw( get_permalink( $lesson->ID ) ); ?>"
                           title="<?php echo esc_attr_e( sprintf( __( 'Start %s', 'woothemes-sensei' ), $lesson->post_title ) ); ?>">

                            <?php echo esc_html( $lesson->post_title ); ?>

                        </a>

                        <span class="lesson-grade">
                            <?php echo $lesson_grade; ?>
                        </span>

                    </h2>

                <?php

                }// end for each

            }// end if count lesson

        } // end for each module
        ?>

        <?php

        $lessons = Sensei()->modules->get_none_module_lessons( $course->ID );
        if( 0 < count( $lessons ) ): ?>

			<h3>

                <?php
                // lesson title will already appear above
                if ( $course_has_lessons_in_modules ) {
	                _e( 'Other Lessons', 'woothemes-sensei' );
                }
                ?>

            </h3>

            <?php foreach ( $lessons as $lesson ): ?>

                <?php
                $lesson_grade = 'n/a';
                $has_questions = get_post_meta( $lesson->ID, '_quiz_has_questions', true );
                if ( $has_questions ) {
                    $lesson_status = Sensei_Utils::user_lesson_status( $lesson->ID, get_current_user_id());
                    // Get user quiz grade
	                $lesson_grade = '';
	                if( ! empty( $lesson_status ) ) {
		                $lesson_grade = get_comment_meta( $lesson_status->comment_ID, 'grade', true );
		                if ( $lesson_grade ) {
			                $lesson_grade .= '%';
		                }
	                }
                }
                ?>

                <h2>

                    <a href="<?php echo esc_url_raw( get_permalink( $lesson->ID ) ) ?>" title="<?php esc_attr_e( sprintf( __( 'Start %s', 'woothemes-sensei' ), $lesson->post_title ) ) ?>" >

                        <?php esc_html_e( sprintf( __( '%s', 'woothemes-sensei' ), $lesson->post_title ) ); ?>

                    </a>

                    <span class="lesson-grade"><?php echo  $lesson_grade; ?></span>

                </h2>

            <?php endforeach; // lessons ?>

        <?php endif; // lessons count > 0  ?>


        <h2 class="total-grade">

            <?php _e( 'Total Grade', 'woothemes-sensei' ); ?>
            <span class="lesson-grade">

                <?php

                    $course_user_grade = Sensei_Utils::sensei_course_user_grade( $course->ID, get_current_user_id() );
                    echo $course_user_grade . '%';

                ?>

            </span>

        </h2>

    </article>

    <?php
    /**
     * Fires inside course-results/lessons.php after the
     * is uer logged check, at the bottom of all lessons.
     *
     * @since 1.4.0
     */
	do_action( 'sensei_course_results_after_lessons', $course->ID );
    ?>

<?php endif; //user logged in ?>
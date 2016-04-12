<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying all single course meta information.
 *
 * Override this template by copying it to yourtheme/sensei/single-course/course-lessons.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<section class="course-lessons">

    <?php

        /**
         * Actions just before the sensei single course lessons loop begins
         *
         * @hooked WooThemes_Sensei_Course::load_single_course_lessons_query
         * @since 1.9.0
         */
        do_action( 'sensei_single_course_lessons_before' );

    ?>

    <?php

    //lessons loaded into loop in the sensei_single_course_lessons_before hook
    if( have_posts() ):

        // start course lessons loop
        while ( have_posts() ): the_post();  ?>

            <article <?php post_class(); ?> >

                <?php

                    /**
                     * The hook is inside the course lesson on the single course. It fires
                     * for each lesson. It is just before the lesson excerpt.
                     *
                     * @since 1.9.0
                     *
                     * @param $lessons_id
                     *
                     * @hooked WooThemes_Sensei_Lesson::the_lesson_meta -  5
                     * @hooked WooThemes_Sensei_Lesson::the_lesson_thumbnail - 8
                     *
                     */
                    do_action( 'sensei_single_course_inside_before_lesson', get_the_ID() );

                ?>

                <section class="entry">

                    <?php
                    /**
                     * Display the lesson excerpt
                     */
                    the_excerpt();
                    ?>

                </section>

                <?php

                    /**
                     * The hook is inside the course lesson on the single course. It is just before the lesson closing markup.
                     * It fires for each lesson.
                     *
                     * @since 1.9.0
                     */
                    do_action( 'sensei_single_course_inside_after_lesson', get_the_ID() );

                ?>

            </article>

        <?php endwhile; // end course lessons loop  ?>

    <?php endif; ?>

    <?php

        /**
         * Actions just before the sensei single course lessons loop begins
         *
         * @hooked WooThemes_Sensei_Course::reset_single_course_query
         *
         * @since 1.9.0
         */
        do_action( 'sensei_single_course_lessons_after' );

    ?>

</section>

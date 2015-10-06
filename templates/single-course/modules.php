<?php
/**
 * List the Course Modules and Lesson in these modules
 *
 * Template is hooked into Single Course sensei_single_main_content. It will
 * only be shown if the course contains modules.
 *
 * All lessons shown here will not be included in the list of other lessons.
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.9.0
 */
?>

<?php

    /**
     * Hook runs inside single-course/course-modules.php
     *
     * It runs before the modules are shown. This hook fires on the single course page,but only if the course has modules.
     *
     * @since 1.8.0
     *
     * @hooked Sensei()->modules->course_modules_title - 20
     */
    do_action('sensei_single_course_modules_before');

?>

<?php if( sensei_have_modules() ): ?>

    <?php while ( sensei_have_modules() ): sensei_setup_module(); ?>
        <?php if( sensei_module_has_lessons() ): ?>

            <article class="module">
                <header>

                    <h2>

                        <a href="<?php sensei_the_module_permalink(); ?>" title="<?php sensei_the_module_title_attribute();?>">

                            <?php sensei_the_module_title(); ?>

                        </a>

                    </h2>

                </header>

                <section class="entry">

                    <section class="module-lessons">

                        <header>

                            <h3><?php _e('Lessons', 'woothemes-sensei') ?></h3>

                        </header>

                        <ul class="lessons-list" >

                            <?php while( sensei_module_has_lessons() ): the_post() ?>

                                <li class="' . $status . '">

                                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute() ?>" >

                                        <?php the_title(); ?>

                                    </a>

                                </li>

                            <?php endwhile; ?>

                        </ul>

                    </section><!-- .module-lessons -->

                </section>

            </article>

        <?php endif; //sensei_module_has_lessons  ?>

    <?php endwhile; // sensei_have_modules ?>

<?php endif; // sensei_have_modules ?>

<?php

/**
 * Hook runs inside single-course/course-modules.php
 *
 * It runs after the modules are shown. This hook fires on the single course page,but only if the course has modules.
 *
 * @since 1.8.0
 */
do_action('sensei_single_course_modules_after');

?>

<?php
return;
// Display each module
foreach ($modules as $module) {


    //
    // module progress
    //
    $module_progress = false;
    if (is_user_logged_in()) {
        global $current_user;
        wp_get_current_user();
        $module_progress = $this->get_user_module_progress($module->term_id, $course_id, $current_user->ID);
    }

    if ($module_progress && $module_progress > 0) {
        $status = __('Completed', 'woothemes-sensei');
        $class = 'completed';
        if ($module_progress < 100) {
            $status = __('In progress', 'woothemes-sensei');
            $class = 'in-progress';
        }
        echo '<p class="status module-status ' . esc_attr($class) . '">' . $status . '</p>';
    }

    //
    // //// module progress
    //

    //
    // module description
    //

    if ('' != $module->description) {
        echo '<p class="module-description">' . $module->description . '</p>';
    }

    //
    // module description
    //

    $lessons = $this->get_lessons( $course_id ,$module->term_id );

    if (count($lessons) > 0) {

        $lessons_list = '';
        foreach ($lessons as $lesson) {
            $status = '';
            $lesson_completed = WooThemes_Sensei_Utils::user_completed_lesson($lesson->ID, get_current_user_id() );
            $title = esc_attr(get_the_title(intval($lesson->ID)));

            if ($lesson_completed) {
                $status = 'completed';
            }

            $lessons_list .= '<li class="' . $status . '"><a href="' . esc_url(get_permalink(intval($lesson->ID))) . '" title="' . esc_attr(get_the_title(intval($lesson->ID))) . '">' . apply_filters('sensei_module_lesson_list_title', $title, $lesson->ID) . '</a></li>';

            // Build array of displayed lesson for exclusion later
            $displayed_lessons[] = $lesson->ID;
        }
        ?>


    <?php }//end count lessons

} // end each module


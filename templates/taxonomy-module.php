<?php
/**
 * The Template for displaying lessons in the module taxonomy
 *
 * Override this template by copying it to yourtheme/sensei/taxonomy-module.php
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
 * action before course archive loop
 *
 * @deprecated since 1.9.0 use sensei_loop_course_before instead
 * @hooked Sensei_Templates::deprecated_archive_hook 80
 */
do_action( 'sensei_archive_lesson_loop_before' );

?>

<?php  if ( have_posts() ) { ?>

    <section id="main-course" class="course-container">

     <section class="module-lessons">

        <?php //@todo create a template calling function as them devs should see the class file call ?>
        <?php Sensei_Templates::get_template( 'loop-lesson.php' ); ?>

     </section>

    </section>

<?php } else { ?>

    <p> <?php _e( 'No lessons found that match your selection.', 'woothemes-sensei' ); ?> </p>

<?php  } // End If Statement ?>

<?php get_sensei_footer(); ?>
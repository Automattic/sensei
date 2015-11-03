<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The Template for displaying the my course page data.
 *
 * Override this template by copying it to yourtheme/sensei/user/my-courses.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php
/**
 * Executes before the Sensei my courses markup begins. This hook
 * only fires whe a user is logged in. If you need to add
 * something here for when users are logged out use `sensei_login_form_before`.
 *
 * @since 1.9.0
 */
do_action( 'sensei_my_courses_before' );
?>

<section id="main-course" class="course-container">
    <?php

    do_action( 'sensei_frontend_messages' );

    do_action( 'sensei_before_user_course_content', get_current_user() );

    echo Sensei()->course->load_user_courses_content( get_current_user() , true );

    do_action( 'sensei_after_user_course_content', get_current_user()  );

    ?>

</section>

<?php
/**
 * Executes after the Sensei my courses template markup ends. This hook
 * only fires whe a user is logged in. If you need to add
 * something here for when users are logged out use `sensei_login_form_after`.
 *
 * @since 1.9.0
 */
do_action( 'sensei_my_courses_after' );
?>

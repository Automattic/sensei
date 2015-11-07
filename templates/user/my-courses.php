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
    /**
     * Executes inside just before the Sensei my courses content. This hook
     * only fires whe a user is logged in.
     *
     * @since 1.9.0
     */
    do_action( 'sensei_my_courses_content_inside_before' );
    ?>

    <?php sensei_the_my_courses_content(); ?>

    <?php
    /**
     * Executes inside just after the Sensei my courses content. This hook
     * only fires whe a user is logged in.
     *
     * @since 1.9.0
     */
    do_action( 'sensei_my_courses_content_inside_after' );
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

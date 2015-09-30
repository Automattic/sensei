<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author 		WooThemes
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // This template is only accessible via WordPress  ?>

<?php get_header(); ?>

<?php

    /**
     * sensei_before_main_content hook
     *
     * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
     */
    do_action( 'sensei_before_main_content' );

?>

<?php

    Sensei_Templates::get_part( 'content', 'single-course' );

?>

<?php

    /**
     * sensei_single_main_content hook
     *
     * @hooked Sensei->Modules->single_course_modules - 9
     * @hooked sensei_single_main_content - 10 (outputs main content)
     */
    //do_action( 'sensei_single_main_content' );
?>

<?php

    /**
     *
     * Add sensei pagination to the single course page
     *
     */
    do_action('sensei_pagination');

?>

<?php

    /**
     * sensei_after_main_content hook
     *
     * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
     */
    do_action( 'sensei_after_main_content' );

?>

<?php

    /**
     * sensei_sidebar hook
     *
     * @hooked sensei_get_sidebar - 10
     */
    do_action( 'sensei_sidebar' );

?>

<?php get_footer(); ?>
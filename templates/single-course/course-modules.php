<?php
/**
 * List the Course Modules and Lesson in these modules
 *
 * Template is hooked into Single Course sensei_single_main_content. It will
 * only be shown if the course contains modules.
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.8.0
 */

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

/**
 * @deprecated  since 1.8.0
 */
do_action('sensei_modules_page_before');


/**
 * Hook runs inside single-course/course-modules.php
 *
 * It runs in the middle of the page. This hook fires on the single course page,but only if the course has modules.
 *
 * @since 1.8.0
 *
 * @hooked  Sensei()->modules->course_module_content  - 20
 */
do_action('sensei_single_course_modules_content');

/**
 * Hook runs inside single-course/course-modules.php
 *
 * It runs after the modules are shown. This hook fires on the single course page,but only if the course has modules.
 *
 * @since 1.8.0
 */
do_action('sensei_single_course_modules_after');

/**
 * @deprecated  since 1.8.0
 */
do_action('sensei_modules_page_after');
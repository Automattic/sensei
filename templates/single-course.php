<?php
/**
 * The Template for displaying all single courses.
 *
 * Override this template by copying it to yourtheme/sensei/single-course.php
 *
 * @author      Automattic
 * @package     Sensei
 * @category    Templates
 * @version     3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_sensei_header();
?>

<?php

/**
 * Hook inside the single course post above the content
 *
 * @param integer $course_id
 *
 * @hooked Sensei()->frontend->sensei_course_start     -  10
 * @hooked Sensei_Course::the_title                    -  10
 * @hooked Sensei()->course->course_image              -  20
 * @hooked Sensei_Course::the_course_enrolment_actions -  30
 * @hooked Sensei()->message->send_message_link        -  35
 * @hooked Sensei_Course::the_course_video             -  40
 * @since  1.9.0
 */
do_action( 'sensei_single_course_content_inside_before', get_the_ID() );

?>

<?php
while ( have_posts() ) {
	the_post();
	the_content();
}
?>

<?php

/**
 * Hook inside the single course post above the content
 *
 * @param integer $course_id
 *
 * @since 1.9.0
 */
do_action( 'sensei_single_course_content_inside_after', get_the_ID() );

get_sensei_footer();

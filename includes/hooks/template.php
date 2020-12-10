<?php
/**
 * Sensei Template Hooks
 *
 * Action/filter hooks used for Sensei functionality hooked into Sensei Templates
 *
 * @author   Automattic
 * @package  Sensei
 * @category Hooks
 * @version  1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// Temporarily disable undefined variable checking.
// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

// Ensure that $this is a Sensei_Main instance.
if ( ! isset( $this ) || ! is_a( $this, 'Sensei_Main' ) ) {
	// FIXME: this whole thing should be a class injected with a Sensei instance
	throw new Exception( 'Template hooks cannot be included outside of a Sensei_Main instance' );
}

// Sensei_Main instance.
$sensei = $this;

// Re-enable undefined variable checking.
// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable

/***************************
 *
 *
 * TEMPLATE SYSTEM HOOKS
 */
// This hook allow us to change the template WordPress loads for a given page/post_type @since 1.9.0
add_filter( 'template_include', array( 'Sensei_Templates', 'template_loader' ), 10, 1 );

// Handle unsupported themes if needed.
add_action( 'template_redirect', array( 'Sensei_Unsupported_Themes', 'init' ) );

// This hook adds the sensei pagination to the pagination hook
add_action( 'sensei_pagination', array( 'Sensei_Frontend', 'load_content_pagination' ), 30 );


/***************************
 *
 *
 * COURSE ARCHIVE HOOKS
 */

// Course archive title hook @since 1.9.0
add_action( 'sensei_archive_before_course_loop', array( 'Sensei_Course', 'archive_header' ), 10, 0 );

// add the course image above the content
add_action( 'sensei_course_content_inside_before', array( $sensei->course, 'course_image' ), 30, 1 );

// add course content title to the courses on the archive page
add_action( 'sensei_course_content_inside_before', array( 'Sensei_Templates', 'the_title' ), 5, 1 );

/***************************
 *
 *
 * SINGLE COURSE HOOKS
 */

// @1.9.0
// hook the single course title on the single course page
add_action( 'sensei_single_course_content_inside_before', array( 'Sensei_Course', 'the_title' ), 10 );

// @1.9.0
// hook the single course title on the single course page
add_action( 'sensei_single_course_content_inside_before', array( $sensei->course, 'course_image' ), 20 );

// @1.9.0
// Filter the content and replace it with the excerpt if the user doesn't have full access
add_filter( 'the_content', array( 'Sensei_Course', 'single_course_content' ) );

// @since 1.9.0
// hooks in the course lessons query and remove it at the end
add_action( 'sensei_single_course_lessons_before', array( 'Sensei_Course', 'load_single_course_lessons_query' ) );
add_action( 'sensei_single_course_lessons_after', array( 'Sensei_Utils', 'restore_wp_query' ) );

// @since 1.9.0
// lesson meta information on the single course page
add_action( 'sensei_single_course_inside_before_lesson', array( 'Sensei_Lesson', 'the_lesson_meta' ), 5 );

// @since 1.9.0
// lesson image
add_action( 'sensei_single_course_inside_before_lesson', array( 'Sensei_Lesson', 'the_lesson_thumbnail' ), 8 );

// @since 1.9.0
// lesson custom excerpts
add_filter( 'get_the_excerpt', array( 'Sensei_Lesson', 'alter_the_lesson_excerpt' ) );

// @since 1.9.0
// hook in the module loop intialization functionality
add_action( 'sensei_single_course_modules_before', array( 'Sensei_Core_Modules', 'setup_single_course_module_loop' ) );

// @since 1.9.0
// hook in the module loop destructor functionality
add_action( 'sensei_single_course_modules_after', array( 'Sensei_Core_Modules', 'teardown_single_course_module_loop' ) );

// @since 1.9.0
// Course meta
add_action( 'sensei_single_course_content_inside_before', array( 'Sensei_Course', 'the_course_video' ), 40 );

/***************************
 *
 *
 * Single Quiz Hooks
 */

// @since 1.9.0
// Single quiz title
add_filter( 'the_title', array( 'Sensei_Quiz', 'single_quiz_title' ), 20, 2 ); // Append Quiz.
add_action( 'sensei_single_quiz_content_inside_before', array( 'Sensei_Quiz', 'the_title' ), 20 ); // Output single quiz.

// since 1.9.0
// Initialize the quiz questions loop.
add_action( 'sensei_single_quiz_content_inside_before', array( 'Sensei_Quiz', 'start_quiz_questions_loop' ) );

// since 1.9.0
// Hook in the quiz user message.
add_action( 'sensei_single_quiz_content_inside_before', array( 'Sensei_Quiz', 'the_user_status_message' ), 40 );

// @since 1.9.0
// hook in the question title, description and quesiton media
add_action( 'sensei_quiz_question_inside_before', array( 'Sensei_Question', 'the_question_title' ), 10 );
add_action( 'sensei_quiz_question_inside_before', array( 'Sensei_Question', 'the_question_description' ), 20 );
add_action( 'sensei_quiz_question_inside_before', array( 'Sensei_Question', 'the_question_media' ), 30 );
add_action( 'sensei_quiz_question_inside_before', array( 'Sensei_Question', 'the_question_hidden_fields' ), 40 );

// @since 1.9.0
// hook in incorrect / correct message below questions if the quiz has been graded
add_action( 'sensei_quiz_question_inside_after', array( 'Sensei_Question', 'the_answer_result_indication' ) );

// @since 1.9.0
// add answer grading feedback at the bottom of the question
add_action( 'sensei_quiz_question_inside_after', array( 'Sensei_Question', 'answer_feedback_notes' ) );

// @since 1.9.0
// add extra question data for different quesiton types when get_question_template_data_is_called.
add_filter( 'sensei_get_question_template_data', array( 'Sensei_Question', 'multiple_choice_load_question_data' ), 10, 3 );
add_filter( 'sensei_get_question_template_data', array( 'Sensei_Question', 'gap_fill_load_question_data' ), 10, 3 );
add_filter( 'sensei_get_question_template_data', array( 'Sensei_Question', 'file_upload_load_question_data' ), 10, 3 );

// @since 1.9.0
// deprecate the quiz button action
add_action( 'sensei_single_quiz_questions_after', array( 'Sensei_Quiz', 'action_buttons' ), 10, 0 );

/***************************
 *
 *
 * Single Lesson Hooks
 */

// @since 1.9.0
// hook in the lesson image on the single lesson
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'the_lesson_image' ), 17 );

// @since 1.9.0
// attach the lesson title
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'the_title' ), 15 );

// @since 1.9.0
// hook in the lesson image on the single lesson
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'user_lesson_quiz_status_message' ), 20 );

// @since 1.9.0
// add the single lesson meta
add_action( 'sensei_single_lesson_content_inside_after', 'sensei_the_single_lesson_meta', 10 );

// @since 1.9.0
// hook in the lesson prerequisite completion message
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'prerequisite_complete_message' ), 20 );

// @since 1.9.0
// hook the single lesson course_signup_link
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Lesson', 'course_signup_link' ), 30 );

// @since 1.9.0
// Add the quiz specific buttons and notices to the lesson
add_action( 'sensei_single_lesson_content_inside_after', array( 'Sensei_Lesson', 'footer_quiz_call_to_action' ) );

// @since 1.9.0
// hook in the comments on the single lessons page
add_action( 'sensei_pagination', array( 'Sensei_Lesson', 'output_comments' ), 90 );

/**********************
 *
 *
 * Single message hooks
 */

add_action( 'sensei_single_message_content_inside_before', array( 'Sensei_Messages', 'the_title' ), 20 );

add_action( 'sensei_single_message_content_inside_before', array( 'Sensei_Messages', 'the_message_sent_by_title' ), 40 );

/*************************
 *
 *
 * Lesson Archive Hooks
 */

// @1.9.0
// The archive title header on the lesson archive loop
add_action( 'sensei_loop_lesson_inside_before', array( $sensei->lesson, 'the_archive_header' ), 20 );

// @1.9.20
// The archive title header on the module taxonomy page.
add_action( 'sensei_taxonomy_module_content_inside_before', array( $sensei->lesson, 'the_archive_header' ), 20 );

// @since 1.9.0
// Output the lesson header on the content-lesson.php which runs inside the lessons loop
add_action( 'sensei_content_lesson_inside_before', array( 'Sensei_Lesson', 'the_lesson_meta' ), 20 );

// @since 1.9.3
// Output the lesson featured image
add_action( 'sensei_content_lesson_inside_before', array( 'Sensei_Lesson', 'the_lesson_thumbnail' ), 30 );

// @since 1.9.0
// output only part of the lesson on the archive
add_filter( 'the_content', array( 'Sensei_Lesson', 'limit_archive_content' ) );

/**************************
 *
 *
 * Learner Profile hooks
 */

// @since 1.9.0
// do the sensei complete course action on the learner profiles page.
add_action( 'sensei_learner_profile_content_before', array( 'Sensei_Templates', 'fire_sensei_complete_course_hook' ) );

// @since 1.9.0
// fire the frontend messages hook before the profile content
add_action( 'sensei_learner_profile_inside_content_before', array( 'Sensei_Templates', 'fire_frontend_messages_hook' ) );


/**********************************
 *
 *
 * Course Results template hooks
 */

// @since 1.9.0
// load the course information on the course results page
add_action( 'sensei_course_results_content_inside_before_lessons', array( $sensei->course_results, 'course_info' ) );

// @since 1.9.0
add_action( 'sensei_course_results_content_inside_before', array( $sensei->course, 'course_image' ) );

/**********************************
 *
 *
 * My Courses template hooks
 */
// @since 1.9.0
// fire the sensei complete course action on the my courses template
add_action( 'sensei_my_courses_before', array( 'Sensei_Templates', 'fire_sensei_complete_course_hook' ) );

// @since 1.9.0
// fire the sensei frontend messages hook before the my-courses content
add_action( 'sensei_my_courses_content_inside_before', array( 'Sensei_Templates', 'fire_frontend_messages_hook' ) );

/**********************************
 *
 *
 * Login form template hooks
 */

/**********************************
 *
 *
 * Archive Message Hooks
 */

// @since 1.9.0
// Deprecate the archive messages hooks no longer supported
add_action( 'sensei_archive_before_message_loop', array( 'Sensei_Messages', 'the_archive_header' ) );

// @since 1.9.0
// output the message title and the message sensei
add_action( 'sensei_content_message_before', array( 'Sensei_Messages', 'the_message_title' ), 10, 1 );
add_action( 'sensei_content_message_before', array( 'Sensei_Messages', 'the_message_sender' ), 20, 1 );

/**********************************
 *
 *
 *   Course Category Archive Hooks
 */
add_action( 'sensei_loop_course_before', array( 'Sensei_Course', 'course_category_title' ), 70, 1 );

/**********************************
 *
 *
 * Teacher Archive
 */
// @since 1.9.0
// add a title to the teacher archive page when view site-url/author/{teacher-username}
add_action( 'sensei_teacher_archive_course_loop_before', array( 'Sensei_Teacher', 'archive_title' ) );

// @since 1.9.0
// remove course meta from the teacher page until it can be refactored to allow only removing the
// teacher name and not all lessons
add_action( 'sensei_teacher_archive_course_loop_before', array( 'Sensei_Teacher', 'remove_course_meta_on_teacher_archive' ) );

/**********************************
 *
 * Frontend notices display
 */
add_action( 'sensei_course_results_content_inside_before', array( $sensei->notices, 'maybe_print_notices' ) );
add_action( 'sensei_single_course_content_inside_before', array( $sensei->notices, 'maybe_print_notices' ), 40 );
add_action( 'sensei_single_lesson_content_inside_before', array( $sensei->notices, 'maybe_print_notices' ), 40 );
add_action( 'sensei_taxonomy_module_content_inside_before', array( $sensei->notices, 'maybe_print_notices' ), 40 );

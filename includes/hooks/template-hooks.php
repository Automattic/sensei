<?php
/**
 * Sensei Template Hooks
 *
 * Action/filter hooks used for Sensei functionality hooked into Sensei Templates
 *
 * @author 		WooThemes
 * @package 	Sensei
 * @category 	Hooks
 * @version     1.9.0
 */

/***************************
 *
 *
 * TEMPLATE SYSTEM HOOKS
 *
 *
 ***************************/
//This hook allow us to change the template WordPress loads for a given page/post_type @since 1.9.0
add_filter( 'template_include', array ( 'Sensei_Templates', 'template_loader' ), 10, 1 );


/***************************
 *
 *
 * COURSE ARCHIVE HOOKS
 *
 *
 ***************************/
// deprecate the archive content hook @since 1.9.0
add_action( 'sensei_archive_before_course_loop', array ( 'Sensei_Templates', 'deprecated_archive_hook' ), 10, 1 );

// Course archive title hook @since 1.9.0
add_action('sensei_archive_before_course_loop', array( 'WooThemes_Sensei_Course', 'archive_header' ), 10, 0 );

// add the course image above the content
add_action('sensei_course_content_before', array( Sensei()->course, 'course_image' ) ,10, 1 );

// add course content title to the courses on the archive page
add_action('sensei_course_content_before', array( 'Sensei_Templates', 'the_title' ) ,11, 1 );

/***************************
 *
 *
 * SINGLE COURSE HOOKS
 *
 *
 ***************************/
// @1.9.0
// add deprecated action hooks for backwards compatibility sake
// hooks on single course page: sensei_course_image , sensei_course_single_title, sensei_course_single_meta
add_action('sensei_single_course_content_inside_before', array( 'Sensei_Templates', 'deprecated_single_course_inside_before_hooks' ), 80);

// @1.9.0
// hook the single course title on the single course page
add_action( 'sensei_single_course_content_inside_before','the_title', 10 );

// @1.9.0
//Add legacy hooks deprecated in 1.9.0
add_action( 'sensei_single_course_content_inside_before', array( 'Sensei_Templates','deprecate_course_single_meta_hooks'), 10 );

// @1.9.0
// Filter the content and replace it with the excerpt if the user doesn't have full access
add_filter( 'the_content', array('WooThemes_Sensei_Course', 'single_course_content' ) );

// @1.9.0
// Deprecate lessons specific single course hooks
add_action( 'sensei_single_course_content_inside_after', array( 'Sensei_Templates','deprecate_sensei_course_single_lessons_hook' ) );

// @1.9.0
// Deprecate single main content hooks
add_action( 'sensei_single_course_content_inside_after', array( 'Sensei_Templates', 'deprecated_single_main_content_hook') );
add_action( 'sensei_single_message_content_inside_after', array( 'Sensei_Templates', 'deprecated_single_main_content_hook') );

/**
 * Deprecate all the post type single titile hooks in favor of before content and after content hooks
 *
 * @deprecate 1.9.0
 * @1.9.0
 */
add_action('sensei_single_message_content_inside_before', array( 'Sensei_Templates', 'deprecate_all_post_type_single_title_hooks' ) );

/**
 * Deprecate hooks into the single course modules
 * @deprecated since 1.9.0
 */
add_action('sensei_single_course_modules_before', array('Sensei_Templates','deprecate_module_before_hook' ) );
add_action('sensei_single_course_modules_after', array('Sensei_Templates','deprecate_module_after_hook' ) );

// @since 1.9.0
// add the single course lessons title
add_action( 'sensei_single_course_content_inside_after' , array( 'WooThemes_Sensei_Course','the_course_lessons_title'), 9 );

// @since 1.9.0
// hooks in the course lessons query and remove it at the end
add_action( 'sensei_single_course_lessons_before', array('WooThemes_Sensei_Course','load_single_course_lessons_query' ) );
add_action( 'sensei_single_course_lessons_after', array( 'WooThemes_Sensei_Utils','restore_wp_query' ));

// @since 1.9.0
// add post classes to the lessons on the single course page
add_filter( 'post_class', array( 'WooThemes_Sensei_Lesson', 'single_course_lessons_classes' ) );

// @since 1.9.0
// lesson meta information on the single course page
add_action( 'sensei_single_course_inside_before_lesson', array('WooThemes_Sensei_Lesson','the_lesson_meta') , 5);

//@since 1.9.0
// lesson image
add_action( 'sensei_single_course_inside_before_lesson', array('WooThemes_Sensei_Lesson','the_lesson_thumbnail') , 8);

//@since 1.9.0
// lesson custom excerpts
add_action( 'get_the_excerpt', array( 'WooThemes_Sensei_Lesson', 'alter_the_lesson_excerpt' ) );

// @since 1.9.0
// run a deprecated hook for backwards compatibility sake
add_action( 'sensei_single_course_modules_before', array( 'Sensei_Core_Modules', 'deprecate_sensei_single_course_modules_content'  ) );

// @since 1.9.0
// hook in the module loop intialization functionality
add_action( 'sensei_single_course_modules_before', array( 'Sensei_Core_Modules', 'setup_single_course_module_loop' ) );

// @since 1.9.0
// hook in the module loop destructor functionality
add_action( 'sensei_single_course_modules_after', array( 'Sensei_Core_Modules', 'teardown_single_course_module_loop' ) );

// @since 1.9.0
// hook in the possible full content override to show instead of excerpt
add_filter('get_the_excerpt', array( 'WooThemes_Sensei_Course', 'full_content_excerpt_override' ) );

/***************************
 *
 *
 * Single Quiz Hooks
 *
 *
 ***************************/
//@since 1.9.0
// deprecate hooks no longer needed
add_action( 'sensei_single_quiz_content_inside_before', array('WooThemes_Sensei_Quiz', 'deprecate_quiz_sensei_single_main_content_hook' ) );
add_action( 'sensei_single_quiz_content_inside_before', array('WooThemes_Sensei_Quiz', 'deprecate_quiz_sensei_quiz_single_title_hook' ) );

//@since 1.9.0
// Single qui title
add_filter( 'the_title', array( 'WooThemes_Sensei_Quiz' , 'single_quiz_title' ) );

// since 1.9.0
// initialize the quiz questions loop
add_action( 'sensei_single_quiz_content_inside_before', array( 'WooThemes_Sensei_Quiz', 'start_quiz_questions_loop') );

// since 1.9.0
// hook in the quiz user message
add_action( 'sensei_single_quiz_content_inside_before', array( 'WooThemes_Sensei_Quiz', 'the_user_status_message' ), 10 );

//@since 1.9.0
// hook in the question title, description and quesiton media
add_action( 'sensei_quiz_question_inside_before', array( 'WooThemes_Sensei_Question','the_question_title' ), 10 );
add_action( 'sensei_quiz_question_inside_before', array( 'WooThemes_Sensei_Question','the_question_description' ), 20 );
add_action( 'sensei_quiz_question_inside_before', array( 'WooThemes_Sensei_Question','the_question_media' ), 30 );
add_action( 'sensei_quiz_question_inside_before', array( 'WooThemes_Sensei_Question','the_question_hidden_fields' ), 40 );

//@since 1.9.0
// hook in incorrect / correct message above questions if the quiz has been graded
add_action( 'sensei_quiz_question_inside_before', array( 'WooThemes_Sensei_Question', 'the_answer_result_indication' ), 50 );

//@since 1.9.0
// add answer grading feedback at the bottom of the question
add_action( 'sensei_quiz_question_inside_after', array( 'WooThemes_Sensei_Question', 'answer_feedback_notes' ) );

//@since 1.9.0
// add extra question data for different quesiton types when get_question_template_data_is_called.
add_filter( 'sensei_get_question_template_data', array( 'WooThemes_Sensei_Question','multiple_choice_load_question_data'), 10, 3);
add_filter( 'sensei_get_question_template_data', array( 'WooThemes_Sensei_Question','gap_fill_load_question_data'), 10, 3);
add_filter( 'sensei_get_question_template_data', array( 'WooThemes_Sensei_Question','file_upload_load_question_data'), 10, 3);

//@since 1.9.0
// deprecate the quiz button action
add_action( 'sensei_single_quiz_questions_after', array( 'WooThemes_Sensei_Quiz', 'action_buttons' ), 10, 0 );

/***************************
 *
 *
 * Single Lesson Hooks
 *
 *
 ***************************/
//@since 1.9.0
// deprecate the main content hook on the single lesson page
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Templates', 'deprecate_lesson_single_main_content_hook' ), 20);

//@since 1.9.0
// hook in the lesson image on the single lesson
add_action( 'sensei_single_lesson_content_inside_before', array( Sensei()->lesson, 'lesson_image' ), 10 );

//@since 1.9.0
// hook in the lesson image on the single lesson deprecated hook function
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Templates','deprecate_lesson_image_hook' ), 10 );

//@since 1.9.0
// hook in the lesson single title deprecated function
add_action( 'sensei_single_lesson_content_inside_before', array( 'Sensei_Templates', 'deprecate_sensei_lesson_single_title' ), 15 );

// @since 1.9.0
// hook in the sensei lesson user notices
add_action( 'sensei_single_lesson_content_inside_before', array( 'WooThemes_Sensei_Lesson', 'user_not_taking_course_message' ), 15 );

// @since 1.9.0
// attach the lesson title
add_action( 'sensei_single_lesson_content_inside_before', array( 'WooThemes_Sensei_Lesson', 'the_title' ), 15 );

// @since 1.9.0
// add the single lesson meta
add_action( 'sensei_single_lesson_content_inside_after', 'sensei_the_single_lesson_meta', 10 );

// @since 1.9.0
// deprecate the sensei_lesson_single_meta hook
add_action( 'sensei_single_lesson_content_inside_after', array( 'Sensei_Templates', 'deprecate_sensei_lesson_single_meta_hook' ), 15 );

// @since 1.9.0
// deprecate the sensei_lesson_course_signup hook
add_action( 'sensei_single_lesson_content_inside_after', array( 'Sensei_Templates','deprecate_sensei_lesson_course_signup_hook' ), 20 );

// @since 1.9.0
// hook in the lesson prerequisite completion message
add_action( 'sensei_single_lesson_content_inside_after', array( 'WooThemes_Sensei_Lesson', 'prerequisite_complete_message' ), 20 );

// @since 1.9.0
// hook the single lesson course_signup_link
add_action( 'sensei_single_lesson_content_inside_after', array( 'WooThemes_Sensei_Lesson', 'course_signup_link' ), 30 );

// @since 1.9.0
// hook the deprecate breadcrumbs and comments hooks
add_action( 'sensei_after_main_content', array( 'Sensei_Templates', 'deprecate_single_lesson_breadcrumbs_and_comments_hooks'), 5 );

/**********************
 *
 *
 * Single message hooks
 *
 *
 ************************/

add_action( 'sensei_single_message_content_inside_before', array( 'WooThemes_Sensei_Messages', 'the_title' ), 20 );

add_action( 'sensei_single_message_content_inside_before', array( 'WooThemes_Sensei_Messages', 'the_message_sent_by_title' ), 40 );

/*************************
 *
 *
 * Lesson Archive Hooks
 *
 *
 *************************/

// deprecate the sensei_lesson_archive_header hook
// @deprecated since 1.9.0
add_action( 'sensei_loop_lesson_inside_before', array( 'WooThemes_Sensei_Lesson', 'deprecate_sensei_lesson_archive_header_hook' ), 20 );

// @1.9.0
//The archive title header on the lesson archive loop
add_action( 'sensei_loop_lesson_inside_before', array( Sensei()->lesson, 'the_archive_header' ), 20 );

// @since 1.9.0
//Outp the lesson header on the content-lesson.php which runs inside the lessons loop
add_action( 'sensei_content_lesson_inside_before', array( 'WooThemes_Sensei_Lesson', 'the_lesson_meta' ), 20 );

/**************************
 *
 *
 * Learner Profile hooks
 *
 *
 **************************/
// @since 1.9.0
// deprecate the learner profile content hook as the markup code is added in the template directly.
add_action('sensei_learner_profile_content_before', array( 'WooThemes_Sensei_Learner_Profiles', 'deprecate_sensei_learner_profile_content_hook' ) );

// @since 1.9.0
// do the sensei complete course action on the learner profiles page.
add_action('sensei_learner_profile_content_before', array( 'WooThemes_Sensei_Learner_Profiles', 'complete_course_action_hook' ) );

// @since 1.9.0
// fire the frontend messages hook before the profile content
add_action('sensei_learner_profile_inside_content_before', array( 'WooThemes_Sensei_Learner_Profiles', 'frontend_messages_hook' ) );


/**********************************
 *
 *
 * Course Results template hooks
 *
 *
 ********************************/

// @since 1.9.0
// fire the deprecated hook function within the course-result.php file
add_action( 'sensei_course_results_content_before', array('WooThemes_Sensei_Course_Results','deprecate_sensei_course_results_content_hook') );

// @since 1.9.0
// fire the sensei message hooke inside the course-result.php file
add_action( 'sensei_course_results_content_inside_before', array('WooThemes_Sensei_Course_Results','fire_sensei_message_hook') );

// @since 1.9.0
// load the course information on the course results page
add_action( 'sensei_course_results_content_inside_after', array( Sensei()->course_results,'course_info') );

// @since 1.9.0
// deprecate the course results top hook in favour of a new hook
add_action( 'sensei_course_results_content_inside_before', array( 'WooThemes_Sensei_Course_Results', 'deprecate_course_results_top_hook') );

// @since 1.9.0
// Fire the course image hook within the course results page
add_action( 'sensei_course_results_content_inside_before', array( 'WooThemes_Sensei_Course_Results', 'fire_course_image_hook') );

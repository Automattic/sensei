<?php
/**
 * Default pattern.
 *
 * @package sensei-lms
 */

ob_start();


require Sensei()->feature_flags->is_enabled( 'course_outline_ai' ) ?
	__DIR__ . '/templates/v2/course-default.php' :
	__DIR__ . '/templates/course-default.php';

return [
	'title'      => __( 'Course Default', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => ob_get_clean(),
];

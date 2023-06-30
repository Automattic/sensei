<?php
/**
 * Video Hero pattern.
 *
 * @package sensei-lms
 */

ob_start();
require Sensei()->feature_flags->is_enabled( 'course_outline_ai' ) ?
	__DIR__ . '/templates/v2/video-hero.php' :
	__DIR__ . '/templates/video-hero.php';

return [
	'title'      => __( 'Video Hero', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => ob_get_clean(),
	'template'   => 'wide-no-featured-no-title',
];

<?php
/**
 * Long Sales Page pattern.
 *
 * @package sensei-lms
 */

ob_start();
require Sensei()->feature_flags->is_enabled( 'course_outline_ai' ) ?
	__DIR__ . '/templates/v2/long-sales.php' :
	__DIR__ . '/templates/long-sales-page.php';

return [
	'title'      => __( 'Long Sales Page', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => ob_get_clean(),
	'template'   => 'wide-no-featured-no-title',
];

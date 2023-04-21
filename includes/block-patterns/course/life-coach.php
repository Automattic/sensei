<?php
/**
 * Long Sales Page pattern.
 *
 * @package sensei-lms
 */

ob_start();
require __DIR__ . '/templates/life-coach.php';

return [
	'title'      => __( 'Life Coach', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => ob_get_clean(),
	'template'   => 'wide-no-featured-no-title',
];

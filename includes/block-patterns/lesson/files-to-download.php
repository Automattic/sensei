<?php
/**
 * Files to Download pattern.
 *
 * @package sensei-lms
 */

ob_start();
require __DIR__ . '/templates/files-to-download.php';

return [
	'title'      => __( 'Files to Download', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => ob_get_clean(),
];

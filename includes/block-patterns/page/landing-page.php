<?php
/**
 * Landing page with Course List as Grid.
 *
 * @package sensei-lms
 */

ob_start();
require __DIR__ . '/templates/v2/landing-page.php';

return [
	'title'      => __( 'Landing Page', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'content'    => ob_get_clean(),
];

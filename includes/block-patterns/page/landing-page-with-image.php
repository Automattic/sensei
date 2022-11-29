<?php
/*
 * Landing Page with Image.
 *
 * @package sensei-lms
 */

ob_start();
require __DIR__ . '/templates/landing-page-with-image.php';

return [
	'title'      => __( 'Landing Page with Image', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'content'    => ob_get_clean(),
];

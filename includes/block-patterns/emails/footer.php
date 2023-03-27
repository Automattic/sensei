<?php
/**
 * Email footer.
 *
 * @package sensei-lms
 */

ob_start();
require __DIR__ . '/templates/footer.php';

return [
	'title'      => __( 'Email Footer', 'sensei-lms' ),
	'categories' => [ 'sensei-emails' ],
	'content'    => ob_get_clean(),
	'inserter'   => false,
];

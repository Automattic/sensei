<?php
/**
 * Admin View: Page - Extensions
 *
 * @package Sensei\Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap sensei sensei_extensions_wrap">
	<h1><?php esc_html_e( 'Sensei LMS Extensions', 'sensei-lms' ); ?></h1>
	<?php
	require_once __DIR__ . '/html-admin-page-extensions-messages.php';
	require_once __DIR__ . '/html-admin-page-extensions-categories.php';
	require_once __DIR__ . '/html-admin-page-extensions-results.php';
	?>
</div>

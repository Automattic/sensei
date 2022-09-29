<?php
/**
 * Admin View: Page - Home
 *
 * @package Sensei\Home
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="sensei-home-page" class="sensei-home-page">
	<h1><?php esc_html_e( 'Sensei Home', 'sensei-lms' ); ?></h1>
	<?php
	require_once __DIR__ . '/html-admin-page-home-messages.php';
	?>
</div>

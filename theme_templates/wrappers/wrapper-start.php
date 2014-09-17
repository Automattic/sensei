<?php
/**
 * Content wrappers
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

// Customised from Sensei default template
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$class = '';
if ( !is_active_sidebar( 'sidebar-1' ) && !is_active_sidebar( 'sidebar-2' ) ) {
	$class = ' full-width';
}
?>
	<div id="primary" class="content-area<?php echo $class; ?>">
		<div id="content" class="site-content" role="main">
		<?php do_action( 'content_top' ); ?>

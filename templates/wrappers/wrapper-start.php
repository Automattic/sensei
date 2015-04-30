<?php
/**
 * Content wrappers
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$template = get_option('template');

switch( $template ) {

	// IF Twenty Eleven
	case 'twentyeleven' :
		echo '<div id="primary"><div id="content" role="main">';
		break;

	// IF Twenty Twelve
	case 'twentytwelve' :
		echo '<div id="primary" class="site-content"><div id="content" role="main" class="entry-content">';
		break;

	// IF Twenty Fourteen
	case 'twentyfourteen' :
		echo '<div id="main-content" class="main-content"><div id="primary" class="content-area"><div id="content" class="site-content" role="main"><div class="entry-content">';
		break;

	// Default
	default :
		echo '<div id="content" class="page col-full"><div id="main" class="col-left">';
		break;
}

?>
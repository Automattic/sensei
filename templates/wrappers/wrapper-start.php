<?php
/**
 * Content wrappers
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

$template = get_option('template');

switch( $template ) {
	
	// IF Twenty Eleven
	case 'twentyeleven' :
		echo '<div id="primary"><div id="content" role="main">';
		break;
		
	// IF Twenty Twelve
	case 'twentytwelve' :
		echo '<div id="primary" class="site-content"><div id="content" role="main">';
		break;
		
	// Default
	default :
		echo '<div id="content" class="page col-full"><div id="main" class="col-left">';
		break;
}

?>
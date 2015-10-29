<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Content wrappers
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */

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

    // IF Twenty Fifteen
    case 'twentyfifteen':
        echo '<div id="primary" class="content-area">'
            .'<main id="main" class="site-main" role="main">';
            ?>
            <style>
                @media screen and (min-width: 59.6875em){
                    article.post {
                        padding-top: 8.3333% !important;
                        margin: 0 8.3333% !important;
                        box-shadow: 0 0 1px rgba(0, 0, 0, 0.15) !important;
                        background-color: #fff !important;
                        padding: 1em 2em 2em !important;
                    }
                }
            </style>
            <?php
        break;

    // IF Twenty Sixteen
    case 'twentysixteen' :
        echo '<div id="primary" class="content-area">'
	        .'<main id="main" class="site-main" role="main">';
        break;

    // IF Storefront
    case 'storefront' :
        echo '<div id="primary" class="content-area">'
            .'<main id="main" class="site-main" role="main">';
        break;

	// Default
	default :
		echo '<div id="content" class="page col-full"><div id="main" class="col-left">';
		break;
}
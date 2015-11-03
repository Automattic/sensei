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
                    #main article.lesson,
                    #main article.course,
                    #main #post-entries,
                    .sensei-breadcrumb {
                        padding-top: 8.3333%;
                        margin: 0 8.3333%;
                        box-shadow: 0 0 1px rgba(0, 0, 0, 0.15);
                        background-color: #fff;
                        padding: 1em 2em 2em;
                    }

                    #main .course-lessons .lesson {
                        margin: 0;
                    }

                    #main #post-entries {
                        padding: 1em 2em;
                        overflow: hidden;
                    }

                    #main article.lesson ol {
                        list-style-position: inside;
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

    // IF Divi
    case 'divi' :
        echo '<div id="main-content">'
            .'<div class="container">'
            .'<div id="content-area" class="clearfix">'
            .'<div id="left-area">';
        break;

    // IF Enfold
    case 'enfold' :
        echo '<div class="container_wrap container_wrap_first main_color sidebar_right">'
            .'<div class="container">'
            .'<main class="template-page content  av-content-small alpha units" role="main" itemprop="mainContentOfPage">';
        break;

	// Default
	default :
		echo '<div id="content" class="page col-full"><div id="main" class="col-left">';
		break;
}
<?php
/**
 * The Template for outputting Lists of any Sensei content type.
 *
 * This template expects the global wp_query to setup and ready for the loop
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.9.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// exit the template early if there are no posts to load
if( ! have_posts() ){
    return;
}
?>

<section class="course-container" >

<?php

while ( have_posts() ) { the_post();

    include('shortcode-content-course-template.php');

}
?>

</section>

<?php
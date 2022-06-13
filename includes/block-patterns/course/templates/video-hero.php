<?php
/**
 * Video Hero pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"120px","bottom":"120px","right":"90px","left":"90px"}},"color":{"text":"#ffffff","background":"#000000"}},"className":"sensei-pattern-group"} -->
<div class="wp-block-group sensei-pattern-group alignfull has-text-color has-background" style="background-color:#000000;color:#ffffff;padding-top:120px;padding-right:90px;padding-bottom:120px;padding-left:90px"><!-- wp:group {"align":"full","layout":{"inherit":false}} -->
<div class="wp-block-group alignfull"><!-- wp:heading {"level":1,"align":"wide","style":{"typography":{"fontSize":"clamp(3rem, 6vw, 4.5rem)","lineHeight":"1.15"}},"className":"sensei-content-title"} -->
<h1 class="alignwide sensei-content-title" style="font-size:clamp(3rem, 6vw, 4.5rem);line-height:1.15"><?php esc_html_e( 'Welcome to the Film Direction Course', 'sensei-lms' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:spacer {"height":32} -->
<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:video {"align":"wide"} -->
<figure class="wp-block-video alignwide"><video controls src="<?php echo esc_url( Sensei()->assets->get_image( 'patterns-video.mp4' ) ); ?>"></video></figure>
<!-- /wp:video -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:paragraph {"className":"sensei-content-author"} -->
<p class="sensei-content-author"><strong>Doug Stilton</strong></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"className":"sensei-content-description"} -->
<p class="sensei-content-description"><?php esc_html_e( 'Start learning about Film Direction with Doug, a senior VP at Films. You will learn all the secrets and how to prepare your project even before touching the camera.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:sensei-lms/button-take-course {"align":"right"} -->
<div class="wp-block-sensei-lms-button-take-course has-text-align-right is-style-default wp-block-sensei-button wp-block-button"><button class="wp-block-button__link"><?php esc_html_e( 'Take Course', 'sensei-lms' ); ?></button></div>
<!-- /wp:sensei-lms/button-take-course --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}}},"className":"sensei-pattern-group","layout":{"inherit":false}} -->
<div class="wp-block-group alignfull sensei-pattern-group" style="padding-top:6rem;padding-bottom:6rem"><!-- wp:group {"align":"wide"} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","style":{"typography":{"lineHeight":1.5}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size" style="line-height:1.5"><?php esc_html_e( "Get to know Doug's network of professionals by taking the Course today!", 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":16} -->
<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"300","lineHeight":1.5}},"fontSize":"x-large"} -->
<p class="has-text-align-center has-x-large-font-size" style="font-weight:300;line-height:1.5"><a href="#">Jesús Rodriguez</a>, <a href="#">Emery Driscoll</a>, <a href="#">Megan Perry</a>, <a href="#">Rowan Price</a>, <a href="#">Angelo Tso</a>, <a href="#">Edward Stilton</a>, <a href="#">Amy Jensen</a>, <a href="#">Boston Bell</a>, <a href="#">Shay Ford</a>, <a href="#">Lee Cunningham</a>, <a href="#">Evelynn Ray</a>, <a href="#">Landen Reese</a>, <a href="#">Ewan Hart</a>, <a href="#">Jenna Chan</a>, <a href="#">Phoenix Murray</a>, <a href="#">Mel Saunders</a>, <a href="#">Aldo Davidson</a>, <a href="#">Zain Hall</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":16} -->
<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:sensei-lms/button-take-course {"align":"center","backgroundColor":"foreground","textColor":"background"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-center"><button class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background"><?php esc_html_e( 'Take Course', 'sensei-lms' ); ?></button></div>
<!-- /wp:sensei-lms/button-take-course --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"color":{"background":"#f8f4e4"},"spacing":{"padding":{"top":"20px","left":"90px","right":"90px","bottom":"20px"}}}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#f8f4e4;padding-top:20px;padding-right:90px;padding-bottom:20px;padding-left:90px"><!-- wp:spacer {"height":24} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":6,"style":{"color":{"text":"#000000"}}} -->
<h6 class="has-text-color" id="ecosystem" style="color:#000000"><?php esc_html_e( 'INTRODUCTION', 'sensei-lms' ); ?></h6>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.1","fontSize":"5vw"},"color":{"text":"#000000"}}} -->
<p class="has-text-color" style="color:#000000;font-size:5vw;line-height:1.1"><strong><?php esc_html_e( 'Film Direction', 'sensei-lms' ); ?></strong></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":5} -->
<div style="height:5px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.38%"} -->
<div class="wp-block-column" style="flex-basis:33.38%"><!-- wp:paragraph {"style":{"color":{"text":"#000000"}},"fontSize":"extra-small"} -->
<p class="has-text-color has-extra-small-font-size" style="color:#000000"><?php echo wp_kses_post( __( "A <strong>film director</strong> controls a film's artistic and dramatic aspects and visualizes the screenplay (or script) while guiding the film crew and actors in the fulfillment of that vision. The director has a key role in choosing the cast members, production design, and all the creative aspects of filmmaking.", 'sensei-lms' ) ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33%"} -->
<div class="wp-block-column" style="flex-basis:33%"><!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( Sensei()->assets->get_image( 'patterns-video-hero-01.jpg' ) ); ?>" alt="The sun setting through a dense forest."/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.62%"} -->
<div class="wp-block-column" style="flex-basis:33.62%"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( Sensei()->assets->get_image( 'patterns-video-hero-02.jpg' ) ); ?>" alt="Wind turbines standing on a grassy plain, against a blue sky."/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"67%"} -->
<div class="wp-block-column" style="flex-basis:67%"><!-- wp:image {"align":"right","sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image alignright size-large"><img src="<?php echo esc_url( Sensei()->assets->get_image( 'patterns-video-hero-03.jpg' ) ); ?>" alt="The sun shining over a ridge leading down into the shore. In the distance, a car drives down a road."/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"33%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33%"><!-- wp:paragraph {"style":{"color":{"text":"#000000"}},"fontSize":"extra-small"} -->
<p class="has-text-color has-extra-small-font-size" style="color:#000000"><?php esc_html_e( 'There are many pathways to becoming a film director. Some film directors started as screenwriters, cinematographers, producers, film editors, or actors. Directors use different approaches. In this course you will also learn about each of these points and figure out which one is for you.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":8} -->
<div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:sensei-lms/button-take-course {"align":"left","backgroundColor":"foreground","textColor":"background"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background"><?php esc_html_e( 'Take Course', 'sensei-lms' ); ?></button></div>
<!-- /wp:sensei-lms/button-take-course --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","right":"90px","bottom":"20px","left":"90px"}},"color":{"text":"#ffffff","background":"#000000"}},"className":"sensei-pattern-group"} -->
<div class="wp-block-group sensei-pattern-group has-text-color has-background" style="background-color:#000000;color:#ffffff;padding-top:20px;padding-right:90px;padding-bottom:20px;padding-left:90px"><!-- wp:spacer {"height":24} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading -->
<h2><?php esc_html_e( "Let's get started", 'sensei-lms' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":24} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:sensei-lms/course-progress /-->

<!-- wp:sensei-lms/course-outline -->
<!-- wp:sensei-lms/course-outline-lesson {"title":"<?php esc_html_e( 'Lesson 1', 'sensei-lms' ); ?>"} /-->

<!-- wp:sensei-lms/course-outline-lesson {"title":"<?php esc_html_e( 'Lesson 2', 'sensei-lms' ); ?>"} /-->

<!-- wp:sensei-lms/course-outline-lesson {"title":"<?php esc_html_e( 'Lesson 3', 'sensei-lms' ); ?>"} /-->
<!-- /wp:sensei-lms/course-outline -->

<!-- wp:spacer {"height":8} -->
<div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:sensei-lms/button-take-course -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link"><?php esc_html_e( 'Take Course', 'sensei-lms' ); ?></button></div>
<!-- /wp:sensei-lms/button-take-course -->

<!-- wp:sensei-lms/button-contact-teacher {"style":{"color":{"text":"#ffffff"}}} -->
<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link has-text-color" style="color:#ffffff"><?php esc_html_e( 'Contact Teacher', 'sensei-lms' ); ?></a></div>
<!-- /wp:sensei-lms/button-contact-teacher --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":24} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

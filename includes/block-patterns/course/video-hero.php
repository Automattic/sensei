<?php
/**
 * Video Hero pattern.
 *
 * @package sensei-lms
 */

return [
	'title'      => __( 'Video Hero', 'sensei-lms' ),
	'categories' => [ \Sensei_Editor_Wizard::PATTERNS_CATEGORY ],
	'blockTypes' => [ \Sensei_Editor_Wizard::POST_CONTENT_BLOCK_TYPE ],
	'content'    => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)","bottom":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)"}},"elements":{"link":{"color":{"text":"var:preset|color|secondary"}}}},"backgroundColor":"foreground","textColor":"secondary"} -->
					<div class="wp-block-group alignfull has-secondary-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:var(--wp--custom--spacing--large, 8rem);padding-bottom:var(--wp--custom--spacing--large, 8rem)"><!-- wp:group {"align":"full","layout":{"inherit":false}} -->
					<div class="wp-block-group alignfull"><!-- wp:heading {"level":1,"align":"wide","style":{"typography":{"fontSize":"clamp(3rem, 6vw, 4.5rem)"}},"textColor":"tertiary"} -->
					<h1 class="alignwide has-tertiary-color has-text-color" id="warble-a-film-about-hobbyist-bird-watchers-1" style="font-size:clamp(3rem, 6vw, 4.5rem)">' . esc_html__( 'Welcome to the Film Direction Course', 'sensei-lms' ) . '</h1>
					<!-- /wp:heading -->

					<!-- wp:spacer {"height":"32px"} -->
					<div style="height:32px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:video {"align":"wide"} -->
					<figure class="wp-block-video alignwide"><video controls src="https://sensei-demo.mystagingwebsite.com/wp-content/themes/twentytwentytwo/assets/videos/birds.mp4"></video></figure>
					<!-- /wp:video -->

					<!-- wp:columns {"align":"wide","textColor":"tertiary"} -->
					<div class="wp-block-columns alignwide has-tertiary-color has-text-color"><!-- wp:column {"width":"50%"} -->
					<div class="wp-block-column" style="flex-basis:50%"><!-- wp:paragraph -->
					<p><strong>Doug Stilton</strong></p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column"><!-- wp:paragraph {"className":"sensei-pattern-description"} -->
					<p class="sensei-pattern-description">' . esc_html__( 'Start learning about Film Direction with Doug, a senior VP at Films. You will learn all the secrets and how to prepare your project even before touching the camera.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column"><!-- wp:sensei-lms/button-take-course {"align":"right","backgroundColor":"tertiary","textColor":"foreground"} -->
					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><button class="wp-block-button__link has-foreground-color has-tertiary-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
					<!-- /wp:sensei-lms/button-take-course --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns --></div>
					<!-- /wp:group --></div>
					<!-- /wp:group -->

					<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}},"elements":{"link":{"color":{"text":"var:preset|color|primary"}}},"color":{}},"textColor":"primary","layout":{"inherit":false}} -->
					<div class="wp-block-group alignfull has-primary-color has-text-color has-link-color" style="padding-top:6rem;padding-bottom:6rem"><!-- wp:group {"align":"wide"} -->
					<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
					<p class="has-text-align-center has-large-font-size">' . esc_html__( "Get to know Doug's network of professionals by taking the Course today!", 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:spacer {"height":"16px"} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"300"}},"fontSize":"x-large"} -->
					<p class="has-text-align-center has-x-large-font-size" style="font-weight:300"><a href="#">Jesús Rodriguez</a>, <a href="#">Emery Driscoll</a>, <a href="#">Megan Perry</a>, <a href="#">Rowan Price</a>, <a href="#">Angelo Tso</a>, <a href="#">Edward Stilton</a>, <a href="#">Amy Jensen</a>, <a href="#">Boston Bell</a>, <a href="#">Shay Ford</a>, <a href="#">Lee Cunningham</a>, <a href="#">Evelynn Ray</a>, <a href="#">Landen Reese</a>, <a href="#">Ewan Hart</a>, <a href="#">Jenna Chan</a>, <a href="#">Phoenix Murray</a>, <a href="#">Mel Saunders</a>, <a href="#">Aldo Davidson</a>, <a href="#">Zain Hall</a>.</p>
					<!-- /wp:paragraph -->

					<!-- wp:spacer {"height":"16px"} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:sensei-lms/button-take-course {"align":"center","backgroundColor":"foreground","textColor":"background"} -->
					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-center"><button class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
					<!-- /wp:sensei-lms/button-take-course --></div>
					<!-- /wp:group --></div>
					<!-- /wp:group -->

					<!-- wp:group {"align":"full","style":{"color":{"background":"#f8f4e4"}}} -->
					<div class="wp-block-group alignfull has-background" style="background-color:#f8f4e4"><!-- wp:spacer {"height":"24px"} -->
					<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:columns {"align":"wide"} -->
					<div class="wp-block-columns alignwide"><!-- wp:column -->
					<div class="wp-block-column"><!-- wp:spacer -->
					<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:heading {"level":6,"style":{"color":{"text":"#000000"}}} -->
					<h6 class="has-text-color" id="ecosystem" style="color:#000000">' . esc_html__( 'INTRODUCTION', 'sensei-lms' ) . '</h6>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.1","fontSize":"5vw"},"color":{"text":"#000000"}}} -->
					<p class="has-text-color" style="color:#000000;font-size:5vw;line-height:1.1"><strong>' . esc_html__( 'Film Direction', 'sensei-lms' ) . '</strong></p>
					<!-- /wp:paragraph -->

					<!-- wp:spacer {"height":"5px"} -->
					<div style="height:5px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:columns {"align":"wide"} -->
					<div class="wp-block-columns alignwide"><!-- wp:column {"width":"33.38%"} -->
					<div class="wp-block-column" style="flex-basis:33.38%"><!-- wp:paragraph {"style":{"color":{"text":"#000000"}},"fontSize":"extra-small"} -->
					<p class="has-text-color has-extra-small-font-size" style="color:#000000">' . wp_kses_post( __( "A <strong>film director</strong> controls a film's artistic and dramatic aspects and visualizes the screenplay (or script) while guiding the film crew and actors in the fulfillment of that vision. The director has a key role in choosing the cast members, production design, and all the creative aspects of filmmaking.", 'sensei-lms' ) ) . '</p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column -->

					<!-- wp:column {"width":"33%"} -->
					<div class="wp-block-column" style="flex-basis:33%"><!-- wp:spacer -->
					<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
					<figure class="wp-block-image size-large"><img src="https://s.w.org/images/core/5.8/outside-01.jpg" alt="The sun setting through a dense forest."/></figure>
					<!-- /wp:image --></div>
					<!-- /wp:column -->

					<!-- wp:column {"width":"33.62%"} -->
					<div class="wp-block-column" style="flex-basis:33.62%"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
					<figure class="wp-block-image size-large"><img src="https://s.w.org/images/core/5.8/outside-02.jpg" alt="Wind turbines standing on a grassy plain, against a blue sky."/></figure>
					<!-- /wp:image --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:columns {"align":"wide"} -->
					<div class="wp-block-columns alignwide"><!-- wp:column {"width":"67%"} -->
					<div class="wp-block-column" style="flex-basis:67%"><!-- wp:image {"align":"right","sizeSlug":"large","linkDestination":"none"} -->
					<figure class="wp-block-image alignright size-large"><img src="https://s.w.org/images/core/5.8/outside-03.jpg" alt="The sun shining over a ridge leading down into the shore. In the distance, a car drives down a road."/></figure>
					<!-- /wp:image --></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center","width":"33%"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33%"><!-- wp:paragraph {"style":{"color":{"text":"#000000"}},"fontSize":"extra-small"} -->
					<p class="has-text-color has-extra-small-font-size" style="color:#000000">' . esc_html__( 'There are many pathways to becoming a film director. Some film directors started as screenwriters, cinematographers, producers, film editors, or actors. Directors use different approaches. In this course you will also learn about each of these points and figure out which one is for you.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:spacer {"height":"8px"} -->
					<div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:sensei-lms/button-take-course {"align":"left","backgroundColor":"foreground","textColor":"background"} -->
					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
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

					<!-- wp:group {"backgroundColor":"foreground","textColor":"background"} -->
					<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background"><!-- wp:spacer {"height":"24px"} -->
					<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:heading -->
					<h2>' . esc_html__( "Let's get started", 'sensei-lms' ) . '</h2>
					<!-- /wp:heading -->

					<!-- wp:spacer {"height":"24px"} -->
					<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:sensei-lms/course-progress /-->

					<!-- wp:sensei-lms/course-outline -->
					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Introduction', 'sensei-lms' ) . '"} /-->

					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( "Meeting Doug's network", 'sensei-lms' ) . '"} /-->

					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Start your journey', 'sensei-lms' ) . '"} /-->

					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'From script to film', 'sensei-lms' ) . '"} /-->
					<!-- /wp:sensei-lms/course-outline -->

					<!-- wp:spacer {"height":"8px"} -->
					<div style="height:8px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group"><!-- wp:sensei-lms/button-take-course {"align":"left","backgroundColor":"background","textColor":"foreground"} -->
					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
					<!-- /wp:sensei-lms/button-take-course -->

					<!-- wp:sensei-lms/button-contact-teacher -->
					<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">' . esc_html__( 'Contact Teacher', 'sensei-lms' ) . '</a></div>
					<!-- /wp:sensei-lms/button-contact-teacher --></div>
					<!-- /wp:group -->

					<!-- wp:spacer {"height":"24px"} -->
					<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer --></div>
					<!-- /wp:group -->',
];

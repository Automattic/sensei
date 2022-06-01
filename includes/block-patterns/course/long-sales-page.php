<?php
/**
 * Long Sales Page pattern.
 *
 * @package sensei-lms
 */

return [
	'title'      => __( 'Long Sales Page', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => '<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaType":"image","mediaWidth":58,"mediaSizeSlug":"full","verticalAlignment":"center","imageFill":false,"style":{"color":{"background":"#121c1c"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background"} -->
					<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile is-vertically-aligned-center has-background-color has-text-color has-background has-link-color" style="background-color:#121c1c;grid-template-columns:auto 58%"><figure class="wp-block-media-text__media"><img src="' . esc_url( Sensei()->assets->get_image( 'patterns-long-sales-01.jpg' ) ) . '" alt="" /></figure><div class="wp-block-media-text__content"><!-- wp:group {"style":{"spacing":{"padding":{"top":"2em","right":"2em","bottom":"2em","left":"2em"}},"elements":{"link":{"color":{"text":"#fffdc7"}}}},"layout":{"inherit":false}} -->
					<div class="wp-block-group has-link-color" style="padding-top:2em;padding-right:2em;padding-bottom:2em;padding-left:2em"><!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"700","fontSize":"48px","lineHeight":"1.15"}}} -->
					<h1 style="font-size:48px;font-weight:700;line-height:1.15"><strong>' . esc_html__( 'Deep dive into portrait photography', 'sensei-lms' ) . '</strong></h1>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"sensei-pattern-description"} -->
					<p class="sensei-pattern-description">' . esc_html__( 'Learn from Jeff Bronson how to shoot photography like a pro in any outside light conditions.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:sensei-lms/button-take-course {"textColor":"background"} -->
					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-text-color">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
					<!-- /wp:sensei-lms/button-take-course --></div>
					<!-- /wp:group --></div></div>
					<!-- /wp:media-text -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:heading -->
					<h2>' . esc_html__( 'What is portrait photography', 'sensei-lms' ) . '</h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>' . wp_kses_post( __( '<strong>Portrait photography</strong>, or <strong>portraiture</strong>, is a type of photography aimed at capturing the personality of a person or group of people by using effective lighting, backdrops, and poses. A portrait photograph may be artistic or clinical.', 'sensei-lms' ) ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:columns -->
					<div class="wp-block-columns"><!-- wp:column -->
					<div class="wp-block-column"><!-- wp:image {"id":1309,"sizeSlug":"full","linkDestination":"none"} -->
					<figure class="wp-block-image size-full"><img src="' . esc_url( Sensei()->assets->get_image( 'patterns-long-sales-02.jpg' ) ) . '" alt="" class="wp-image-1309"/></figure>
					<!-- /wp:image --></div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column"></div>
					<!-- /wp:column -->

					<!-- wp:column {"width":"10%"} -->
					<div class="wp-block-column" style="flex-basis:10%"></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:columns -->
					<div class="wp-block-columns"><!-- wp:column -->
					<div class="wp-block-column"></div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column"><!-- wp:quote -->
					<blockquote class="wp-block-quote"><p>' . esc_html__( 'A photographic portrait means to consider who we have before us and what we want to show about that person.', 'sensei-lms' ) . '</p><cite>Jeff Bronson</cite></blockquote>
					<!-- /wp:quote --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:spacer {"height":24} -->
					<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)","bottom":"var(\u002d\u002dwp\u002d\u002dcustom\u002d\u002dspacing\u002d\u002dlarge, 8rem)"}},"elements":{"link":{"color":{"text":"var:preset|color|background"}}},"color":{"background":"#121c1c"}},"textColor":"background","layout":{"inherit":true}} -->
					<div class="wp-block-group alignfull has-background-color has-text-color has-background has-link-color" style="background-color:#121c1c;padding-top:var(--wp--custom--spacing--large, 8rem);padding-bottom:var(--wp--custom--spacing--large, 8rem)"><!-- wp:heading -->
					<h2>' . esc_html__( 'What you will learn to master', 'sensei-lms' ) . '</h2>
					<!-- /wp:heading -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:group {"align":"full","layout":{"inherit":true}} -->
					<div class="wp-block-group alignfull"><!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
					<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
					<!-- /wp:separator -->

					<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
					<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
					<h3>' . esc_html__( 'Lighting for portraiture', 'sensei-lms' ) . '</h3>
					<!-- /wp:heading --></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
					<p>' . esc_html__( "There are many techniques available to light a subject's face.", 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
					<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
					<!-- /wp:separator -->

					<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
					<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
					<h3>' . esc_html__( 'Three-point lighting', 'sensei-lms' ) . '</h3>
					<!-- /wp:heading --></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
					<p>' . esc_html__( 'Three-point lighting is one of the most common lighting setups. It is traditionally used in a studio, but photographers may use it on-location in combination with ambient light.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
					<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
					<!-- /wp:separator -->

					<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
					<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
					<h3>' . esc_html__( 'Key light', 'sensei-lms' ) . '</h3>
					<!-- /wp:heading --></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
					<p>' . esc_html__( "The key light, also known as the main light, is placed either to the left, right, or above the subject's face, typically 30 to 60 degrees from the camera.", 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
					<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
					<!-- /wp:separator -->

					<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
					<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"210px"} -->
					<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:210px"><!-- wp:heading {"level":3} -->
					<h3>' . esc_html__( 'Fill light', 'sensei-lms' ) . '</h3>
					<!-- /wp:heading --></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"></div>
					<!-- /wp:column -->

					<!-- wp:column {"verticalAlignment":"center"} -->
					<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
					<p>' . esc_html__( 'The fill light, also known as the secondary main light, is typically placed opposite the key light.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->

					<!-- wp:separator {"opacity":"css","backgroundColor":"background","className":"alignwide is-style-wide"} -->
					<hr class="wp-block-separator has-text-color has-background-color has-css-opacity has-background-background-color has-background alignwide is-style-wide"/>
					<!-- /wp:separator --></div>
					<!-- /wp:group --></div>
					<!-- /wp:group -->

					<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"6rem","bottom":"6rem"}},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"background","textColor":"foreground","layout":{"inherit":true}} -->
					<div class="wp-block-group alignfull has-foreground-color has-background-background-color has-text-color has-background has-link-color" style="padding-top:6rem;padding-bottom:6rem"><!-- wp:media-text {"mediaId":1337,"mediaType":"image","verticalAlignment":"bottom","imageFill":false} -->
					<div class="wp-block-media-text alignwide is-stacked-on-mobile is-vertically-aligned-bottom"><figure class="wp-block-media-text__media"><img src="' . esc_url( Sensei()->assets->get_image( 'patterns-long-sales-01.jpg' ) ) . '" alt="" class="wp-image-1337 size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:heading -->
					<h2>' . esc_html__( 'Meet Jeff Bronson', 'sensei-lms' ) . '</h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>' . esc_html__( 'You will begin by getting to know the work of Jeff de Bronson, who will also teach you how to learn the best tricks he accumulated throughout his 25+ years of experience.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph -->
					<p>' . esc_html__( 'Jeff lives in NYC and has worked for many world-famous publications. In his free time, he likes to discover new ways of how he can pass on the skills and artistic views accumulated in his journey.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:sensei-lms/button-contact-teacher -->
					<div class="wp-block-sensei-lms-button-contact-teacher is-style-outline wp-block-sensei-button wp-block-button has-text-align-left"><a class="wp-block-button__link">' . esc_html__( 'Contact Teacher', 'sensei-lms' ) . '</a></div>
					<!-- /wp:sensei-lms/button-contact-teacher --></div></div>
					<!-- /wp:media-text --></div>
					<!-- /wp:group -->

					<!-- wp:group {"align":"full","style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}},"spacing":{"padding":{"top":"6rem","bottom":"4rem"}}},"backgroundColor":"foreground","textColor":"background","layout":{"inherit":true}} -->
					<div class="wp-block-group alignfull has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:6rem;padding-bottom:4rem"><!-- wp:columns {"align":"wide"} -->
					<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
					<div class="wp-block-column" style="flex-basis:50%"><!-- wp:heading {"fontSize":"x-large"} -->
					<h2 class="has-x-large-font-size" id="extended-trailer">' . esc_html__( 'Jeff at work', 'sensei-lms' ) . '</h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p>' . esc_html__( 'Meet Jeff in his studio and see firsthand how he approaches a photoshoot in this exclusive trailer.', 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph -->
					<p>' . esc_html__( "Unlock the full video by signing up for Jeff's course.", 'sensei-lms' ) . '</p>
					<!-- /wp:paragraph -->

					<!-- wp:sensei-lms/button-take-course {"textColor":"background"} -->

					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-text-color">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
					<!-- /wp:sensei-lms/button-take-course --></div>
					<!-- /wp:column -->

					<!-- wp:column {"width":"66.66%"} -->
					<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:video -->
					<figure class="wp-block-video"><video controls src="' . esc_url( Sensei()->assets->get_image( 'patterns-video.mp4' ) ) . '"></video></figure>
					<!-- /wp:video --></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns --></div>
					<!-- /wp:group -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:image {"align":"center","width":150,"height":150,"sizeSlug":"large","linkDestination":"none","className":"is-style-rounded"} -->
					<figure class="wp-block-image aligncenter size-large is-resized is-style-rounded"><img src="' . esc_url( Sensei()->assets->get_image( 'patterns-long-sales-03.jpg' ) ) . '" alt="A side profile of a woman in a russet-colored turtleneck and white bag. She looks up with her eyes closed." width="150" height="150"/></figure>
					<!-- /wp:image -->

					<!-- wp:quote {"align":"center","className":"is-style-large"} -->
					<blockquote class="wp-block-quote has-text-align-center is-style-large"><p>' . esc_html__( '"Jeff\'s course really help me understand how to work with light and my closer to my subjects. Amazing course!"', 'sensei-lms' ) . '</p><cite>— Anna Wong, <em>' . esc_html__( 'Volunteer', 'sensei-lms' ) . '</em></cite></blockquote>
					<!-- /wp:quote -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:group {"backgroundColor":"foreground","textColor":"background","layout":{"inherit":false}} -->
					<div class="wp-block-group has-background-color has-foreground-background-color has-text-color has-background"><!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer -->

					<!-- wp:heading {"textAlign":"left"} -->
					<h2 class="has-text-align-left">' . esc_html__( 'Course Lessons', 'sensei-lms' ) . '</h2>
					<!-- /wp:heading -->

					<!-- wp:sensei-lms/course-progress /-->

					<!-- wp:sensei-lms/course-outline -->
					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Lighting for portraiture', 'sensei-lms' ) . '"} /-->

					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Three-point lighting', 'sensei-lms' ) . '"} /-->

					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Key light', 'sensei-lms' ) . '"} /-->

					<!-- wp:sensei-lms/course-outline-lesson {"title":"' . esc_html__( 'Fill light', 'sensei-lms' ) . '"} /-->
					<!-- /wp:sensei-lms/course-outline -->

					<!-- wp:sensei-lms/button-take-course {"textColor":"background"} -->
					<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-left"><button class="wp-block-button__link has-background-color has-text-color">' . esc_html__( 'Take Course', 'sensei-lms' ) . '</button></div>
					<!-- /wp:sensei-lms/button-take-course -->

					<!-- wp:spacer {"height":16} -->
					<div style="height:16px" aria-hidden="true" class="wp-block-spacer"></div>
					<!-- /wp:spacer --></div>
					<!-- /wp:group -->',
];

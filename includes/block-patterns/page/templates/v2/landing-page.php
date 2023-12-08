<?php
/**
 * Landing page with Course List as Grid.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"20px","left":"20px","top":"60px","bottom":"100px"},"margin":{"top":"0","bottom":"0"}}},"className":"sensei-landing-pattern-header","layout":{"type":"constrained","contentSize":"660px"}} -->
<div class="wp-block-group alignfull sensei-landing-pattern-header" style="margin-top:0;margin-bottom:0;padding-top:60px;padding-right:20px;padding-bottom:100px;padding-left:20px"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"textTransform":"uppercase"}},"fontSize":"xx-large","fontFamily":"heading"} -->
<h1 class="wp-block-heading has-text-align-center has-heading-font-family has-xx-large-font-size" style="text-transform:uppercase"><?php echo esc_html__( 'Grow your writing skills with Course', 'sensei-lms' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":"fontSize":"medium","fontFamily":"body"} -->
<p class="has-text-align-center has-body-font-family has-medium-font-size"><?php echo esc_html__( 'Writing is powerful! Allows you to articulate and explain yourself to others. When done well, it allows you to tell stories that could fascinate millions.', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"typography":{"textTransform":"uppercase"}},"fontFamily":"heading"} -->
<div class="wp-block-button has-heading-font-family" style="text-transform:uppercase"><a class="wp-block-button__link wp-element-button"><?php echo esc_html__( 'Start Learning Now', 'sensei-lms' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","right":"20px","left":"20px","bottom":"100px"},"blockGap":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"foreground","textColor":"background","className":"sensei-course-theme-course-list-pattern","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull sensei-course-theme-course-list-pattern has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:80px;padding-right:20px;padding-bottom:100px;padding-left:20px"><!-- wp:group {"style":{"border":{"left":{"width":"1px","style":"solid"},"top":{},"right":{},"bottom":{}},"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-left-style:solid;border-left-width:1px;margin-bottom:40px;padding-left:20px"><!-- wp:heading {"style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"700"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-style:normal;font-weight:700;text-transform:uppercase"><?php echo esc_html__( 'Course List', 'sensei-lms' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":"className":"sensei-course-list-all-courses-link","fontSize":"small","fontFamily":"system"} -->
<p class="sensei-course-list-all-courses-link has-link-color has-system-font-family has-small-font-size"><a href="<?php echo esc_url( Sensei_Course::get_courses_page_url() ); ?>" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Explore all courses', 'sensei-lms' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":"3","inherit":false},"className":"wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list\u002d\u002dis-grid-view","layout":{"type":"default"}} -->
<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-grid-view"><!-- wp:post-template {"align":"wide","layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px"},"border":{"width":"0px","style":"none"}},"layout":{"inherit":false}} -->
<div class="wp-block-group" style="border-style:none;border-width:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:post-featured-image {"isLink":true,"height":"200px","lock":{"move":false,"remove":false},"style":{"border":{"width":"1px"}},"borderColor":"background"} /-->

<!-- wp:sensei-lms/course-categories {"options":{"backgroundColor":"#F1EDE7","textColor":"#00594F"},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
<div style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;--sensei-lms-course-categories-background-color:#F1EDE7;--sensei-lms-course-categories-text-color:#00594F" class="wp-block-sensei-lms-course-categories"></div>
<!-- /wp:sensei-lms/course-categories -->

<!-- wp:post-title {"textAlign":"left","isLink":true,"lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

<!-- wp:post-author {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"x-small","fontFamily":"system"} /-->

<!-- wp:post-excerpt {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"20px","right":"0px","bottom":"20px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

<!-- wp:sensei-lms/course-actions {"lock":{"move":false,"remove":false}} -->
<!-- wp:sensei-lms/button-take-course {"align":"full","backgroundColor":"background","textColor":"foreground"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background"><?php echo esc_html__( 'Start', 'sensei-lms' ); ?></button></div>
<!-- /wp:sensei-lms/button-take-course -->

<!-- wp:sensei-lms/button-continue-course {"align":"full","backgroundColor":"background","textColor":"foreground"} -->
<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background"><?php echo esc_html__( 'Continue', 'sensei-lms' ); ?></a></div>
<!-- /wp:sensei-lms/button-continue-course -->

<!-- wp:sensei-lms/button-view-results {"align":"full","className":"is-style-default","backgroundColor":"background","textColor":"foreground"} -->
<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background"><?php echo esc_html__( 'Results', 'sensei-lms' ); ?></a></div>
<!-- /wp:sensei-lms/button-view-results -->
<!-- /wp:sensei-lms/course-actions --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"},"blockGap":"0px"}},"className":"sensei-blog-posts-pattern","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull sensei-blog-posts-pattern" style="padding-top:80px;padding-right:20px;padding-bottom:80px;padding-left:20px"><!-- wp:group {"style":{"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}},"border":{"left":{"width":"1px","style":"solid"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="border-left-style:solid;border-left-width:1px;margin-bottom:40px;padding-left:20px"><!-- wp:heading {"style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"700"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-style:normal;font-weight:700;text-transform:uppercase"><?php echo esc_html__( 'Blog Posts', 'sensei-lms' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sensei-pattern-header-link","fontSize":"small","fontFamily":"system"} -->
<p class="sensei-pattern-header-link has-system-font-family has-small-font-size"><a href="#"><?php echo esc_html__( 'Visit the blog', 'sensei-lms' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"query":{"perPage":"3","pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:post-featured-image {"isLink":true,"width":"","height":"218px","align":"center","style":{"border":{"width":"1px"},"spacing":{"margin":{"bottom":"40px"}}}} /-->

<!-- wp:post-date {"style":{"spacing":{"margin":{"bottom":"20px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

<!-- wp:post-title {"isLink":true,"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"bottom":"40px"}}},"fontSize":"x-large","fontFamily":"heading"} /-->

<!-- wp:post-excerpt /--></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","right":"20px","left":"20px"},"margin":{"bottom":"100px"}}},"layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull" style="margin-bottom:100px;padding-top:0px;padding-right:20px;padding-bottom:0px;padding-left:20px"><!-- wp:group {"style":{"spacing":{"padding":{"left":"40px","top":"20px","bottom":"20px"}},"border":{"left":{"width":"1px","style":"solid"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="border-left-style:solid;border-left-width:1px;padding-top:20px;padding-bottom:20px;padding-left:40px"><!-- wp:heading {"textAlign":"left","style":{"typography":{"textTransform":"uppercase"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
<h2 class="wp-block-heading has-text-align-left" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;text-transform:uppercase"><?php echo wp_kses( __( 'Keep track of the latest<br>news and lessons.<br>Every week in your inbox.', 'sensei-lms' ), [ 'br' => [] ] ); ?></h2>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right","verticalAlignment":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php echo esc_html__( 'Join Our Mailing List', 'sensei-lms' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"120px","right":"20px","left":"20px"},"blockGap":"0px"}},"backgroundColor":"foreground","textColor":"background","className":"wp-sensei-testimonial-pattern","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull wp-sensei-testimonial-pattern has-background-color has-foreground-background-color has-text-color has-background" style="padding-top:80px;padding-right:20px;padding-bottom:120px;padding-left:20px">

<!-- wp:group {"style":{"border":{"left":{"width":"1px","style":"solid"}},"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group" style="border-left-style:solid;border-left-width:1px;margin-bottom:40px;padding-left:20px"><!-- wp:heading {"style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"700"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-style:normal;font-weight:700;text-transform:uppercase"><?php echo esc_html__( 'What Students Say', 'sensei-lms' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"35px","left":"35px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"bottom","width":"40%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:40%"><!-- wp:image {"id":2150,"sizeSlug":"full","linkDestination":"none","style":{"border":{"width":"1px"}}} -->
<figure class="wp-block-image size-full has-custom-border"><img src="<?php echo esc_url( Sensei()->assets->get_image( 'testimonial.png' ) ); ?>" alt="" class="wp-image-2150" style="border-width:1px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom"} -->
<div class="wp-block-column is-vertically-aligned-bottom">
<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"40px"},"padding":{"bottom":"0px"}},"typography":{"textTransform":"uppercase","lineHeight":"1"}},"fontSize":"x-large","fontFamily":"heading"} -->
<p class="has-heading-font-family has-x-large-font-size" style="margin-bottom:40px;padding-bottom:0px;line-height:1;text-transform:uppercase"><?php echo esc_html__( '“I always wanted to write, and thanks to Course, I got it right. My writing is clearer, and I can finally get my message across.”', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"700"},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}},"fontSize":"small","fontFamily":"system"} -->
<p class="has-system-font-family has-small-font-size" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0;font-style:normal;font-weight:700">Christopher Brown</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}},"fontSize":"small","fontFamily":"system"} -->
<p class="has-system-font-family has-small-font-size" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><?php echo esc_html__( 'Founder at BeautifulWriting.com', 'sensei-lms' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

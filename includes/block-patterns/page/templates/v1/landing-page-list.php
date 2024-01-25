<?php
/**
 * Default Landing Page pattern content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"20px","left":"20px","top":"62px","bottom":"97px"},"blockGap":"2.5rem","margin":{"top":"0","bottom":"0"}}},"className":"sensei-landing-pattern-header","layout":{"type":"constrained","contentSize":"660px"}} -->
<div class="wp-block-group alignfull sensei-landing-pattern-header" style="margin-top:0;margin-bottom:0;padding-top:62px;padding-right:20px;padding-bottom:97px;padding-left:20px">
	<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"textTransform":"uppercase","lineHeight":"0.9"}},"textColor":"primary"} -->
	<h1 class="has-text-align-center has-primary-color has-text-color" style="line-height:0.9;text-transform:uppercase"><?php echo esc_html__( 'grow your writing Skills with Course', 'sensei-lms' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"letterSpacing":"-1%","lineHeight":"1.3"}},"textColor":"primary"} -->
	<p class="has-text-align-center has-primary-color has-text-color" style="letter-spacing:-1%;line-height:1.3"><?php echo esc_html__( 'Writing is powerful! Allows you to articulate and explain yourself to others. When done well, it allows you to tell stories that could fascinate millions.', 'sensei-lms' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"primary","textColor":"background","style":{"border":{"radius":"8px"}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color has-background wp-element-button" style="border-radius:8px"><?php echo esc_html__( 'START LEARNING NOW', 'sensei-lms' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->



<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"5rem","right":"20px","left":"20px","bottom":"100px"},"blockGap":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"primary","textColor":"background","className":"sensei-course-theme-course-list-pattern","layout":{"type":"constrained","contentSize":""}} -->
<div class="wp-block-group alignfull sensei-course-theme-course-list-pattern has-background-color has-primary-background-color has-text-color has-background has-link-color" style="padding-top:5rem;padding-right:20px;padding-bottom:100px;padding-left:20px">
	<!-- wp:group {"style":{"border":{"left":{"color":"var:preset|color|background","width":"1px"}},"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group" style="border-left-color:var(--wp--preset--color--background);border-left-width:1px;margin-bottom:40px;padding-left:20px">
		<!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.01em","lineHeight":"1.0"},"spacing":{"padding":{"top":"0px"}}},"textColor":"background","className":"sensei-pattern-heading","fontSize":"medium"} -->
		<h2 class="sensei-pattern-heading has-background-color has-text-color has-medium-font-size" style="padding-top:0px;letter-spacing:0.01em;line-height:1.0;text-transform:uppercase"><strong><?php echo esc_html__( 'Course list', 'sensei-lms' ); ?></strong></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","className":"sensei-course-list-all-courses-link"} -->
		<p class="sensei-course-list-all-courses-link has-background-color has-text-color has-link-color"><a href="<?php echo esc_url( Sensei_Course::get_courses_page_url() ); ?>" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Explore all courses', 'sensei-lms' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":6,"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":"2","inherit":false},"style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","className":"wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list\u002d\u002dis-list-view","layout":{"type":"default"}} -->
	<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view has-background-color has-text-color has-link-color">
		<!-- wp:post-template {"align":"wide"} -->
		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"20px","left":"20px"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"width":"32%"} -->
			<div class="wp-block-column" style="flex-basis:32%">
				<!-- wp:post-featured-image {"isLink":true,"height":"380px","lock":{"move":false,"remove":false},"align":"full","style":{"border":{"width":"1px","radius":"4px"}},"borderColor":"background"} /-->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"50%","style":{"spacing":{"blockGap":"20px","padding":{"bottom":"20px"}}},"layout":{"type":"default"}} -->
			<div class="wp-block-column" style="padding-bottom:20px;flex-basis:50%">
				<!-- wp:sensei-lms/course-categories {"options":{},"fontFamily":"system","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"right":"0px","left":"0px","bottom":"11px","top":"20px"}}}} -->
				<div style="margin-top:20px;margin-right:0px;margin-bottom:11px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px" class="wp-block-sensei-lms-course-categories has-system-font-family"></div>
				<!-- /wp:sensei-lms/course-categories -->

				<!-- wp:post-title {"textAlign":"left","isLink":true,"lock":{"move":false,"remove":false},"align":"full","style":{"typography":{"textTransform":"uppercase","lineHeight":"1"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"right":"0px","bottom":"20px","left":"0px","top":"0px"}}},"textColor":"primary","className":"sensei-course-list-title-no-underline"} /-->

				<!-- wp:post-author {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"textColor":"background"} /-->

				<!-- wp:post-excerpt {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"right":"0px","left":"0px","bottom":"0px","top":"0px"},"margin":{"top":"2.5rem"}}},"textColor":"background","className":"sensei-post-excerpt-no-margin"} /-->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"15%"} -->
			<div class="wp-block-column" style="flex-basis:15%">
				<!-- wp:sensei-lms/course-actions {"lock":{"move":false,"remove":false}} -->
				<!-- wp:sensei-lms/button-take-course {"align":"full","borderRadius":8,"buttonClassName":[],"backgroundColor":"background","textColor":"primary"} -->
				<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px"><?php echo esc_html__( 'Start', 'sensei-lms' ); ?></button></div>
				<!-- /wp:sensei-lms/button-take-course -->

				<!-- wp:sensei-lms/button-continue-course {"align":"full","borderRadius":8,"buttonClassName":[],"backgroundColor":"background","textColor":"primary"} -->
				<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px"><?php echo esc_html__( 'Continue', 'sensei-lms' ); ?></a></div>
				<!-- /wp:sensei-lms/button-continue-course -->

				<!-- wp:sensei-lms/button-view-results {"align":"full","borderRadius":8,"buttonClassName":[],"className":"is-style-default","backgroundColor":"background","textColor":"primary"} -->
				<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px"><?php echo esc_html__( 'Results', 'sensei-lms' ); ?></a></div>
				<!-- /wp:sensei-lms/button-view-results -->
				<!-- /wp:sensei-lms/course-actions -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"20px","right":"20px"},"blockGap":"0px"}},"backgroundColor":"background","textColor":"foreground","className":"sensei-blog-posts-pattern","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull sensei-blog-posts-pattern has-foreground-color has-background-background-color has-text-color has-background" style="padding-top:80px;padding-right:20px;padding-bottom:80px;padding-left:20px">
	<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"40px"}}},"textColor":"primary","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
	<div class="wp-block-group has-primary-color has-text-color" style="padding-bottom:40px">
		<!-- wp:group {"style":{"spacing":{"padding":{"left":"20px"}},"border":{"top":{"width":"0px","style":"none"},"right":{"width":"0px","style":"none"},"bottom":{"width":"0px","style":"none"},"left":{"width":"1px","color":"var:preset|color|primary"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;border-right-style:none;border-right-width:0px;border-bottom-style:none;border-bottom-width:0px;border-left-color:var(--wp--preset--color--primary);border-left-width:1px;padding-left:20px">
			<!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase","lineHeight":"1"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"className":"sensei-pattern-heading","fontSize":"medium"} -->
			<h2 class="sensei-pattern-heading has-medium-font-size" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;line-height:1;text-transform:uppercase"><?php echo esc_html__( 'Blog posts', 'sensei-lms' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"sensei-pattern-header-link"} -->
		<p class="sensei-pattern-header-link"><a href="#"><?php echo esc_html__( 'Visit the blog', 'sensei-lms' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"query":{"perPage":"3","pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"exclude","inherit":false},"displayLayout":{"type":"flex","columns":3}} -->
	<div class="wp-block-query">
		<!-- wp:post-template -->
		<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
			<!-- wp:post-featured-image {"isLink":true,"width":"","height":"218px","align":"center","style":{"border":{"radius":"4px","width":"1px"}},"borderColor":"primary"} /-->

			<!-- wp:post-date {"style":{"spacing":{"margin":{"top":"2.5rem","bottom":"1.25rem"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"typography":{"lineHeight":"1","letterSpacing":"0.02em"}},"textColor":"primary"} /-->

			<!-- wp:post-title {"isLink":true,"style":{"typography":{"lineHeight":"1","textTransform":"uppercase"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"bottom":"2.5rem"}}},"textColor":"primary","className":"sensei-pattern-no-underline"} /-->

			<!-- wp:post-excerpt {"style":{"typography":{"lineHeight":"1.3"}}} /-->
		</div>
		<!-- /wp:group -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->




<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","bottom":"100px","right":"20px","left":"20px"}}},"className":"sensei-pattern-mail-list","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull sensei-pattern-mail-list" style="padding-top:0px;padding-right:20px;padding-bottom:100px;padding-left:20px">
	<!-- wp:group {"style":{"spacing":{"padding":{"left":"38px","top":"20px","bottom":"20px"}},"border":{"top":{"width":"0px","style":"none"},"right":{"width":"0px","style":"none"},"bottom":{"width":"0px","style":"none"},"left":{"color":"var:preset|color|foreground"}}},"className":"is-style-group-left-border","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group is-style-group-left-border" style="border-top-style:none;border-top-width:0px;border-right-style:none;border-right-width:0px;border-bottom-style:none;border-bottom-width:0px;border-left-color:var(--wp--preset--color--foreground);padding-top:20px;padding-bottom:20px;padding-left:38px">
		<!-- wp:heading {"textAlign":"left","style":{"typography":{"lineHeight":"1","textTransform":"uppercase"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontFamily":"secondary"} -->
		<h2 class="has-text-align-left has-secondary-font-family" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;line-height:1;text-transform:uppercase"><?php echo wp_kses( __( 'Keep track of the latest<br>news and lessons.<br>Every week in your inbox.', 'sensei-lms' ), [ 'br' => [] ] ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right","verticalAlignment":"center"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"foreground","textColor":"background","style":{"border":{"radius":"8px"}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-foreground-background-color has-text-color has-background wp-element-button" style="border-radius:8px"><?php echo esc_html__( 'Join Our Mailing List', 'sensei-lms' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->




<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"120px","right":"20px","left":"20px"},"blockGap":"0px"}},"backgroundColor":"foreground","textColor":"background","className":"wp-sensei-testimonial-pattern","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignfull wp-sensei-testimonial-pattern has-background-color has-foreground-background-color has-text-color has-background" style="padding-top:80px;padding-right:20px;padding-bottom:120px;padding-left:20px"><!-- wp:group {"style":{"spacing":{"padding":{"left":"20px","bottom":"0px"}},"border":{"top":{"width":"0px","style":"none"},"right":{"width":"0px","style":"none"},"bottom":{"width":"0px","style":"none"},"left":{"color":"var:preset|color|tertiary","width":"1px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group" style="border-top-style:none;border-top-width:0px;border-right-style:none;border-right-width:0px;border-bottom-style:none;border-bottom-width:0px;border-left-color:var(--wp--preset--color--tertiary);border-left-width:1px;padding-bottom:0px;padding-left:20px"><!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase","lineHeight":"1","letterSpacing":"0.01em"},"spacing":{"padding":{"top":"0px"}}},"className":"sensei-pattern-heading","fontSize":"medium","fontFamily":"secondary"} -->
		<h2 class="sensei-pattern-heading has-secondary-font-family has-medium-font-size" style="padding-top:0px;letter-spacing:0.01em;line-height:1;text-transform:uppercase"><?php echo esc_html__( 'What students say', 'sensei-lms' ); ?></h2>
		<!-- /wp:heading --></div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"35px","left":"35px"},"padding":{"top":"40px"}}}} -->
	<div class="wp-block-columns" style="padding-top:40px"><!-- wp:column {"verticalAlignment":"bottom","width":"41%"} -->
		<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:41%"><!-- wp:image {"id":2150,"sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"8px","width":"1px"}},"borderColor":"background"} -->
			<figure class="wp-block-image size-full has-custom-border"><img src="<?php echo esc_url( Sensei()->assets->get_image( 'testimonial.png' ) ); ?>" alt="" class="has-border-color has-background-border-color wp-image-2150" style="border-width:1px;border-radius:8px"/></figure>
			<!-- /wp:image --></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"bottom"} -->
		<div class="wp-block-column is-vertically-aligned-bottom">
			<!-- wp:heading {"style":{"spacing":{"margin":{"bottom":"40px"}},"typography":{"lineHeight":"1","textTransform":"uppercase"}},"textColor":"background","fontFamily":"secondary"} -->
			<h2 class="has-background-color has-text-color has-secondary-font-family" style="margin-bottom:40px;line-height:1;text-transform:uppercase"><?php echo esc_html__( '“I always wanted to write, and thanks to Course, I got it right. My writing is clearer, and I can finally get my message across.”', 'sensei-lms' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","lineHeight":"1","letterSpacing":"0.02em"}}} -->
			<p style="font-style:normal;font-weight:700;letter-spacing:0.02em;line-height:1">Christopher Brown</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1","letterSpacing":"0.02em"},"spacing":{"padding":{"top":"10px"},"margin":{"top":"0px"}}}} -->
			<p style="margin-top:0;padding-top:10px;letter-spacing:0.02em;line-height:1"><?php echo esc_html__( 'Founder at BeautifulWriting.com', 'sensei-lms' ); ?></p>
			<!-- /wp:paragraph --></div>
		<!-- /wp:column --></div>
	<!-- /wp:columns --></div>
<!-- /wp:group -->

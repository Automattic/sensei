<?php
/**
 * Quiz Graded email pattern.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"56px","bottom":"56px","left":"56px"}},"color":{"background":"#ffffff"}}} -->
<div class="wp-block-group has-background" style="background-color:#ffffff;padding-top:40px;padding-right:56px;padding-bottom:56px;padding-left:56px"><!-- wp:post-title {"style":{"typography":{"textTransform":"none","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#101517"}}} /-->

	<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"},"margin":{"top":"48px"}}}} -->
	<div class="wp-block-group has-background" style="background-color:#f6f7f7;margin-top:48px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"32px"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"24px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:0px;margin-right:0px;margin-bottom:24px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:32px"><strong>[grade:validation]</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"0px","bottom":"0px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:0px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:16px"><strong>Course Name</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#101517"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:0;margin-bottom:0;font-size:16px">[course:name]</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:24px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:16px"><strong>Lesson Name</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#101517"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:0;margin-bottom:0;font-size:16px">[lesson:name]</p>
		<!-- /wp:paragraph -->

		<!-- wp:group {"style":{"spacing":{"padding":{"right":"0px","bottom":"0px","left":"0px","top":"0px"},"blockGap":"10px","margin":{"top":"24px"}},"border":{"width":"0px","style":"none"}}} -->
		<div class="wp-block-group" style="border-style:none;border-width:0px;margin-top:24px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"23px","bottom":"0px","right":"0px"}},"color":{"text":"#101517"}}} -->
			<p class="has-text-color" style="color:#101517;margin-top:23px;margin-right:0px;margin-bottom:0px;font-size:16px"><strong>Your Grade</strong></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"24px","lineHeight":"1"},"spacing":{"margin":{"bottom":"40px"}},"color":{"text":"#101517"}}} -->
			<p class="has-text-color" style="color:#101517;margin-bottom:40px;font-size:24px;line-height:1"><strong>[grade:percentage]</strong></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}}} -->
		<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#101517","text":"#fefefe"},"typography":{"fontSize":"16px"}}} -->
			<div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="[quiz:url]" style="color:#fefefe;background-color:#101517;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Review Quiz</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

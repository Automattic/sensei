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

<!-- wp:post-title {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#020202"},"spacing":{"margin":{"bottom":"48px"}}}} /-->

<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}}}} -->
<div class="wp-block-group has-background" style="background-color:#f6f7f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"32px"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#010101"}}} -->
	<p class="has-text-color" style="color:#010101;font-size:32px;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><strong>[grade:validation]</strong></p>
	<!-- /wp:paragraph -->

	<!-- wp:group -->
	<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;line-height:1;margin-top:24px;margin-bottom:0px"><strong>Course Name</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1"},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;line-height:1">[course:name]</p>
		<!-- /wp:paragraph --></div>
	<!-- /wp:group -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"5px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"10px"},"border":{"width":"0px","style":"none"}}} -->
	<div class="wp-block-group" style="border-style:none;border-width:0px;padding-top:5px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;line-height:1;margin-top:24px;margin-bottom:0px"><strong>Lesson Name</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1"},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;line-height:1">[lesson:name]</p>
		<!-- /wp:paragraph --></div>
	<!-- /wp:group -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"5px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"10px"},"border":{"width":"0px","style":"none"}}} -->
	<div class="wp-block-group" style="border-style:none;border-width:0px;padding-top:5px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;margin-top:24px;margin-bottom:0px"><strong>Your Grade</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"24px","lineHeight":"1"},"spacing":{"margin":{"bottom":"40px"}},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:24px;line-height:1;margin-bottom:40px"><strong>[grade:percentage]</strong></p>
		<!-- /wp:paragraph --></div>
	<!-- /wp:group -->

	<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}}} -->
	<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#020202","text":"#fefefe"}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background" href="[quiz:url]" style="background-color:#020202;color:#fefefe;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Review Quiz</a></div>
		<!-- /wp:button --></div>
	<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->

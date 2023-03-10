<?php
/**
 * Course Completed email pattern.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- wp:post-title {"style":{"typography":{"fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#020202"},"spacing":{"margin":{"bottom":"48px"}}}} /-->

<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"},"blockGap":"0px"}}} -->
<div class="wp-block-group has-background" style="background-color:#f6f7f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"32px","lineHeight":"1"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#010101"}}} -->
	<p class="has-text-color" style="color:#010101;font-size:32px;line-height:1;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><strong>[student:displayname]</strong></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"10px","padding":{"top":"0px","bottom":"20px"}}}} -->
	<div class="wp-block-group" style="padding-top:0px;padding-bottom:20px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"2"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;line-height:2;margin-top:24px;margin-bottom:0px"><strong>Course Name</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1"},"color":{"text":"#020202"}}} -->
		<p class="has-text-color" style="color:#020202;font-size:16px;line-height:1">[course:name]</p>
		<!-- /wp:paragraph --></div>
	<!-- /wp:group -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons"><!-- wp:button {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#020202","text":"#fefefe"},"border":{"radius":"4px"},"typography":{"fontSize":"16px"}}} -->
		<div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link has-text-color has-background" href="[completed:url]" style="border-radius:4px;background-color:#020202;color:#fefefe;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">View Results</a></div>
		<!-- /wp:button --></div>
	<!-- /wp:buttons --></div>
<!-- /wp:group -->

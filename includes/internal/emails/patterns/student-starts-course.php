<?php
/**
 * Student Starts Course Email pattern.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"56px","bottom":"56px","left":"56px"}},"color":{"background":"#ffffff"}}} -->
<div class="wp-block-group has-background" style="background-color:#ffffff;padding-top:40px;padding-right:56px;padding-bottom:56px;padding-left:56px"><!-- wp:post-title {"style":{"typography":{"textTransform":"capitalize","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#101517"}}} /-->

	<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"},"margin":{"top":"48px"}}}} -->
	<div class="wp-block-group has-background" style="background-color:#f6f7f7;margin-top:48px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"32px"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;font-size:32px;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><strong>[student:displayname]</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;font-size:16px;margin-top:24px;margin-bottom:0px"><strong>Course Name</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#101517"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<p class="has-text-color" style="color:#101517;font-size:16px;margin-top:0;margin-bottom:0">[course:name]</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}}} -->
		<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"style":{"color":{"text":"#fefefe","background":"#020202"}},"className":"has-custom-font-size"} -->
			<div class="wp-block-button has-custom-font-size"><a class="wp-block-button__link has-text-color has-background" href="[manage:students]" style="background-color:#020202;color:#fefefe">Manage Students</a></div>
			<!-- /wp:button --></div>
		<!-- /wp:buttons --></div>
	<!-- /wp:group --></div>
<!-- /wp:group -->
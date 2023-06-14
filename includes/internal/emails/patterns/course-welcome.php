<?php
/**
 * Course Welcome Email pattern.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"56px","left":"56px","bottom":"48px"}},"color":{"background":"#ffffff"}},"className":"email-notification__content"} -->
<div class="wp-block-group email-notification__content has-background" style="background-color:#ffffff;padding-top:40px;padding-right:56px;padding-bottom:48px;padding-left:56px"><!-- wp:post-title {"style":{"typography":{"textTransform":"capitalize","fontStyle":"normal","fontWeight":"700","fontSize":"40px","lineHeight":"1"},"color":{"text":"#101517"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"48px","left":"0px"}}},"className":"email-notification__title"} /-->

	<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"40px","left":"32px"},"margin":{"top":"0px","bottom":"0px"},"blockGap":"0px"}}} -->
	<div class="wp-block-group has-background" style="background-color:#f6f7f7;margin-top:0px;margin-bottom:0px;padding-top:32px;padding-right:32px;padding-bottom:40px;padding-left:32px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"32px","lineHeight":1},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:32px;line-height:1"><strong>[student:displayname]</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:24px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:16px"><strong><?php echo esc_html__( 'Course Name', 'sensei-lms' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"0px","bottom":"0px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;margin-top:0px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:16px">[course:name]</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}}} -->
		<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"style":{"color":{"text":"#fefefe","background":"#101517"},"typography":{"fontSize":"16px"}},"className":"has-custom-font-size"} -->
			<div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="[course:url]" style="color:#fefefe;background-color:#101517"><?php echo esc_html__( 'Start Course', 'sensei-lms' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"style":{"color":{"text":"#101517"},"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"36px","right":"0px","bottom":"0px","left":"0px"}}},"className":"info__extra"} -->
		<p class="info__extra has-text-color" style="color:#101517;margin-top:36px;margin-right:0px;margin-bottom:0px;margin-left:0px;font-size:16px"><?php echo esc_html__( 'Please login to start learning.', 'sensei-lms' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

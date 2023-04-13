<?php
/**
 * Course Expiration Today email pattern.
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
		<p class="has-text-color" style="color:#101517;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:32px"><strong>[student:displayname]</strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#101517"}}} -->
		<p class="has-text-color" style="color:#101517;font-size:16px;margin-top:24px;margin-bottom:0px"><strong><?php echo esc_html__( 'Course Name', 'sensei-lms' ); ?></strong></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#101517"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<p class="has-text-color" style="color:#101517;font-size:16px;margin-top:0;margin-bottom:0">[course:name]</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px","bottom":"20px"}}}} -->
		<div class="wp-block-buttons" style="margin-top:40px;margin-bottom:20px"><!-- wp:button {"style":{"color":{"background":"#101517","text":"#fefefe"},"typography":{"fontSize":"16px"}},"className":"has-custom-font-size"} -->
			<div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link has-text-color has-background" href="[resume:url]" style="color:#fefefe;background-color:#101517"><?php echo esc_html__( 'Resume Course', 'sensei-lms' ); ?></a></div>
			<!-- /wp:button --></div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"style":{"color":{"text":"#101517"},"typography":{"fontSize":"16px"}},"className":"info__extra"} -->
		<p class="info__extra has-text-color" style="color:#101517;font-size:16px"><?php echo esc_html__( 'You can enjoy access to all the course materials and quizzes until midnight today.', 'sensei-lms' ); ?></p>
		<!-- /wp:paragraph --></div>
	<!-- /wp:group --></div>
<!-- /wp:group -->

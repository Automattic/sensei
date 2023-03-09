<?php
/**
 * Course Created email pattern.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"56px","bottom":"56px","left":"56px"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background" style="background-color:#ffffff;padding-top:40px;padding-right:56px;padding-bottom:56px;padding-left:56px"><!-- wp:post-title {"style":{"typography":{"textTransform":"none","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#43af99"},"spacing":{"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"},"margin":{"top":"48px"}}}} -->
<div class="wp-block-group has-background" style="background-color:#f6f7f7;margin-top:48px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"0px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;margin-top:0px;margin-bottom:0px;font-size:16px"><strong>Course Name</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#020202"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-color" style="color:#020202;margin-top:0;margin-bottom:0;font-size:16px">[course:name]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;margin-top:24px;margin-bottom:0px;font-size:16px"><strong>Teacher Name</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#020202"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-color" style="color:#020202;margin-top:0;margin-bottom:0;font-size:16px">[teacher:displayname]</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}}} -->
<div class="wp-block-buttons" style="margin-top:40px"><!-- wp:button {"textColor":"black","style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#43af99"},"typography":{"fontSize":"16px"}}} -->
<div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link has-black-color has-text-color has-background wp-element-button" href="[manage:course]" style="background-color:#43af99;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Manage course</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

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

<!-- wp:post-title {"style":{"typography":{"textTransform":"capitalize","fontStyle":"normal","fontWeight":"700","fontSize":"40px"},"color":{"text":"#020202"}}} /-->

<!-- wp:group {"style":{"color":{"background":"#f6f7f7"},"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}}}} -->
<div class="wp-block-group has-background" style="background-color:#f6f7f7;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;margin-top:24px;margin-bottom:0px;font-size:16px"><strong>Course Name</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#020202"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-color" style="color:#020202;margin-top:0;margin-bottom:0;font-size:16px">[course:name]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"margin":{"top":"24px","bottom":"0px"}},"color":{"text":"#020202"}}} -->
<p class="has-text-color" style="color:#020202;font-size:16px;margin-top:24px;margin-bottom:0px"><strong>Teacher Name</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"color":{"text":"#020202"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-color" style="color:#020202;margin-top:0;margin-bottom:0;font-size:16px">[teacher:displayname]</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"40px"}}}} -->
<div class="wp-block-buttons" style="margin-top:40px">
<!-- wp:button {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"20px","right":"20px"}},"color":{"background":"#020202","text":"#fefefe"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background" href="[manage:course]" style="background-color:#020202;color:#fefefe;padding-top:16px;padding-right:20px;padding-bottom:16px;padding-left:20px">Manage Course</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

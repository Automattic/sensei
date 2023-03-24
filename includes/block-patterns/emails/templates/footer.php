<?php
/**
 * Email footer content.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"0px","bottom":"40px","left":"52px","right":"52px"},"blockGap":"0px"}},"backgroundColor":"white","className":"email-notification__footer","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide email-notification__footer has-white-background-color has-background"
	style="padding-top:0px;padding-right:52px;padding-bottom:40px;padding-left:52px">
	<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","bottom":"0px"},"blockGap":"0"}},"className":"footer_info","layout":{"type":"default"}} -->
	<div class="wp-block-group footer_info"
		style="margin-top:0px;margin-bottom:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px">
		<!-- wp:image {"id":1088,"width":32,"height":32,"sizeSlug":"full","linkDestination":"none","style":{"border":{"width":"0px","style":"none"}},"className":"sensei-logo inline-block"} -->
		<figure class="wp-block-image size-full is-resized has-custom-border sensei-logo inline-block">
			<img src="<?php echo esc_url( Sensei()->assets->get_image( 'sensei-circle-logo.png' ) ); ?>" alt="Sensei logo"
				class="wp-image-1088" style="border-style:none;border-width:0px" width="32"
				height="32" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:paragraph {"align":"left","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"7px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"color":{"text":"#101517"},"typography":{"fontSize":"14px"}},"className":"inline-block powered"} -->
		<p class="has-text-align-left inline-block powered has-text-color"
			style="color:#101517;margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:7px;font-size:14px">
				<?php
				printf(
					wp_kses(
						// translators: %s is http://senseilms.com.
						__( 'Powered by <a href="%s" target="_blank" rel="noopener noreferrer">SenseiLMS.com</a>', 'sensei-lms' ),
						array(
							'a' => array(
								'href'   => true,
								'target' => true,
								'rel'    => true,
							),
						)
					),
					'https://senseilms.com'
				);
				?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<?php
/**
 * Video Lesson pattern.
 *
 * @package sensei-lms
 */

return [
	'title'      => __( 'Video Lesson', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => '<!-- wp:video -->
					<figure class="wp-block-video"></figure>
					<!-- /wp:video -->

					<!-- wp:paragraph {"placeholder":"' . esc_html__( 'Include a transcript, link to a transcript, or a summary of the video.', 'sensei-lms' ) . '"} -->
					<p></p>
					<!-- /wp:paragraph -->',
];

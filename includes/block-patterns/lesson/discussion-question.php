<?php
/**
 * Discussion Question pattern.
 *
 * @package sensei-lms
 */

return [
	'title'      => __( 'Discussion Question', 'sensei-lms' ),
	'categories' => [ \Sensei_Block_Patterns::get_patterns_category_name() ],
	'blockTypes' => [ \Sensei_Block_Patterns::get_post_content_block_type_name() ],
	'content'    => '<!-- wp:group {"backgroundColor":"tertiary"} -->
					<div class="wp-block-group has-tertiary-background-color has-background"><!-- wp:paragraph {"placeholder":"' . esc_html__( 'Write your discussion question topic or prompt here.', 'sensei-lms' ) . '"} -->
					<p></p>
					<!-- /wp:paragraph --></div>
					<!-- /wp:group -->

					<!-- wp:comments-query-loop -->
					<div class="wp-block-comments-query-loop"><!-- wp:post-comments-form /-->

					<!-- wp:comment-template -->
					<!-- wp:columns -->
					<div class="wp-block-columns"><!-- wp:column {"width":"40px"} -->
					<div class="wp-block-column" style="flex-basis:40px"><!-- wp:avatar {"size":40,"style":{"border":{"radius":"20px"}}} /--></div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column"><!-- wp:comment-author-name /-->

					<!-- wp:group {"style":{"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"layout":{"type":"flex"}} -->
					<div class="wp-block-group" style="margin-top:0px;margin-bottom:0px"><!-- wp:comment-date /-->

					<!-- wp:comment-edit-link /--></div>
					<!-- /wp:group -->

					<!-- wp:comment-content /-->

					<!-- wp:comment-reply-link /--></div>
					<!-- /wp:column --></div>
					<!-- /wp:columns -->
					<!-- /wp:comment-template -->

					<!-- wp:comments-pagination -->
					<!-- wp:comments-pagination-previous /-->

					<!-- wp:comments-pagination-numbers /-->

					<!-- wp:comments-pagination-next /-->
					<!-- /wp:comments-pagination --></div>
					<!-- /wp:comments-query-loop -->',
];

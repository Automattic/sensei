<?php
/**
 * Sensei Course List Block Patterns.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Course List Block Patterns class.
 */
class Sensei_Course_List_Block_Patterns {

	/**
	 * Register the patterns for the course list blocks.
	 *
	 * @access private
	 */
	public function register_course_list_block_patterns() {
		$pagination =
			'<!-- wp:query-pagination {"paginationArrow":"arrow","align":"wide","layout":{"type":"flex","justifyContent":"space-between"}} -->
				<!-- wp:query-pagination-previous {"fontSize":"small"} /-->

				<!-- wp:query-pagination-numbers /-->

				<!-- wp:query-pagination-next {"fontSize":"small"} /-->
			<!-- /wp:query-pagination -->';

		// Get extra links for Course List patterns.
		$patterns_with_extra_links = [ 'course-list', 'course-grid' ];
		$course_list_extra_links   = [];
		foreach ( $patterns_with_extra_links as $pattern ) {

			/**
			 * Filter to add extra links to a Course List pattern. The added
			 * links must be a valid rendered block.
			 *
			 * @since 4.10.0
			 *
			 * @param {array}  $course_list_extra_links The extra links.
			 * @param {string} $pattern                 The pattern name.
			 *
			 * @return {array} The extra links.
			 */
			$course_list_extra_links[ $pattern ] = apply_filters( 'sensei_course_list_block_patterns_extra_links', [], $pattern );
		}

		$patterns = [
			'course-list'                 =>
			[
				'title'       => __( 'Courses displayed in a list', 'sensei-lms' ),
				'categories'  => array( 'query' ),
				'blockTypes'  => array( 'core/query' ),
				'description' => 'course-list-element',
				'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","categoryIds":[],"tagIds":[],"order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":6},"displayLayout":{"type":"list"},"layout":{"inherit":true}} -->
					<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view">

						<!-- wp:sensei-lms/course-list-filter {"align":"left","types":["student_course","categories"],"lock":{"move": true}} /-->

						<!-- wp:post-template -->

							<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"10px","right":"10px","bottom":"10px","left":"10px"}},"border":{"width":"1px","color":"#c7c3c34f"}},"layout":{"inherit":false}} -->
								<div class="wp-block-group alignfull has-border-color" style="border-color:#c7c3c34f;border-width:1px;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">

									<!-- wp:post-featured-image {"isLink":true, "height":"324px"} /-->
									<!-- wp:columns -->
										<div class="wp-block-columns">

											<!-- wp:column {"width":"66.66%"} -->
												<div class="wp-block-column" style="flex-basis:66.66%">
													<!-- wp:sensei-lms/course-categories -->
														<div class="wp-block-sensei-lms-course-categories"></div>
													<!-- /wp:sensei-lms/course-categories -->

													<!-- wp:post-title {"textAlign":"left","isLink":true} /-->

													<!-- wp:post-author {"textAlign":"left"} /-->

													<!-- wp:post-excerpt {"textAlign":"left"} /-->

													<!-- wp:sensei-lms/course-overview /-->

													' . implode( "\n", $course_list_extra_links['course-list'] ) . '

													<!-- wp:sensei-lms/course-progress /-->
												</div>
											<!-- /wp:column -->

											<!-- wp:column {"width":"33.33%"} -->
												<div class="wp-block-column" style="flex-basis:33.33%">
													<!-- wp:sensei-lms/course-actions -->
														<!-- wp:sensei-lms/button-take-course {"align":"right"} -->
															<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><button class="wp-block-button__link">' . esc_html__( 'Start Course', 'sensei-lms' ) . '</button></div>
														<!-- /wp:sensei-lms/button-take-course -->

														<!-- wp:sensei-lms/button-continue-course {"align":"right"} -->
															<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">' . esc_html__( 'Continue', 'sensei-lms' ) . '</a></div>
														<!-- /wp:sensei-lms/button-continue-course -->

														<!-- wp:sensei-lms/button-view-results {"align":"right","className":"is-style-default"} -->
															<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">' . esc_html__( 'Visit Results', 'sensei-lms' ) . '</a></div>
														<!-- /wp:sensei-lms/button-view-results -->
													<!-- /wp:sensei-lms/course-actions -->
												</div>
											<!-- /wp:column -->
										</div>
									<!-- /wp:columns -->

								</div>
							<!-- /wp:group -->
						<!-- /wp:post-template -->' .
					$pagination .
					'</div>
				<!-- /wp:query -->',
			],
			'course-grid'                 =>
				[
					'title'       => __( 'Courses displayed in a grid', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":12},"displayLayout":{"type":"flex","columns":3},"align":"wide","layout":{"inherit":true}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-grid-view alignwide">

							<!-- wp:sensei-lms/course-list-filter {"align":"left","types":["student_course","categories"],"lock":{"move": true}} /-->

							<!-- wp:post-template {"align":"wide"} -->

								<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"10px","right":"10px","bottom":"10px","left":"10px"},"blockGap":"2px"},"border":{"width":"1px","color":"#c7c3c34f"}},"layout":{"inherit":false}} -->
									<div class="wp-block-group alignfull has-border-color" style="border-color:#c7c3c34f;border-width:1px;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">

										<!-- wp:post-featured-image {"isLink":true,"height":"180px","lock":{"move":true}} /-->

										<!-- wp:sensei-lms/course-categories -->
											<div class="wp-block-sensei-lms-course-categories"></div>
										<!-- /wp:sensei-lms/course-categories -->

										<!-- wp:post-title {"textAlign":"left","isLink":true,"lock":{"move": true},"style":{"typography":{"fontSize":"36px"}}} /-->

										<!-- wp:post-author {"textAlign":"left","lock":{"move": true}} /-->

										<!-- wp:post-excerpt {"textAlign":"left","lock":{"move": true}} /-->

										<!-- wp:sensei-lms/course-overview /-->

										' . implode( "\n", $course_list_extra_links['course-grid'] ) . '

										<!-- wp:sensei-lms/course-progress {"lock":{"move": true}} /-->

										<!-- wp:sensei-lms/course-actions {"lock":{"move": true}} -->
											<!-- wp:sensei-lms/button-take-course {"align":"full"} -->
												<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full">
													<button class="wp-block-button__link">' . esc_html__( 'Start Course', 'sensei-lms' ) . '</button>
												</div>
											<!-- /wp:sensei-lms/button-take-course -->

											<!-- wp:sensei-lms/button-continue-course {"align":"full"} -->
												<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full">
													<a class="wp-block-button__link">' . esc_html__( 'Continue', 'sensei-lms' ) . '</a>
												</div>
											<!-- /wp:sensei-lms/button-continue-course -->

											<!-- wp:sensei-lms/button-view-results {"align":"full","className":"is-style-default"} -->
												<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full">
													<a class="wp-block-button__link">' . esc_html__( 'Visit Results', 'sensei-lms' ) . '</a>
												</div>
											<!-- /wp:sensei-lms/button-view-results -->
										<!-- /wp:sensei-lms/course-actions -->

									</div>
								<!-- /wp:group -->

							<!-- /wp:post-template -->' .
						$pagination .
						'</div>
					<!-- /wp:query -->',
				],
			'course-grid-with-background' =>
				[
					'title'      => __( 'Course List - Grid', 'sensei-lms' ),
					'categories' => array( 'sensei-lms' ),
					'content'    => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"5rem","right":"20px","left":"20px","bottom":"100px"},"blockGap":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"primary","textColor":"background","className":"sensei-course-theme-course-list-pattern","layout":{"type":"constrained","contentSize":""}} -->
						<div class="wp-block-group alignfull sensei-course-theme-course-list-pattern has-background-color has-primary-background-color has-text-color has-background has-link-color" style="padding-top:5rem;padding-right:20px;padding-bottom:100px;padding-left:20px"><!-- wp:group {"style":{"border":{"left":{"color":"var:preset|color|background","width":"1px"}},"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
						<div class="wp-block-group" style="border-left-color:var(--wp--preset--color--background);border-left-width:1px;margin-bottom:40px;padding-left:20px"><!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase","lineHeight":"1"},"spacing":{"padding":{"top":"0px"}}},"textColor":"background","className":"sensei-pattern-heading","fontSize":"medium"} -->
						<h2 class="sensei-pattern-heading has-background-color has-text-color has-medium-font-size" style="padding-top:0px;line-height:1;text-transform:uppercase"><strong>' . esc_html__( 'Course list', 'sensei-lms' ) . '</strong></h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","className":"sensei-course-list-all-courses-link"} -->
						<p class="sensei-course-list-all-courses-link has-background-color has-text-color has-link-color"><a href="' . Sensei_Course::get_courses_page_url() . '" target="_blank" rel="noreferrer noopener">' . esc_html__( 'Explore all courses', 'sensei-lms' ) . '</a></p>
						<!-- /wp:paragraph --></div>
						<!-- /wp:group -->

						<!-- wp:query {"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":"3","inherit":false},"displayLayout":{"type":"flex","columns":3},"className":"wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list\u002d\u002dis-grid-view","layout":{"type":"default"}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-grid-view"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px"},"border":{"width":"0px","style":"none"}},"layout":{"inherit":false}} -->
						<div class="wp-block-group alignfull" style="border-style:none;border-width:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:post-featured-image {"isLink":true,"height":"200px","lock":{"move":false,"remove":false},"align":"full","style":{"border":{"width":"1px"}},"borderColor":"background"} /-->

						<!-- wp:sensei-lms/course-categories {"options":{"backgroundColor":"#F1EDE7","textColor":"#00594F"},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
						<div style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;--sensei-lms-course-categories-background-color:#F1EDE7;--sensei-lms-course-categories-text-color:#00594F" class="wp-block-sensei-lms-course-categories"></div>
						<!-- /wp:sensei-lms/course-categories -->

						<!-- wp:post-title {"textAlign":"left","isLink":true,"lock":{"move":false,"remove":false},"align":"full","style":{"typography":{"textTransform":"uppercase","lineHeight":"1"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"className":"sensei-course-list-title-no-underline"} /-->

						<!-- wp:post-author {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

						<!-- wp:post-excerpt {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"22px","right":"0px","bottom":"17px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"className":"sensei-post-excerpt-no-margin"} /-->

						<!-- wp:sensei-lms/course-actions {"lock":{"move":false,"remove":false}} -->
						<!-- wp:sensei-lms/button-take-course {"align":"full","borderRadius":8,"backgroundColor":"background","textColor":"primary"} -->
						<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px">' . esc_html__( 'Start', 'sensei-lms' ) . '</button></div>
						<!-- /wp:sensei-lms/button-take-course -->

						<!-- wp:sensei-lms/button-continue-course {"align":"full","borderRadius":8,"backgroundColor":"background","textColor":"primary"} -->
						<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px">' . esc_html__( 'Continue', 'sensei-lms' ) . '</a></div>
						<!-- /wp:sensei-lms/button-continue-course -->

						<!-- wp:sensei-lms/button-view-results {"align":"full","borderRadius":8,"className":"is-style-default","backgroundColor":"background","textColor":"primary"} -->
						<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px">' . esc_html__( 'Results', 'sensei-lms' ) . '</a></div>
						<!-- /wp:sensei-lms/button-view-results -->
						<!-- /wp:sensei-lms/course-actions --></div>
						<!-- /wp:group -->
						<!-- /wp:post-template --></div>
						<!-- /wp:query --></div>
						<!-- /wp:group -->',
				],
			'course-list-with-background' =>
				[
					'title'      => __( 'Course List', 'sensei-lms' ),
					'categories' => array( 'sensei-lms' ),
					'content'    => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"5rem","right":"20px","left":"20px","bottom":"100px"},"blockGap":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"primary","textColor":"background","className":"sensei-course-theme-course-list-pattern","layout":{"type":"constrained","contentSize":""}} -->
						<div class="wp-block-group alignfull sensei-course-theme-course-list-pattern has-background-color has-primary-background-color has-text-color has-background has-link-color" style="padding-top:5rem;padding-right:20px;padding-bottom:100px;padding-left:20px"><!-- wp:group {"style":{"border":{"left":{"color":"var:preset|color|background","width":"1px"}},"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
						<div class="wp-block-group" style="border-left-color:var(--wp--preset--color--background);border-left-width:1px;margin-bottom:40px;padding-left:20px"><!-- wp:heading {"level":2,"style":{"typography":{"textTransform":"uppercase","lineHeight":"1"},"spacing":{"padding":{"top":"0px"}}},"textColor":"background","className":"sensei-pattern-heading","fontSize":"medium"} -->
						<h2 class="sensei-pattern-heading has-background-color has-text-color has-medium-font-size" style="padding-top:0px;line-height:1;text-transform:uppercase"><strong>' . esc_html__( 'Course list', 'sensei-lms' ) . '</strong></h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","className":"sensei-course-list-all-courses-link"} -->
						<p class="sensei-course-list-all-courses-link has-background-color has-text-color has-link-color"><a href="' . Sensei_Course::get_courses_page_url() . '" target="_blank" rel="noreferrer noopener">' . esc_html__( 'Explore all courses', 'sensei-lms' ) . '</a></p>
						<!-- /wp:paragraph --></div>
						<!-- /wp:group -->

						<!-- wp:query {"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":"2","inherit":false},"style":{"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"textColor":"background","className":"wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list\u002d\u002dis-list-view","layout":{"type":"default"}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view has-background-color has-text-color has-link-color"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"20px","left":"20px"}}}} -->
						<div class="wp-block-columns"><!-- wp:column {"width":"32%"} -->
						<div class="wp-block-column" style="flex-basis:32%"><!-- wp:post-featured-image {"isLink":true,"height":"380px","lock":{"move":false,"remove":false},"align":"full","style":{"border":{"width":"1px"}},"borderColor":"background"} /--></div>
						<!-- /wp:column -->

						<!-- wp:column {"width":"50%","style":{"spacing":{"blockGap":"20px","padding":{"top":"20px","bottom":"20px"}}},"layout":{"type":"default"}} -->
						<div class="wp-block-column" style="padding-top:20px;padding-bottom:20px;flex-basis:50%"><!-- wp:sensei-lms/course-categories {"options":{"backgroundColor":"#F1EDE7","textColor":"#00594F"},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
						<div style="margin-top:0px;margin-right:0px;margin-bottom:0px;margin-left:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;--sensei-lms-course-categories-background-color:#F1EDE7;--sensei-lms-course-categories-text-color:#00594F" class="wp-block-sensei-lms-course-categories"></div>
						<!-- /wp:sensei-lms/course-categories -->

						<!-- wp:post-title {"textAlign":"left","isLink":true,"lock":{"move":false,"remove":false},"align":"full","style":{"typography":{"textTransform":"uppercase","lineHeight":"1"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"11px","right":"0px","bottom":"20px","left":"0px"}}},"textColor":"primary","className":"sensei-course-list-title-no-underline"} /-->

						<!-- wp:post-author {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"textColor":"background"} /-->

						<!-- wp:post-excerpt {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"40px","right":"0px","left":"0px","bottom":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"textColor":"background","className":"sensei-post-excerpt-no-margin"} /--></div>
						<!-- /wp:column -->

						<!-- wp:column {"width":"15%"} -->
						<div class="wp-block-column" style="flex-basis:15%"><!-- wp:sensei-lms/course-actions {"lock":{"move":false,"remove":false}} -->
						<!-- wp:sensei-lms/button-take-course {"align":"full","borderRadius":8,"buttonClassName":[],"backgroundColor":"background","textColor":"primary"} -->
						<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px">' . esc_html__( 'Start', 'sensei-lms' ) . '</button></div>
						<!-- /wp:sensei-lms/button-take-course -->

						<!-- wp:sensei-lms/button-continue-course {"align":"full","borderRadius":8,"buttonClassName":[],"backgroundColor":"background","textColor":"primary"} -->
						<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px">' . esc_html__( 'Continue', 'sensei-lms' ) . '</a></div>
						<!-- /wp:sensei-lms/button-continue-course -->

						<!-- wp:sensei-lms/button-view-results {"align":"full","borderRadius":8,"buttonClassName":[],"className":"is-style-default","backgroundColor":"background","textColor":"primary"} -->
						<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-primary-color has-background-background-color has-text-color has-background" style="border-radius:8px">' . esc_html__( 'Results', 'sensei-lms' ) . '</a></div>
						<!-- /wp:sensei-lms/button-view-results -->
						<!-- /wp:sensei-lms/course-actions --></div>
						<!-- /wp:column --></div>
						<!-- /wp:columns -->
						<!-- /wp:post-template --></div>
						<!-- /wp:query --></div>
						<!-- /wp:group -->',
				],
		];

		foreach ( $patterns as $key => $pattern ) {
			register_block_pattern(
				$key,
				$pattern
			);
		}
	}
}

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
			 * @hook sensei_course_list_block_patterns_extra_links
			 *
			 * @param {array}  $course_list_extra_links The extra links.
			 * @param {string} $pattern                 The pattern name.
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
					'title'      => __( 'Course List', 'sensei-lms' ),
					'categories' => array( 'sensei-lms' ),
					'content'    => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","right":"20px","left":"20px","bottom":"100px"},"blockGap":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|background"}}}},"backgroundColor":"foreground","textColor":"background","className":"sensei-course-theme-course-list-pattern","layout":{"type":"constrained","contentSize":"1000px"}} -->
						<div class="wp-block-group alignfull sensei-course-theme-course-list-pattern has-background-color has-foreground-background-color has-text-color has-background has-link-color" style="padding-top:80px;padding-right:20px;padding-bottom:100px;padding-left:20px"><!-- wp:group {"style":{"border":{"left":{"width":"1px","style":"solid"},"top":{},"right":{},"bottom":{}},"spacing":{"padding":{"left":"20px"},"margin":{"bottom":"40px"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
						<div class="wp-block-group" style="border-left-style:solid;border-left-width:1px;margin-bottom:40px;padding-left:20px"><!-- wp:heading {"style":{"typography":{"textTransform":"uppercase","fontStyle":"normal","fontWeight":"700"},"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"medium"} -->
						<h2 class="wp-block-heading has-medium-font-size" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-style:normal;font-weight:700;text-transform:uppercase">' . esc_html__( 'Course List', 'sensei-lms' ) . '</h2>
						<!-- /wp:heading -->

						<!-- wp:paragraph {"style":"className":"sensei-course-list-all-courses-link","fontSize":"small","fontFamily":"system"} -->
						<p class="sensei-course-list-all-courses-link has-link-color has-system-font-family has-small-font-size"><a href="' . Sensei_Course::get_courses_page_url() . '" target="_blank" rel="noreferrer noopener">' . esc_html__( 'Explore all courses', 'sensei-lms' ) . '</a></p>
						<!-- /wp:paragraph --></div>
						<!-- /wp:group -->

						<!-- wp:query {"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":"3","inherit":false},"className":"wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list\u002d\u002dis-grid-view","layout":{"type":"default"}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-grid-view"><!-- wp:post-template {"align":"wide","layout":{"type":"grid","columnCount":3}} -->
						<!-- wp:group {"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px"},"border":{"width":"0px","style":"none"}},"layout":{"inherit":false}} -->
						<div class="wp-block-group" style="border-style:none;border-width:0px;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:post-featured-image {"isLink":true,"height":"200px","lock":{"move":false,"remove":false},"style":{"border":{"width":"1px"}},"borderColor":"background"} /-->

						<!-- wp:sensei-lms/course-categories {"options":{"backgroundColor":"#F1EDE7","textColor":"#00594F"},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
						<div style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;--sensei-lms-course-categories-background-color:#F1EDE7;--sensei-lms-course-categories-text-color:#00594F" class="wp-block-sensei-lms-course-categories"></div>
						<!-- /wp:sensei-lms/course-categories -->

						<!-- wp:post-title {"textAlign":"left","isLink":true,"lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

						<!-- wp:post-author {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"fontSize":"x-small","fontFamily":"system"} /-->

						<!-- wp:post-excerpt {"textAlign":"left","lock":{"move":false,"remove":false},"style":{"spacing":{"padding":{"top":"20px","right":"0px","bottom":"20px","left":"0px"},"margin":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} /-->

						<!-- wp:sensei-lms/course-actions {"lock":{"move":false,"remove":false}} -->
						<!-- wp:sensei-lms/button-take-course {"align":"full","backgroundColor":"background","textColor":"foreground"} -->
						<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background">' . esc_html__( 'Start', 'sensei-lms' ) . '</button></div>
						<!-- /wp:sensei-lms/button-take-course -->

						<!-- wp:sensei-lms/button-continue-course {"align":"full","backgroundColor":"background","textColor":"foreground"} -->
						<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background">' . esc_html__( 'Continue', 'sensei-lms' ) . '</a></div>
						<!-- /wp:sensei-lms/button-continue-course -->

						<!-- wp:sensei-lms/button-view-results {"align":"full","className":"is-style-default","backgroundColor":"background","textColor":"foreground"} -->
						<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link has-foreground-color has-background-background-color has-text-color has-background">' . esc_html__( 'Results', 'sensei-lms' ) . '</a></div>
						<!-- /wp:sensei-lms/button-view-results -->
						<!-- /wp:sensei-lms/course-actions --></div>
						<!-- /wp:group -->
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

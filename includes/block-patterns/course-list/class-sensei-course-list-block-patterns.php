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
			'<!-- wp:separator {"align":"wide","className":"is-style-wide"} -->
				<hr class="wp-block-separator alignwide is-style-wide"/>
			<!-- /wp:separator -->

			<!-- wp:query-pagination {"paginationArrow":"arrow","align":"wide","layout":{"type":"flex","justifyContent":"space-between"}} -->
				<!-- wp:query-pagination-previous {"fontSize":"small"} /-->

				<!-- wp:query-pagination-numbers /-->

				<!-- wp:query-pagination-next {"fontSize":"small"} /-->
			<!-- /wp:query-pagination -->';

		$patterns = [
			'course-list'             =>
			[
				'title'       => __( 'Courses displayed in a list', 'sensei-lms' ),
				'categories'  => array( 'query' ),
				'blockTypes'  => array( 'core/query' ),
				'description' => 'course-list-element',
				'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","categoryIds":[],"tagIds":[],"order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":6},"displayLayout":{"type":"list"},"layout":{"inherit":true}} -->
					<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view">
						<!-- wp:post-template -->
							<!-- wp:post-featured-image {"isLink":true,"align":"center"} /-->
							<!-- wp:columns -->
								<div class="wp-block-columns">

									<!-- wp:column {"width":"66.66%"} -->
										<div class="wp-block-column" style="flex-basis:66.66%">
											<!-- wp:sensei-lms/course-categories /-->

											<!-- wp:post-title {"textAlign":"left","isLink":true} /-->

											<!-- wp:post-author {"textAlign":"left"} /-->

											<!-- wp:post-excerpt {"textAlign":"left"} /-->

											<!-- wp:sensei-lms/course-progress /-->
										</div>
									<!-- /wp:column -->

									<!-- wp:column {"width":"33.33%"} -->
										<div class="wp-block-column" style="flex-basis:33.33%">
											<!-- wp:sensei-lms/course-actions -->
												<!-- wp:sensei-lms/button-take-course {"align":"right"} -->
													<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><button class="wp-block-button__link">Start Course</button></div>
												<!-- /wp:sensei-lms/button-take-course -->

												<!-- wp:sensei-lms/button-continue-course {"align":"right"} -->
													<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">Continue</a></div>
												<!-- /wp:sensei-lms/button-continue-course -->

												<!-- wp:sensei-lms/button-view-results {"align":"right","className":"is-style-default"} -->
													<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">Visit Results</a></div>
												<!-- /wp:sensei-lms/button-view-results -->
											<!-- /wp:sensei-lms/course-actions -->
										</div>
									<!-- /wp:column -->
								</div>
							<!-- /wp:columns -->
						<!-- /wp:post-template -->' .
					$pagination .
					'</div>
				<!-- /wp:query -->',
			],
			'course-grid'                     =>
				[
					'title'       => __( 'Courses displayed in a grid', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":12},"displayLayout":{"type":"flex","columns":3},"align":"wide","layout":{"inherit":true}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-grid-view alignwide">
							<!-- wp:post-template {"align":"wide"} -->
								<!-- wp:post-featured-image {"isLink":true,"align":"center"} /-->

								<!-- wp:sensei-lms/course-categories /-->

								<!-- wp:post-title {"textAlign":"left","isLink":true} /-->

								<!-- wp:post-author {"textAlign":"left"} /-->

								<!-- wp:post-excerpt {"textAlign":"left"} /-->

								<!-- wp:sensei-lms/course-progress /-->

								<!-- wp:sensei-lms/course-actions -->
									<!-- wp:sensei-lms/button-take-course {"align":"full"} -->
										<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full">
											<button class="wp-block-button__link">Start Course</button>
										</div>
									<!-- /wp:sensei-lms/button-take-course -->

									<!-- wp:sensei-lms/button-continue-course {"align":"full"} -->
										<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full">
											<a class="wp-block-button__link">Continue</a>
										</div>
									<!-- /wp:sensei-lms/button-continue-course -->

									<!-- wp:sensei-lms/button-view-results {"align":"full","className":"is-style-default"} -->
										<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full">
											<a class="wp-block-button__link">Visit Results</a>
										</div>
									<!-- /wp:sensei-lms/button-view-results -->
								<!-- /wp:sensei-lms/course-actions -->
							<!-- /wp:post-template -->' .
						$pagination .
						'</div>
					<!-- /wp:query -->',
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

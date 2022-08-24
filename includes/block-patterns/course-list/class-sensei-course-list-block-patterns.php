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
		$pagination = '<!-- wp:separator {"align":"wide","className":"is-style-wide"} -->
						<hr class="wp-block-separator alignwide is-style-wide"/>
						<!-- /wp:separator -->

						<!-- wp:query-pagination {"paginationArrow":"arrow","align":"wide","layout":{"type":"flex","justifyContent":"space-between"}} -->
						<!-- wp:query-pagination-previous {"fontSize":"small"} /-->

						<!-- wp:query-pagination-numbers /-->

						<!-- wp:query-pagination-next {"fontSize":"small"} /-->
						<!-- /wp:query-pagination --></div>
						<!-- /wp:query -->';

		$course_action_button = '<!-- wp:sensei-lms/course-actions -->
						<!-- wp:sensei-lms/button-take-course {"align":"full"} -->
						<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><button class="wp-block-button__link">Start Course</button></div>
						<!-- /wp:sensei-lms/button-take-course -->

						<!-- wp:sensei-lms/button-continue-course {"align":"full"} -->
						<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link">Continue</a></div>
						<!-- /wp:sensei-lms/button-continue-course -->

						<!-- wp:sensei-lms/button-view-results {"align":"full","className":"is-style-default"} -->
						<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-full"><a class="wp-block-button__link">Visit Results</a></div>
						<!-- /wp:sensei-lms/button-view-results -->
						<!-- /wp:sensei-lms/course-actions -->';

		$patterns = [
			'course-list-columns'             =>
			[
				'title'       => __( 'Grid of courses with details', 'sensei-lms' ),
				'categories'  => array( 'query' ),
				'blockTypes'  => array( 'core/query' ),
				'description' => 'course-list-element',
				'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","categoryIds":[],"tagIds":[],"order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":4},"displayLayout":{"type":"flex","columns":3},"align":"wide","layout":{"inherit":true}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list alignwide"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:post-featured-image {"isLink":true,"width":"100%","height":"318px"} /-->

						<!-- wp:sensei-lms/course-categories /-->

						<!-- wp:post-title {"level":1,"fontSize":"large","isLink":true,"className":"hide-url-underline"} /-->

						<!-- wp:post-author /-->

						<!-- wp:post-excerpt {"fontSize":"medium"} /-->

						<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /-->' . $course_action_button . '
						<!-- /wp:post-template -->' . $pagination,
			],
			'course-list-columns-title'       =>
				[
					'title'       => __( 'Grid of courses with title', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","categoryIds":[],"tagIds":[],"order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":4},"displayLayout":{"type":"flex","columns":3},"align":"wide","layout":{"inherit":true}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list alignwide"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:post-featured-image {"isLink":true,"width":"100%","height":"318px"} /-->

						<!-- wp:sensei-lms/course-categories /-->

						<!-- wp:post-title {"level":1,"fontSize":"x-large","isLink":true,"className":"hide-url-underline"} /-->' . $course_action_button . '
						<!-- /wp:post-template -->' . $pagination,
				],
			'course-list-columns-description' =>
				[
					'title'       => __( 'Grid of courses with title and description', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"offset":0,"postType":"course","categoryIds":[],"tagIds":[],"order":"desc","orderBy":"date","author":"","search":"","sticky":"","perPage":4},"displayLayout":{"type":"flex","columns":3},"align":"wide","layout":{"inherit":true}} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list alignwide"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:post-featured-image {"isLink":true,"width":"100%","height":"318px"} /-->

						<!-- wp:sensei-lms/course-categories /-->

						<!-- wp:post-title {"level":1,"fontSize":"x-large","isLink":true,"className":"hide-url-underline"} /-->

						<!-- wp:post-excerpt {"fontSize":"medium"} /-->' . $course_action_button . '
						<!-- /wp:post-template -->' . $pagination,
				],
			'course-list'                     =>
				[
					'title'       => __( 'List of courses', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list alignwide"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:columns {"verticalAlignment":null,"align":"wide","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
						<div class="wp-block-columns alignwide" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"verticalAlignment":"center","width":"30%","layout":{"inherit":false}} -->
						<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%"><!-- wp:post-featured-image {"isLink":true,"align":"center"} /--></div>
						<!-- /wp:column -->

						<!-- wp:column {"width":"50%","layout":{"inherit":false}} -->
						<div class="wp-block-column" style="flex-basis:50%">

						<!-- wp:sensei-lms/course-categories /-->

						<!-- wp:post-title {"fontSize":"x-large","isLink":true,"className":"hide-url-underline"} /-->

						<!-- wp:post-author /-->

						<!-- wp:post-excerpt /-->

						<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /--></div>
						<!-- /wp:column -->

						<!-- wp:column {"verticalAlignment":"top","width":"20%"} -->
						<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%"><!-- wp:spacer {"height":"3px"} -->
						<div style="height:3px" aria-hidden="true" class="wp-block-spacer"></div>
						<!-- /wp:spacer -->' . $course_action_button . '</div>
						<!-- /wp:column --></div>
						<!-- /wp:columns -->
						<!-- /wp:post-template -->' . $pagination,
				],
			'course-list-title'               =>
				[
					'title'       => __( 'List of courses with title', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list alignwide"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:columns {"verticalAlignment":null,"align":"wide","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
						<div class="wp-block-columns alignwide" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"verticalAlignment":"center","width":"30%","layout":{"inherit":false}} -->
						<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%"><!-- wp:post-featured-image {"isLink":true,"align":"center"} /--></div>
						<!-- /wp:column -->

						<!-- wp:column {"width":"50%","layout":{"inherit":false}} -->
						<div class="wp-block-column" style="flex-basis:50%">

						<!-- wp:sensei-lms/course-categories /-->

						<!-- wp:post-title {"fontSize":"x-large","isLink":true,"className":"hide-url-underline"} /-->

						</div>
						<!-- /wp:column -->

						<!-- wp:column {"verticalAlignment":"top","width":"20%"} -->
						<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%"><!-- wp:spacer {"height":"3px"} -->
						<div style="height:3px" aria-hidden="true" class="wp-block-spacer"></div>
						<!-- /wp:spacer -->' . $course_action_button . '</div>
						<!-- /wp:column --></div>
						<!-- /wp:columns -->
						<!-- /wp:post-template -->' . $pagination,
				],
			'course-list-description'         =>
				[
					'title'       => __( 'List of courses with title and description', 'sensei-lms' ),
					'categories'  => array( 'query' ),
					'blockTypes'  => array( 'core/query' ),
					'description' => 'course-list-element',
					'content'     => '<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"course","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide"} -->
						<div class="wp-block-query wp-block-sensei-lms-course-list alignwide"><!-- wp:post-template {"align":"wide"} -->
						<!-- wp:columns {"verticalAlignment":null,"align":"wide","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}}} -->
						<div class="wp-block-columns alignwide" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:column {"verticalAlignment":"center","width":"30%","layout":{"inherit":false}} -->
						<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%"><!-- wp:post-featured-image {"isLink":true,"align":"center"} /--></div>
						<!-- /wp:column -->

						<!-- wp:column {"width":"50%","layout":{"inherit":false}} -->
						<div class="wp-block-column" style="flex-basis:50%">

						<!-- wp:sensei-lms/course-categories /-->

						<!-- wp:post-title {"fontSize":"x-large","isLink":true,"className":"hide-url-underline"} /-->
						<!-- wp:post-excerpt /-->
						</div>
						<!-- /wp:column -->

						<!-- wp:column {"verticalAlignment":"top","width":"20%"} -->
						<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:20%"><!-- wp:spacer {"height":"3px"} -->
						<div style="height:3px" aria-hidden="true" class="wp-block-spacer"></div>
						<!-- /wp:spacer -->' . $course_action_button . '</div>
						<!-- /wp:column --></div>
						<!-- /wp:columns -->
						<!-- /wp:post-template -->' . $pagination,
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

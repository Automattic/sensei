<?php
/**
 * Create Sensei pages during setup wizard.
 *
 * @package Sensei\Setup_Wizard
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create Sensei base pages.
 *
 * @package Sensei_Setup_Wizard
 */
class Sensei_Setup_Wizard_Pages {

	/**
	 * Create a page unless one already exists with the given slug.
	 *
	 * @param mixed  $slug         Page slug.
	 * @param string $page_title   The page title.
	 * @param string $page_content The content of the page.
	 * @param int    $post_parent  Parent post ID.
	 *
	 * @return integer $page_id The ID of the created page.
	 */
	public function create_page( $slug, $page_title = '', $page_content = '', $post_parent = 0 ) {

		$page = get_page_by_path( $slug );
		if ( $page ) {
			return $page->ID;
		}

		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_parent'    => $post_parent,
			'comment_status' => 'closed',
		);

		$page_id = wp_insert_post( $page_data );

		return $page_id;

	}

	/**
	 * Create Sensei pages and update settings.
	 */
	public function create_pages() {

		// Courses page.
		$new_course_page_id = $this->create_page( esc_sql( _x( 'courses-overview', 'page_slug', 'sensei-lms' ) ), __( 'Courses', 'sensei-lms' ), $this->get_courses_page_template() );
		Sensei()->settings->set( 'course_page', $new_course_page_id );

		// My Courses page.
		$new_my_course_page_id = $this->create_page( esc_sql( _x( 'my-courses', 'page_slug', 'sensei-lms' ) ), __( 'My Courses', 'sensei-lms' ), $this->get_learner_courses_page_template() );
		Sensei()->settings->set( 'my_course_page', $new_my_course_page_id );

		// Course Completion Page.
		$new_course_completed_page_id = $this->create_page( esc_sql( _x( 'course-completed', 'page_slug', 'sensei-lms' ) ), __( 'Course Completed', 'sensei-lms' ), $this->get_course_completed_page_template() );
		Sensei()->settings->set( 'course_completed_page', $new_course_completed_page_id );

		Sensei()->initiate_rewrite_rules_flush();
	}

	/**
	 * Get the template for learner courses page.
	 *
	 * @return string
	 */
	private function get_learner_courses_page_template() {
		$blocks = serialize_blocks(
			/**
			 * Filter the learner courses page template when auto-creating it
			 * through setup wizard.
			 *
			 * @hook  sensei_learner_courses_page_template
			 * @since 3.13.1
			 *
			 * @param {array} $blocks Blocks array.
			 *
			 * @return {array} Blocks array.
			 */
			apply_filters(
				'sensei_learner_courses_page_template',
				[
					[
						'blockName'    => 'sensei-lms/button-learner-messages',
						'innerContent' => [],
						'attrs'        => [],
					],
					[
						'blockName'    => 'core/query',
						'innerContent' => [
							'<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:0"><!-- wp:sensei-lms/course-list-filter {"types":["student_course"],"defaultOptions":{"student_course":"active"},"lock":{"move":true}} /--></div>
<!-- /wp:group -->

<!-- wp:post-template {"align":"center"} -->
<!-- wp:group {"align":"center","style":{"spacing":{"padding":{"top":"10px","right":"10px","bottom":"10px","left":"10px"}},"border":{"width":"1px","color":"#c7c3c34f"}},"className":"aligncenter","layout":{"inherit":false}} -->
<div class="wp-block-group aligncenter has-border-color" style="border-color:#c7c3c34f;border-width:1px;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><!-- wp:post-featured-image {"isLink":true,"height":"324px","align":"center"} /-->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:sensei-lms/course-categories {"textAlign":"left","options":{},"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|20"}}}} -->
<div style="padding-bottom:var(--wp--preset--spacing--20)" class="wp-block-sensei-lms-course-categories has-text-align-left"></div>
<!-- /wp:sensei-lms/course-categories -->

<!-- wp:post-title {"textAlign":"left","isLink":true,"style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}}} /-->

<!-- wp:post-author {"textAlign":"left"} /-->

<!-- wp:post-excerpt {"textAlign":"left"} /-->

<!-- wp:sensei-lms/course-overview /-->

<!-- wp:sensei-lms/course-progress {"defaultBarColor":"primary"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","style":{"spacing":{"padding":{"top":"0"}}}} -->
<div class="wp-block-column" style="padding-top:0;flex-basis:33.33%"><!-- wp:sensei-lms/course-actions -->
<!-- wp:sensei-lms/button-take-course {"align":"right"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><button class="wp-block-button__link">' . __( 'Start Course', 'sensei-lms' ) . '</button></div>
<!-- /wp:sensei-lms/button-take-course -->

<!-- wp:sensei-lms/button-continue-course {"align":"right"} -->
<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">' . __( 'Continue', 'sensei-lms' ) . '</a></div>
<!-- /wp:sensei-lms/button-continue-course -->

<!-- wp:sensei-lms/button-view-results {"align":"right","className":"is-style-default"} -->
<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">' . __( 'Visit Results', 'sensei-lms' ) . '</a></div>
<!-- /wp:sensei-lms/button-view-results -->
<!-- /wp:sensei-lms/course-actions --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination {"paginationArrow":"arrow","align":"center","layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous {"fontSize":"small"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"fontSize":"small"} /-->
<!-- /wp:query-pagination --></div>',
						],
						'attrs'        => [
							'queryId'       => 0,
							'query'         => [
								'postType' => 'course',
								'perPage'  => 10,
								'offset'   => 0,
								'inherit'  => false,
								'sticky'   => '',
								'pages'    => 0,
								'order'    => 'desc',
								'orderBy'  => 'date',
								'author'   => '',
								'search'   => '',
								'exclude'  => [],
							],
							'displayLayout' => [
								'type' => 'list',
							],
							'className'     => 'wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view',
						],
					],
				]
			)
		);

		return $blocks;
	}

	/**
	 * Get the template for course archive page.
	 *
	 * @return string
	 */
	private function get_courses_page_template() {
		$blocks = serialize_blocks(
			/**
			 * Filter the courses page template when auto-creating it
			 * through setup wizard.
			 *
			 * @hook  sensei_courses_page_template
			 * @since 4.11.0
			 *
			 * @param {array} $blocks Blocks array.
			 *
			 * @return {array} Blocks array.
			 */
			apply_filters(
				'sensei_course_archive_page_template',
				[
					[
						'blockName'    => 'core/query',
						'innerContent' => [
							'<div class="wp-block-query wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view">
<!-- wp:sensei-lms/course-list-filter {"types":["featured"],"lock":{"move":true}} /-->
<!-- wp:post-template {"align":"center"} -->
<!-- wp:group {"align":"center","style":{"spacing":{"padding":{"top":"10px","right":"10px","bottom":"10px","left":"10px"}},"border":{"width":"1px","color":"#c7c3c34f"}},"className":"aligncenter","layout":{"inherit":false}} -->
<div class="wp-block-group aligncenter has-border-color" style="border-color:#c7c3c34f;border-width:1px;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px"><!-- wp:post-featured-image {"isLink":true,"height":"324px","align":"center"} /-->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:sensei-lms/course-categories {"textAlign":"left","options":{},"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|20"}}}} -->
<div style="padding-bottom:var(--wp--preset--spacing--20)" class="wp-block-sensei-lms-course-categories has-text-align-left"></div>
<!-- /wp:sensei-lms/course-categories -->

<!-- wp:post-title {"textAlign":"left","isLink":true,"style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}}} /-->

<!-- wp:post-author {"textAlign":"left"} /-->

<!-- wp:post-excerpt {"textAlign":"left"} /-->

<!-- wp:sensei-lms/course-overview {"className":"has-text-align-left"} /-->

<!-- wp:sensei-lms/course-progress {"defaultBarColor":"foreground","className":"has-text-align-left"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%","style":{"spacing":{"padding":{"top":"0"}}}} -->
<div class="wp-block-column" style="padding-top:0;flex-basis:33.33%"><!-- wp:sensei-lms/course-actions -->
<!-- wp:sensei-lms/button-take-course {"align":"right"} -->
<div class="wp-block-sensei-lms-button-take-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><button class="wp-block-button__link">' . __( 'Start Course', 'sensei-lms' ) . '</button></div>
<!-- /wp:sensei-lms/button-take-course -->

<!-- wp:sensei-lms/button-continue-course {"align":"right"} -->
<div class="wp-block-sensei-lms-button-continue-course is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">' . __( 'Continue', 'sensei-lms' ) . '</a></div>
<!-- /wp:sensei-lms/button-continue-course -->

<!-- wp:sensei-lms/button-view-results {"align":"right","className":"is-style-default"} -->
<div class="wp-block-sensei-lms-button-view-results is-style-default wp-block-sensei-button wp-block-button has-text-align-right"><a class="wp-block-button__link">' . __( 'Visit Results', 'sensei-lms' ) . '</a></div>
<!-- /wp:sensei-lms/button-view-results -->
<!-- /wp:sensei-lms/course-actions --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination {"paginationArrow":"arrow","align":"center","layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous {"fontSize":"small"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"fontSize":"small"} /-->
<!-- /wp:query-pagination --></div>',
						],
						'attrs'        => [
							'queryId'       => 0,
							'query'         => [
								'postType' => 'course',
								'offset'   => 0,
								'inherit'  => true,
							],
							'displayLayout' => [
								'type' => 'list',
							],
							'className'     => 'wp-block-sensei-lms-course-list wp-block-sensei-lms-course-list--is-list-view',
						],
					],
				]
			)
		);

		return $blocks;
	}

	/**
	 * Get the template for course completed page.
	 *
	 * @return string
	 */
	private function get_course_completed_page_template() {
		$blocks = serialize_blocks(
			/**
			 * Filter the course completed page template when auto-creating it
			 * through setup wizard.
			 *
			 * @hook  sensei_course_completed_page_template
			 * @since 3.13.1
			 *
			 * @param {array} $blocks Blocks array.
			 *
			 * @return {array} Blocks array.
			 */
			apply_filters(
				'sensei_course_completed_page_template',
				[
					[
						'blockName'    => 'core/paragraph',
						'innerContent' => [ '<p class="has-text-align-center has-large-font-size">' . __( 'Congratulations on completing this course! 🥳', 'sensei-lms' ) . '</p>' ],
						'attrs'        => [
							'align'    => 'center',
							'fontSize' => 'large',
						],
					],
					[
						'blockName'    => 'core/buttons',
						'innerContent' => [ '<div class="wp-block-buttons is-content-justification-center" id="course-completed-actions">', null, '</div>' ],
						'attrs'        => [
							'contentJustification' => 'center',
							'anchor'               => 'course-completed-actions',
						],
						'innerBlocks'  => [
							[
								'blockName'    => 'core/button',
								'innerContent' => [ '<div class="wp-block-button more-courses"><a class="wp-block-button__link">' . __( 'Find More Courses', 'sensei-lms' ) . '</a></div>' ],
								'attrs'        => [
									'className' => 'more-courses',
								],
							],
						],
					],
					[
						'blockName'    => 'sensei-lms/course-results',
						'innerContent' => [],
						'attrs'        => [],
					],
				]
			)
		);

		return $blocks;
	}

}

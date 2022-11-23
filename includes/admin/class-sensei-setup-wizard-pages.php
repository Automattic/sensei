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
		$new_course_page_id = $this->create_page( esc_sql( _x( 'courses-overview', 'page_slug', 'sensei-lms' ) ), __( 'Courses', 'sensei-lms' ), '' );
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
						'blockName'    => 'sensei-lms/learner-courses',
						'innerContent' => [],
						'attrs'        => [],
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

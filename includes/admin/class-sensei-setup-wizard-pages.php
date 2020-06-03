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
	 * Create Courses and My Courses pages.
	 * Updates Sensei settings Course page nad My Courses options.
	 *
	 * @return void
	 */
	public function create_pages() {

		// Courses page.
		$new_course_page_id = $this->create_page( esc_sql( _x( 'courses-overview', 'page_slug', 'sensei-lms' ) ), __( 'Courses', 'sensei-lms' ), '' );
		Sensei()->settings->set( 'course_page', $new_course_page_id );

		// My Courses page.
		$new_my_course_page_id = $this->create_page( esc_sql( _x( 'my-courses', 'page_slug', 'sensei-lms' ) ), __( 'My Courses', 'sensei-lms' ), '[sensei_user_courses]' );
		Sensei()->settings->set( 'my_course_page', $new_my_course_page_id );

		Sensei()->initiate_rewrite_rules_flush();
	}


}

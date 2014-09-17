<?php

/**
 * Clears the cache of the Course Terms
 */
function imperial_course_terms_clear_cache() {
	wp_cache_delete( 'getCourseTerms', 'imperial' );
}
add_action( "delete_course-terms", 'imperial_course_terms_clear_cache' );
add_action( "edited_course-terms", 'imperial_course_terms_clear_cache' );
add_action( "created_course-terms", 'imperial_course_terms_clear_cache' );

/**
 * Requires the P2P plugin to create Post to Post connections
 */
function imperial_courses_connection_types() {

	// Relate Courses to Multiple Programmes
	$terms = wp_cache_get( 'getCourseTerms', 'imperial' );
	if ( !is_array( $terms ) ) {
		$terms = $all_terms = array();
		$all_terms = get_terms( 'course-terms', array( 'hide_empty' => false ) );
		foreach ( $all_terms AS $term ) {
			$terms[ $term->slug ] = $term->name . '&nbsp;'; 
		}
//		asort($terms);
		wp_cache_set( 'getCourseTerms', $terms, 'imperial' ); // Cache cleared via imperial_course_terms_clear_cache()
	}
	p2p_register_connection_type( array(
		'name' => 'course_programme',
		'from' => 'course',
		'to' => 'programme',
		'cardinality' => 'many-to-many',
		'title' => array(
			'from' => __( 'Programmes', 'imperial' ),
			'to' => __( 'Courses', 'imperial' ),
		),
		'admin_box' => array(
			'show' => 'any',
			'context' => 'normal',
			'priority' => 'core',
		),
		'fields' => array(
			'term' => array( 
				'title' => 'Terms',
				'type' => 'checkbox',
				'values' => $terms,
			),
		),
		'admin_column' => 'any',
		'admin_dropdown' => 'any',
	) );

	if ( post_type_exists('forum') ) {
		// Relate a Forum to a single Course
		p2p_register_connection_type( array(
			'name' => 'course_forum',
			'from' => 'forum',
			'to' => 'course',
			'cardinality' => 'one-to-one',
			'title' => array(
				'from' => __( 'Course (optional - <small>Course or Programme, not both</small>)', 'imperial' ),
				'to' => __( 'Forum', 'imperial' ),
			),
			'from_labels' => array(
				'column_title' => __( 'Course', 'imperial' ),
			),
			'to_labels' => array(
				'column_title' => __( 'Forum', 'imperial' ),
			),
			'admin_box' => array(
				'show' => 'any',
				'context' => 'normal',
			),
			'admin_column' => 'any',
		) );
	} // 'forum' exists as post type
//* // Replaced with 'hidden' categories
	// Relate posts to Multiple Courses
	p2p_register_connection_type( array(
		'name' => 'post_course',
		'from' => 'post',
		'to' => 'course',
		'cardinality' => 'many-to-many',
		'title' => array(
			'from' => __( 'Course(s) - <small>If you are a Course Leader/Teaching Assistant: Please only select a Course (not a Programme)</small>', 'imperial' ),
			'to' => __( 'Posts', 'imperial' ),
		),
		'from_labels' => array(
			'column_title' => __( 'Courses', 'imperial' ),
		),
		'to_labels' => array(
			'column_title' => __( 'Posts', 'imperial' ),
		),
		'admin_box' => array(
			'show' => 'from',
			'context' => 'normal',
		),
		'admin_column' => 'from',
//		'admin_dropdown' => 'from',
	) );
//*/
	// Relate Pages to Multiple Courses
	p2p_register_connection_type( array(
		'name' => 'page_course',
		'from' => 'page',
		'to' => 'course',
		'cardinality' => 'many-to-many',
		'title' => array(
			'from' => __( 'Course(s) (optional - <small>only for Course specific Content, leave blank otherwise</small>)', 'imperial' ),
			'to' => __( 'Pages', 'imperial' ),
		),
		'from_labels' => array(
			'column_title' => __( 'Courses', 'imperial' ),
		),
		'to_labels' => array(
			'column_title' => __( 'Pages', 'imperial' ),
		),
		'admin_box' => array(
			'show' => 'from',
			'context' => 'side',
		),
		'admin_column' => 'from',
		'admin_dropdown' => 'from',
	) );

	// Setting Course Leader and Course Instructors, but only for Staff users
	p2p_register_connection_type( array(
		'name' => 'staff_course',
		'from' => 'user',
		'to' => 'course',
		'cardinality' => 'many-to-many',
		'title' => array(
			'from' => __( 'Staff Courses', 'imperial' ),
			'to' => __( 'Staff', 'imperial' )
		),
		'admin_box' => array(
			'show' => 'any',
			'context' => 'normal',
		),
		'fields' => array(
			'role' => array( 
				'title' => 'Role',
				'text' => 'Instructor',
				'type' => 'select',
				'values' => array( 'Course Leader' )
			),
		),
		'from_labels' => array(
			'column_title' => __( 'Staff Courses', 'imperial' ),
		),
		'admin_column' => 'any',
		'admin_dropdown' => 'from',
//		'from_query_vars' => array( 'meta_key' => 'staff', 'meta_value' => 1 ), // Limit to only Staff users
	) );

	// Allowing students on Multiple Courses
	p2p_register_connection_type( array(
		'name' => 'student_course',
		'from' => 'user',
		'to' => 'course',
		'cardinality' => 'many-to-many',
		'title' => array(
			'from' => __( 'Students Courses', 'imperial' ),
			'to' => __( 'Students', 'imperial' ),
		),
		'admin_box' => array(
			'show' => 'from',
			'context' => 'side',
		),
		'from_labels' => array(
			'column_title' => __( 'Student Courses', 'imperial' ),
		),
		'admin_column' => false,
//		'admin_dropdown' => 'from',
//		'from_query_vars' => array( 'meta_key' => 'student', 'meta_value' => 1 ), // Limit to only Student users
	) );

} // END imperial_courses_connection_types()
add_action( 'p2p_init', 'imperial_courses_connection_types', 20 );


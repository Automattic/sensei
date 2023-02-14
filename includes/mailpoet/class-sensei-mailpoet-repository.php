<?php
/**
 * File containing the class Sensei_MailPoet_Repository.
 *
 * @since $$next-version$$
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Some Helper methods to fetch Sensei data: courses, groups and students.
 */
class Sensei_MailPoet_Repository {
	/**
	 * Get all enrolled students in a course or group.
	 *
	 * @param int    $id The post ID.
	 * @param string $post_type The post type.
	 *
	 * @return array
	 */
	public static function get_students( $id, $post_type ) {
		global $wpdb;
		if ( 'group' === $post_type ) {
			if ( class_exists( 'Sensei_Pro_Student_Groups\Repositories\Group_Student_Repository', true ) ) {
				$group_student_repo = new Sensei_Pro_Student_Groups\Repositories\Group_Student_Repository( $wpdb );
				$student_ids        = $group_student_repo->find_group_students( $id );
			}
		}
		if ( 'course' === $post_type ) {
			$students    = get_comments(
				array(
					'post_id' => $id,
					'type'    => 'sensei_course_status',
					'status'  => 'any',
				)
			);
			$student_ids = array_map(
				static function( $student ) {
					return $student->user_id;
				},
				$students
			);
		}
		if ( ! $student_ids ) {
			return array();
		}

		$args = array(
			'include' => $student_ids,
			'fields'  => array( 'id', 'user_email', 'display_name', 'user_nicename' ),
		);
		return get_users( $args );
	}

	/**
	 * Generates a Sensei LMS prefixed name for a MailPoet list.
	 *
	 * @param string $name The name of the course or group.
	 *
	 * @param string $post_type The post type: course or group.
	 *
	 * @return string
	 */
	public static function get_list_name( $name, $post_type ) {
		return 'Sensei LMS ' . ucfirst( $post_type ) . ': ' . $name;
	}

	/**
	 * Get all groups and courses in Sensei.
	 */
	public static function fetch_sensei_lists() {
		$args = array(
			'post_type'        => array( 'group', 'course' ),
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'post_status'      => 'any',
			'suppress_filters' => 0,
		);

		$wp_query_obj       = new WP_Query( $args );
		return array_map(
			static function( $post ) {
				return array(
					'id'          => $post->ID,
					'post_type'   => $post->post_type,
					'name'        => $post->post_title,
					'description' => $post->post_excerpt,
				);
			},
			$wp_query_obj->posts
		);
	}

	public static function index_lists_by_key( $list, $key ) {
		$hash = array();
		foreach ( $list as $item ) {
			if ( gettype( $item ) === 'array' && array_key_exists( $key, $item ) ) {
				$hash[ $item[ $key ] ] = $item;
			} elseif ( gettype( $item ) === 'object' && property_exists( $item, $key ) ) {
				$hash[ $item->$key ] = $item;
			} else {
				$hash[ $item ] = $item;
			}
		}
		return $hash;
	}
}
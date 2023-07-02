<?php
/**
 * File containing the class Sensei_MailPoet_Repository.
 *
 * @package sensei
 */

namespace Sensei\Emails\MailPoet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Some Helper methods to fetch Sensei data: courses, groups and students.
 *
 * @since 4.13.0
 */
class Repository {
	/**
	 * Get all enrolled students in a course or group.
	 *
	 * @since 4.13.0
	 * @param int    $id The post ID.
	 * @param string $post_type The post type.
	 *
	 * @return array
	 */
	public static function get_students( $id, $post_type ) {
		global $wpdb;
		if ( 'group' === $post_type ) {
			if ( class_exists( 'Sensei_Pro_Student_Groups\Repositories\Group_Student_Repository', true ) ) {
				$group_student_repo = new \Sensei_Pro_Student_Groups\Repositories\Group_Student_Repository( $wpdb );
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

		$args  = array(
			'include' => $student_ids,
			'fields'  => array( 'ID', 'user_email', 'display_name', 'user_nicename' ),
		);
		$users = get_users( $args );
		return self::user_objects_to_array( $users );
	}

	/**
	 * Generates a Sensei LMS prefixed name for a MailPoet list.
	 *
	 * @since 4.13.0
	 * @param string $name The name of the course or group.
	 * @param string $post_type The post type: course or group.
	 *
	 * @return string
	 */
	public static function get_list_name( $name, $post_type ) {
		$singular_name = get_post_type_object( $post_type )->labels->singular_name ?? ucfirst( $post_type );
		// translators: Placeholder is the post type singular name: Course or Group. The second placeholder is the Course or Group name.
		return sprintf( __( 'Sensei LMS %1$s: %2$s', 'sensei-lms' ), $singular_name, $name );
	}

	/**
	 * Get all groups and courses in Sensei.
	 *
	 * @since 4.13.0
	 *
	 * @return array
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

		$wp_query_obj = new \WP_Query( $args );
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

	/**
	 * Convert array of user objects to array of arrays.
	 *
	 * @since 4.13.0
	 * @param array $users Array of user objects.
	 *
	 * @return array Array of user arrays.
	 */
	public static function user_objects_to_array( $users ) {
		return array_map(
			static function( $user ) {
				return array(
					'id'            => $user->ID,
					'email'         => strtolower( $user->user_email ),
					'display_name'  => $user->display_name,
					'wp_user_login' => $user->user_nicename,
				);
			},
			$users
		);
	}
}

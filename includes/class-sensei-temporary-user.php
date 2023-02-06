<?php
/**
 * Temporary User
 *
 * Handles operations related to allowing Temporary users take a course.
 *
 * @package Sensei\Frontend
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Temporary User Class.
 *
 * @author  Automattic
 *
 * @since   4.11.0
 * @package Core
 */
class Sensei_Temporary_User {

	/**
	 * Initialize the hooks for hiding temporary users and roles.
	 *
	 * @since 4.11.0
	 */
	public static function init() {
		add_filter( 'editable_roles', [ static::class, 'filter_out_temporary_user_roles' ], 11 );
		add_filter( 'views_users', [ static::class, 'filter_out_temporary_user_role_tabs' ] );

		add_filter( 'pre_user_query', [ static::class, 'filter_out_temporary_users' ], 11 );
		add_filter( 'sensei_learners_query', [ static::class, 'filter_learners_query' ] );
		add_filter( 'sensei_count_statuses_args', [ static::class, 'filter_count_statuses' ] );
		add_filter( 'sensei_check_for_activity', [ static::class, 'filter_sensei_activity' ], 10, 2 );
	}

	/**
	 * Create a user without triggering user registration hooks.
	 *
	 * @param mixed $userdata wp_insert_user options.
	 *
	 * @return int|WP_Error
	 */
	public static function create_user( $userdata ) {

		remove_all_filters( 'user_register' );

		/*
		 * Workaround for MailPoet. If its default 'WordPress Users' list is active, all users will show up as subscribers,
		 * but at least with this the temporary users will start as 'Unsubscribed' instead of 'Unconfirmed', not counting
		 * towards the subscriber count of the plan. The subscribers will disappear when the temporary users are deleted.
		 *
		 */
		$_POST['mailpoet'] = [ 'subscribe_on_register_active' => true ];

		return wp_insert_user( $userdata );
	}

	/**
	 * Deletes a user.
	 *
	 * @param int $user_id User ID to delete.
	 *
	 * @return void
	 */
	public static function delete_user( int $user_id ): void {
		if ( is_multisite() ) {
			if ( ! function_exists( 'wpmu_delete_user' ) ) {
				require_once ABSPATH . '/wp-admin/includes/ms.php';
			}
			wpmu_delete_user( $user_id );
		} else {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}
			wp_delete_user( $user_id );
		}
	}

	/**
	 * Remove guest users from user queries.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public static function filter_out_temporary_users( WP_User_Query $query ) {
		global $wpdb;

		$query->query_where = str_replace(
			'WHERE 1=1',
			"WHERE 1=1 AND {$wpdb->users}.user_login NOT LIKE '" . Sensei_Guest_User::LOGIN_PREFIX . "%'
			AND {$wpdb->users}.user_login NOT LIKE '" . Sensei_Preview_User::LOGIN_PREFIX . "%'
			",
			$query->query_where
		);

	}

	/**
	 * Remove guest users from user queries.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param string $query The user query.
	 */
	public static function filter_learners_query( string $query ) {

		return str_replace(
			'WHERE 1=1',
			"WHERE 1=1 AND u.user_login NOT LIKE '" . Sensei_Guest_User::LOGIN_PREFIX . "%'
			AND u.user_login NOT LIKE '" . Sensei_Preview_User::LOGIN_PREFIX . "%'
			",
			$query
		);
	}

	/**
	 * Make sure temporary users are not counted.
	 * When the user has an ungraded quiz, they are still counted, since they will show up in the grading list, as per self::filter_sensei_activity.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param array $args Original sensei_count_statuses arguments.
	 *
	 * @return array
	 */
	public static function filter_count_statuses( array $args ) {

		$args['query'] = ( $args['query'] ?? '' ) . " AND ( ( comment_author NOT LIKE '" . Sensei_Guest_User::LOGIN_PREFIX . "%'
			AND comment_author NOT LIKE '" . Sensei_Preview_User::LOGIN_PREFIX . "%' ) OR comment_approved = 'ungraded')
			";

		return $args;
	}

	/**
	 * Filter out temporary users from grading lists, except when the lesson needs grading.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param array $comments Sensei activity comments.
	 * @param array $args Original arguments for sensei_check_for_activity.
	 *
	 * @return array
	 */
	public static function filter_sensei_activity( $comments, $args ) {

		// Only filter when listing multiple users.
		if ( isset( $args['user_id'] ) || ! is_array( $comments ) ) {
			return $comments;
		}

		return array_filter(
			$comments,
			function( $comment ) {
				return in_array( $comment->comment_approved, [ 'ungraded' ], true ) || ! self::is_temporary_user( $comment->user_id );
			}
		);
	}

	/**
	 * Filter out Guest Student role tab from Users page in Settings.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param array $views List of tabs.
	 */
	public static function filter_out_temporary_user_role_tabs( $views ) {
		unset( $views[ Sensei_Guest_User::ROLE ] );
		unset( $views[ Sensei_Preview_User::ROLE ] );
		return $views;
	}

	/**
	 * Remove Guest Student role from showing up Settings.
	 *
	 * @since 4.11.0
	 * @access private
	 *
	 * @param array $roles List of roles.
	 */
	public static function filter_out_temporary_user_roles( $roles ) {
		unset( $roles[ Sensei_Guest_User::ROLE ] );
		unset( $roles[ Sensei_Preview_User::ROLE ] );
		return $roles;
	}

	/**
	 * Retrieve list of users without filtering out temporary users.
	 *
	 * @see get_users
	 *
	 * @param array $args Get users parameters.
	 *
	 * @return array List of users.
	 */
	public static function get_all_users( $args = array() ) {
		remove_filter( 'pre_user_query', [ static::class, 'filter_out_temporary_users' ], 11 );
		$users = get_users( $args );
		add_filter( 'pre_user_query', [ static::class, 'filter_out_temporary_users' ], 11 );

		return $users;
	}

	/**
	 * Check if a user is a temporary user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private static function is_temporary_user( $user_id ) {
		// Has guest role.
		$roles = get_userdata( $user_id )->roles ?? [];
		return in_array( Sensei_Guest_User::ROLE, $roles, true ) || in_array( Sensei_Preview_User::ROLE, $roles, true );
	}

}

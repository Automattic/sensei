<?php
/**
 * File containing the Utils utility class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shared static utilities for progress and grading services.
 *
 * @internal
 *
 * @since 4.26.0
 */
class Utils {

	/**
	 * Post statuses that Reports counts as live content: a course or lesson that
	 * exists and is accessible.
	 *
	 * @since $$next-version$$
	 *
	 * @var string[]
	 */
	public const REPORTS_POST_STATUSES = array( 'publish', 'private' );

	/**
	 * Post statuses that Grading counts as live content.
	 *
	 * @since $$next-version$$
	 *
	 * @var string[]
	 */
	public const GRADING_POST_STATUSES = array( 'publish', 'private' );

	/**
	 * Get the Reports post statuses as a quoted list for a `post_status IN ( ... )` SQL clause.
	 *
	 * @since $$next-version$$
	 *
	 * @return string Quoted, comma-separated statuses, e.g. "'publish','private'".
	 */
	public static function get_reports_post_status_sql(): string {
		return "'" . implode( "','", self::REPORTS_POST_STATUSES ) . "'";
	}

	/**
	 * Resolve progress IDs while retaining the IDs used by report rows.
	 *
	 * Use the same progress-ID filters as the repositories to locate stored progress.
	 * For example, WPML uses these filters to share progress with the original-language post.
	 * Callers query the mapped IDs and key their results by the requested IDs.
	 *
	 * @since $$next-version$$
	 *
	 * @param int[]  $post_ids Requested post IDs.
	 * @param string $type     Course or lesson progress type.
	 * @return array<int, int> Requested ID => stored progress ID.
	 */
	public static function get_progress_post_id_map( array $post_ids, string $type ): array {
		$map = array();
		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( 'course' === $type ) {
				$map[ $post_id ] = (int) apply_filters( 'sensei_course_progress_get_course_id', $post_id );
			} else {
				$map[ $post_id ] = (int) apply_filters( 'sensei_lesson_progress_get_lesson_id', $post_id );
			}
		}

		return $map;
	}

	/**
	 * Get the Grading post statuses as a quoted list for a `post_status IN ( ... )` SQL clause.
	 *
	 * @since $$next-version$$
	 *
	 * @return string Quoted, comma-separated statuses.
	 */
	public static function get_grading_post_status_sql(): string {
		return "'" . implode( "','", self::GRADING_POST_STATUSES ) . "'";
	}

	/**
	 * Build a SQL-safe quoted status list from activity arguments.
	 *
	 * @since $$next-version$$
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 * @param array $args Activity arguments containing a status key.
	 * @return string Comma-separated quoted status values.
	 */
	public static function get_statuses_sql( \wpdb $wpdb, array $args ): string {
		$raw = (array) ( $args['status'] ?? array() );
		if ( empty( $raw ) ) {
			return "'__none__'";
		}

		// Values originate from class constants or caller-provided filter args,
		// not raw user input.
		$escaped = array();
		foreach ( $raw as $status ) {
			$escaped[] = $wpdb->prepare( '%s', (string) $status );
		}

		return implode( ',', $escaped );
	}

	/**
	 * Get the site's UTC offset in '+HH:MM' / '-HH:MM' format for CONVERT_TZ.
	 *
	 * Uses a numeric offset so that MySQL timezone tables are not required.
	 *
	 * @since 4.26.0
	 *
	 * @return string UTC offset string, e.g. '+05:00' or '-05:00'.
	 */
	public static function get_utc_offset_string(): string {
		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = abs( (int) ( ( $offset - $hours ) * 60 ) );
		$sign    = $offset < 0 ? '-' : '+';

		return sprintf( '%s%02d:%02d', $sign, abs( $hours ), $minutes );
	}

	/**
	 * Build SQL clause for excluding users by login prefix.
	 *
	 * When include_statuses_override is set, excluded users are kept
	 * if their effective status matches one of the override statuses.
	 *
	 * @since 4.26.0
	 *
	 * @param \wpdb  $wpdb          WordPress database object.
	 * @param array  $args          Query arguments with 'exclude_user_login_prefixes' and optional 'include_statuses_override'.
	 * @param string $status_column SQL expression for the status column (default: 'p.status').
	 * @return string SQL clause.
	 */
	public static function build_user_exclusion_clause( \wpdb $wpdb, array $args, string $status_column = 'p.status' ): string {
		if ( empty( $args['exclude_user_login_prefixes'] ) ) {
			return '';
		}

		$excluded_user_ids = self::get_user_ids_by_login_prefixes( $wpdb, $args['exclude_user_login_prefixes'] );

		if ( empty( $excluded_user_ids ) ) {
			return '';
		}

		$id_placeholders = implode( ', ', array_fill( 0, count( $excluded_user_ids ), '%d' ) );

		if ( ! empty( $args['include_statuses_override'] ) ) {
			$status_placeholders = implode( ', ', array_fill( 0, count( $args['include_statuses_override'] ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders and column expression created dynamically.
			return $wpdb->prepare( " AND ( p.user_id NOT IN ( $id_placeholders ) OR $status_column IN ( $status_placeholders ) )", array_merge( $excluded_user_ids, $args['include_statuses_override'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders created dynamically.
		return $wpdb->prepare( " AND p.user_id NOT IN ( $id_placeholders )", $excluded_user_ids );
	}

	/**
	 * Log a database query error if one occurred.
	 *
	 * @since 4.26.0
	 *
	 * @param \wpdb  $wpdb    WordPress database object.
	 * @param string $context Description of the query for debugging.
	 */
	public static function log_query_error( \wpdb $wpdb, string $context ): void {
		if ( ! empty( $wpdb->last_error ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging for query failures.
			error_log( 'Sensei: ' . $context . ' query failed: ' . $wpdb->last_error );
		}
	}

	/**
	 * Get user IDs whose login matches any of the given prefixes.
	 *
	 * Runs as a separate query to avoid JOINing wp_users, which may
	 * be on a different database in some environments.
	 *
	 * @since 4.26.0
	 *
	 * @param \wpdb    $wpdb     WordPress database object.
	 * @param string[] $prefixes User login prefixes to match.
	 * @return int[] Matching user IDs.
	 */
	private static function get_user_ids_by_login_prefixes( \wpdb $wpdb, array $prefixes ): array {
		$prefixes = array_filter( $prefixes );
		if ( empty( $prefixes ) ) {
			return [];
		}

		$like_clauses = [];
		foreach ( $prefixes as $prefix ) {
			$escaped_prefix = $wpdb->esc_like( $prefix );
			$like_clauses[] = $wpdb->prepare( 'user_login LIKE %s', $escaped_prefix . '%' );
		}

		$where = implode( ' OR ', $like_clauses );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic WHERE built from prepared clauses. Caching handled by callers.
		$result = (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE $where" );
		self::log_query_error( $wpdb, 'User ID lookup by login prefix' );

		return array_map( 'intval', $result );
	}
}

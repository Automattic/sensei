<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei utility class for unsupported theme handling.
 *
 * Provides common functions needed for unsupported theme handlers.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Utils {

	/**
	 * Turn off pagination in the theme.
	 */
	public static function disable_theme_pagination() {
		add_filter( 'previous_post_link', '__return_false' );
		add_filter( 'next_post_link', '__return_false' );
	}

	/**
	 * Disable rendering of comments.
	 */
	public static function disable_comments() {
		add_filter( 'comments_template_query_args', array( 'Sensei_Unsupported_Theme_Handler_Utils', 'filter_comment_args_load_none' ), 100 );
		add_filter( 'comments_open', '__return_false', 100 );
		add_filter( 'get_comments_number', '__return_false', 100 );
	}

	/**
	 * If we have previously disabled comments, re-enable them.
	 */
	public static function reenable_comments() {
		remove_filter( 'comments_template_query_args', array( 'Sensei_Unsupported_Theme_Handler_Utils', 'filter_comment_args_load_none' ), 100 );
		remove_filter( 'comments_open', '__return_false', 100 );
		remove_filter( 'get_comments_number', '__return_false', 100 );
	}

	/**
	 * Filter on `comments_template_query_args` to ensure that no comments are
	 * loaded.
	 *
	 * @param array $comment_args
	 *
	 * @return array
	 */
	public static function filter_comment_args_load_none( $comment_args ) {
		$comment_args['comment__in'] = array( 0 );
		return $comment_args;
	}


}

<?php
/**
 * File containing the \Sensei\WPML\Progress_Query_Helper trait.
 *
 * @package sensei
 */

namespace Sensei\WPML;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Progress_Query_Helper
 *
 * Translates the IDs of progress queries to the original language. Meant for
 * the compatibility classes that already use the WPML_API trait.
 *
 * @since $$next-version$$
 */
trait Progress_Query_Helper {
	/**
	 * Translate the post IDs of a progress query with the given callback.
	 *
	 * Only the IDs of the given post type are translated; the rest are kept as is.
	 *
	 * @param mixed    $args         Query arguments, with an optional `post_id` and `post__in`.
	 * @param string   $post_type    Post type whose IDs to translate.
	 * @param callable $translate_id Callback translating one ID.
	 * @return mixed
	 */
	private function translate_query_post_ids( $args, $post_type, callable $translate_id ) {
		if ( ! is_array( $args ) || ! $this->get_current_language() ) {
			return $args;
		}

		if ( ! empty( $args['post_id'] ) ) {
			$args['post_id'] = $this->translate_query_post_id( $args['post_id'], $post_type, $translate_id );
		}

		if ( ! empty( $args['post__in'] ) ) {
			$post_ids = (array) $args['post__in'];
			foreach ( $post_ids as $index => $post_id ) {
				$post_ids[ $index ] = $this->translate_query_post_id( $post_id, $post_type, $translate_id );
			}
			$args['post__in'] = $post_ids;
		}

		return $args;
	}

	/**
	 * Translate one ID when it belongs to the given post type.
	 *
	 * @param int|string $post_id      Post ID.
	 * @param string     $post_type    Post type whose IDs to translate.
	 * @param callable   $translate_id Callback translating one ID.
	 * @return int
	 */
	private function translate_query_post_id( $post_id, $post_type, callable $translate_id ): int {
		$post_id = (int) $post_id;

		return get_post_type( $post_id ) === $post_type ? (int) $translate_id( $post_id ) : $post_id;
	}
}

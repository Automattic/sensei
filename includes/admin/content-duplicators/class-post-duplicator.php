<?php
/**
 * File containing the Post_Duplicator class.
 *
 * @package sensei
 */

namespace Sensei\Admin\Content_Duplicators;

use WP_Post;

/**
 * Class Post_Publicator
 *
 * @since 4.21.0
 */
class Post_Duplicator {
	/**
	 * Duplicate post.
	 *
	 * @param  WP_Post     $post          Post to be duplicated.
	 * @param  string|null $suffix        Suffix for duplicated post title. Default: null.
	 * @param  boolean     $ignore_course Ignore lesson course when dulicating. Default: false.
	 * @return WP_Post|null Duplicate post object.
	 */
	public function duplicate( WP_Post $post, ?string $suffix = null, bool $ignore_course = false ): ?WP_Post {
		$new_post = array();

		$fields = array( 'ID', 'post_status', 'post_date', 'post_date_gmt', 'post_name', 'post_modified', 'post_modified_gmt', 'guid', 'comment_count' );
		foreach ( $post as $field => $value ) {
			if ( ! in_array( $field, $fields, true ) ) {
				$new_post[ $field ] = $value;
			}
		}

		$new_post['post_title']       .= $suffix;
		$new_post['post_date']         = current_time( 'mysql' );
		$new_post['post_date_gmt']     = get_gmt_from_date( $new_post['post_date'] );
		$new_post['post_modified']     = $new_post['post_date'];
		$new_post['post_modified_gmt'] = $new_post['post_date_gmt'];

		switch ( $post->post_type ) {
			case 'course':
				$new_post['post_status'] = 'draft';
				break;
			case 'lesson':
				$new_post['post_status'] = 'draft';
				break;
			case 'quiz':
				$new_post['post_status'] = 'publish';
				break;
			case 'question':
				$new_post['post_status'] = 'publish';
				break;
		}

		// As per wp_update_post() we need to escape the data from the db.
		$new_post = wp_slash( $new_post );

		/**
		 * Filter arguments for `wp_insert_post` when duplicating a Sensei post.
		 * This may be a Course, Lesson, or Quiz.
		 *
		 * @since 3.11.0
		 *
		 * @hook  sensei_duplicate_post_args
		 *
		 * @param {array}   $new_post The arguments for duplicating the post.
		 * @param {WP_Post} $post     The original post being duplicated.
		 * @return {array}  The new arguments to be handed to `wp_insert_post`.
		 */
		$new_post = apply_filters( 'sensei_duplicate_post_args', $new_post, $post );

		$new_post_id = wp_insert_post( $new_post );

		if ( is_wp_error( $new_post_id ) || 0 === $new_post_id ) {
			return null;
		}

		$post_meta = get_post_custom( $post->ID );
		if ( $post_meta ) {
			$ignore_meta_keys = array( '_quiz_lesson', '_quiz_id', '_lesson_quiz', '_lesson_prerequisite' );

			/**
			 * Ignored meta fields when duplicating a post.
			 *
			 * @since 3.7.0
			 *
			 * @hook  sensei_duplicate_post_ignore_meta
			 *
			 * @param {array}   $meta_keys The meta keys to be ignored.
			 * @param {WP_Post} $new_post  The new duplicate post.
			 * @param {WP_Post} $post      The original post that's being duplicated.
			 * @return {array} $meta_keys The meta keys to be ignored.
			 */
			$ignore_meta = apply_filters( 'sensei_duplicate_post_ignore_meta', $ignore_meta_keys, $new_post, $post );

			if ( $ignore_course ) {
				$ignore_meta[] = '_lesson_course';
			}

			foreach ( $post_meta as $key => $meta ) {
				foreach ( $meta as $value ) {
					$value = maybe_unserialize( $value );
					if ( ! in_array( $key, $ignore_meta, true ) ) {
						add_post_meta( $new_post_id, $key, $value );
					}
				}
			}
		}

		add_post_meta( $new_post_id, '_duplicate', $post->ID );

		$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

		foreach ( $taxonomies as $slug => $tax ) {
			$terms = get_the_terms( $post->ID, $slug );
			if ( is_array( $terms ) && 0 < count( $terms ) ) {
				foreach ( $terms as $term ) {
					wp_set_object_terms( $new_post_id, $term->term_id, $slug, true );
				}
			}
		}

		$new_post = get_post( $new_post_id );
		if ( ! $new_post instanceof \WP_Post ) {
			return null;
		}

		return $new_post;
	}
}

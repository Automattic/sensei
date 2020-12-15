<?php
/**
 * File containing the class Sensei_Progress_Data_Store_Comments.
 *
 * @since [STORAGE_MILESTONE]
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data store for managing progress in WP Comments table.
 */
class Sensei_Progress_Data_Store_Comments implements Sensei_Progress_Data_Store_Interface {
	/**
	 * Query progress results.
	 *
	 * @param array $args Arguments used in query.
	 *
	 * @return Sensei_Progress_Data_Results
	 */
	public function query( $args = [] ) {
		// The data store doesn't recognized progress records tied to parent ID.
		unset( $args['parent_post_id'] );

		if ( isset( $args['type'] ) ) {
			$args['type'] = 'sensei_' . $args['type'] . '_status';
		}

		$query        = new WP_Comment_Query();
		$comments_raw = $query->query( $args );

		if ( ! empty( $args['count'] ) ) {
			return new Sensei_Progress_Data_Results(
				$args,
				[],
				(int) $comments_raw
			);
		}

		/**
		 * It runs while getting the comments for the given request.
		 *
		 * @deprecated [STORAGE_MILESTONE]
		 *
		 * @param int|array $comments
		 */
		$comments_raw = apply_filters_deprecated( 'sensei_check_for_activity', [ $comments_raw ], '[STORAGE_MILESTONE]' );
		$records      = [];
		foreach ( $comments_raw as $comment ) {
			$records[] = $this->comment_to_record( $comment );
		}

		return new Sensei_Progress_Data_Results(
			$args,
			$records,
			$comments_raw->found_comments
		);
	}

	/**
	 * Convert a comment record to a progress record.
	 *
	 * @param WP_Comment $comment Comment object.
	 *
	 * @return Sensei_Progress
	 */
	private function comment_to_record( WP_Comment $comment ) {
		$record_class = Sensei_Progress_Manager::instance()->get_record_class_name( $this->get_record_type( $comment ) );

		$lazy_data = function() use ( $comment ) {
			$data = [];
			foreach ( get_comment_meta( $comment->comment_ID ) as $meta_key => $values ) {
				if ( 'start' === $meta_key ) {
					$start_date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $values[0], wp_timezone() );
					$values[0]  = $start_date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
				}

				$data[ $meta_key ] = $values[0];
			}

			return $data;
		};

		return new $record_class(
			(int) $comment->user_id,
			(int) $comment->comment_post_ID,
			null,
			$comment->comment_approved,
			$lazy_data,
			DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $comment->comment_date_gmt ),
			null,
			$this,
			$comment->comment_ID
		);
	}

	/**
	 * Get a record type for a comment.
	 *
	 * @param WP_Comment $comment
	 *
	 * @return string|null
	 */
	private function get_record_type( WP_Comment $comment ) {
		$map = [
			'sensei_course_status' => 'course',
			'sensei_lesson_status' => 'lesson',
		];

		return isset( $map[ $comment->comment_type ] ) ? $map[ $comment->comment_type ] : null;
	}

	/**
	 * Reset progress.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function delete( Sensei_Progress $progress ) {
		if ( $progress->get_data_store_id() ) {
			$comment_id = $progress->get_data_store_id();
		} else {
			$comment_id = $this->fetch_existing_id( $progress );
		}

		if ( ! isset( $comment_id ) ) {
			return false;
		}

		$result = wp_delete_comment( $comment_id, true );

		$progress->set_storage_ref( null, null );
		Sensei()->flush_comment_counts_cache( $progress->get_post_id() );

		return $result;
	}

	/**
	 * Save a progress record.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function save( Sensei_Progress $progress ) {
		$comment = [];

		if ( $progress->get_data_store_id() ) {
			$comment['comment_ID'] = $progress->get_data_store_id();
		} else {
			$existing_id = $this->fetch_existing_id( $progress );
			if ( $existing_id ) {
				$comment['comment_ID'] = $existing_id;
			}
		}

		$comment['comment_post_ID']  = $progress->get_post_id();
		$comment['comment_date']     = $progress->get_date_created()->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
		$comment['comment_approved'] = $progress->get_status();
		$comment['comment_type']     = 'sensei_' . $progress->get_record_type() . '_status';

		if ( isset( $comment['comment_ID'] ) ) {
			if ( ! wp_update_comment( $comment ) ) {
				return false;
			}
		} else {
			$comment_id = wp_insert_comment( $comment );
			if ( ! $comment_id ) {
				return false;
			}

			$progress->set_storage_ref( $this, (int) $comment_id );
			Sensei()->flush_comment_counts_cache( $progress->get_post_id() );
		}

		foreach ( $progress->get_data() as $key => $value ) {
			// In comment meta, this has been stored using the WordPress timezone. Going forward, we'll standardize this to the UTC timezone.
			if ( 'start' === $key ) {
				$start_date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
				$value      = $start_date->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
			}

			update_comment_meta( $progress->get_data_store_id(), $key, $value );
		}

		return true;
	}

	/**
	 * Query for existing record ID based on unique properties.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return int|null
	 */
	private function fetch_existing_id( Sensei_Progress $progress ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Purpose is to go around cache and check for record in live DB.
		$comment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND user_id = %d AND comment_type = %s ",
				$progress->get_post_id(),
				$progress->get_user_id(),
				'sensei_' . $progress->get_record_type() . '_status'
			)
		);

		if ( $comment_id ) {
			return (int) $comment_id;
		}

		return null;
	}
}

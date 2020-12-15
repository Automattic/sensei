<?php
/**
 * File containing the class Sensei_Progress_Data_Store_Table.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data store for managing progress in our custom table.
 */
class Sensei_Progress_Data_Store_Table implements Sensei_Progress_Data_Store_Interface {
	/**
	 * Query progress results.
	 *
	 * @param array $args Arguments used in query.
	 *
	 * @return Sensei_Progress_Data_Results
	 */
	public function query( $args = [] ) {
		global $wpdb;

		// @todo Add caching.

		$where = [];
		if ( isset( $args['user_id'] ) ) {
			$where[] = $wpdb->prepare( 'user_id=%d', $args['user_id'] );
		}

		if ( isset( $args['post_id'] ) ) {
			$where[] = $wpdb->prepare( 'post_id=%d', $args['post_id'] );
		}

		if ( isset( $args['parent_post_id'] ) ) {
			$where[] = $wpdb->prepare( 'parent_post_id=%d', $args['parent_post_id'] );
		}

		if (
			isset( $args['type'] )
			&& 'all' !== $args['type']
			&& ( ! is_array( $args['type'] ) || ! in_array( 'all', $args['type'], true ) )
		) {
			$where[] = $this->where_helper( 'type', $args['type'] );
		}

		if (
			isset( $args['status'] )
			&& 'all' !== $args['status']
			&& ( ! is_array( $args['status'] ) || ! in_array( 'all', $args['status'], true ) )
		) {
			$where[] = $this->where_helper( 'status', $args['status'] );
		}

		$fields = '*';
		if ( ! empty( $args['count'] ) ) {
			$fields = 'COUNT(*)';
		}

		$query = "SELECT {$fields} FROM {$wpdb->sensei_lms_progress} WHERE " . implode( ' AND ', $where );

		if ( ! empty( $args['count'] ) ) {
			return new Sensei_Progress_Data_Results(
				$args,
				[],
				(int) $wpdb->get_var( $query )
			);
		}

		if ( ! empty( $args['number'] ) ) {
			if ( ! isset( $args['offset'] ) ) {
				$args['offset'] = 0;
			}

			$query .= ' LIMIT ' . (int) $args['offset'] . ',' . (int) $args['number'];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Preparation and caching handled above.
		$data_raw = $wpdb->get_results( $query, ARRAY_A );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Used with query above.
		$total_number = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		$results = [];
		foreach ( $data_raw as $item ) {
			$results[] = $this->row_to_record( $item );
		}

		return new Sensei_Progress_Data_Results(
			$args,
			$results,
			$total_number
		);
	}

	/**
	 * Prepare a value logical statement.
	 *
	 * @param string          $field       Field name.
	 * @param string|string[] $value       Value to prepare.
	 * @param string          $placeholder sprintf replacement for values.
	 *
	 * @return string
	 */
	private function where_helper( $field, $value, $placeholder = '%s' ) {
		global $wpdb;

		if ( is_array( $value ) ) {
			if ( empty( $value ) ) {
				return '1=0';
			}
			$values = [];
			foreach ( $value as $v ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholder is dynamic.
				$values[] = $wpdb->prepare( $placeholder, $v );
			}
			return $field . ' IN (' . implode( ',', $values ) . ')';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholder is dynamic.
		return $wpdb->prepare( $field . '=' . $placeholder, $value );
	}

	/**
	 * Convert a database row to a progress record.
	 *
	 * @param array $item Progress database record.
	 *
	 * @return Sensei_Progress
	 */
	private function row_to_record( $item ) {
		$record_class = Sensei_Progress_Manager::instance()->get_record_class_name( $item['type'] );

		if ( ! isset( $item['data'] ) ) {
			$item['data'] = '[]';
		}

		$data = json_decode( $item['data'], true );

		return new $record_class(
			(int) $item['user_id'],
			(int) $item['post_id'],
			(int) $item['parent_post_id'],
			$item['status'],
			$data,
			DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $item['date_created'] ),
			DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $item['date_modified'] ),
			$this,
			$item['id']
		);
	}

	/**
	 * Delete a progress record.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function delete( Sensei_Progress $progress ) {
		global $wpdb;

		if ( $progress->get_data_store_id() ) {
			$record_id = $progress->get_data_store_id();
		} else {
			$record_id = $this->fetch_existing_id( $progress );
		}

		if ( ! isset( $record_id ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Handling directly.
		$result = $wpdb->delete( $wpdb->sensei_lms_progress, [ 'id' => (int) $record_id ] );

		// @todo Cache cleanup.

		return false !== $result;
	}

	/**
	 * Save a progress record.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function save( Sensei_Progress $progress ) {
		global $wpdb;

		$id = null;
		if ( $progress->get_data_store_id() ) {
			$id = $progress->get_data_store_id();
		} else {
			$existing_id = $this->fetch_existing_id( $progress );
			if ( $existing_id ) {
				$id = $existing_id;
			}
		}

		$record                   = [];
		$record['user_id']        = $progress->get_user_id();
		$record['post_id']        = $progress->get_post_id();
		$record['parent_post_id'] = $progress->get_parent_post_id();
		$record['status']         = $progress->get_status();
		$record['type']           = $progress->get_record_type();
		$record['data']           = wp_json_encode( $progress->get_data() );
		$record['date_created']   = $progress->get_date_created()->format( 'Y-m-d H:i:s' );
		$record['date_modified']  = $progress->get_date_modified()->format( 'Y-m-d H:i:s' );

		$data_value_types = [
			'%d', // user_id.
			'%d', // post_id.
			'%d', // parent_post_id.
			'%s', // status.
			'%s', // type.
			'%s', // data.
			'%s', // date_created.
			'%s', // date_modified.
		];

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Handling directly.
			if ( ! $wpdb->update( $wpdb->sensei_lms_progress, $record, [ 'id' => $id ], $data_value_types, [ '%d' ] ) ) {
				return false;
			}
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Handling directly.
			$result = $wpdb->insert( $wpdb->sensei_lms_progress, $record, $data_value_types );
			if ( ! $result ) {
				return false;
			}

			$progress->set_storage_ref( $this, (int) $wpdb->insert_id );

			// @todo Cache cleanup.
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
				"SELECT id FROM $wpdb->sensei_lms_progress WHERE post_id = %d AND user_id = %d AND type = %s ",
				$progress->get_post_id(),
				$progress->get_user_id(),
				$progress->get_record_type()
			)
		);

		if ( $comment_id ) {
			return (int) $comment_id;
		}

		return null;
	}
}

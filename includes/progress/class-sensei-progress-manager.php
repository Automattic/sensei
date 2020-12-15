<?php
/**
 * File containing the class Sensei_Progress_Manager.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton handling the management of progress.
 */
class Sensei_Progress_Manager {
	const COURSE_DATA_STORE_META  = '_progress_data_store';
	const INITIAL_PROGRESS_STATUS = 'not-started';

	/**
	 * Instance of singleton.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Instantiated data store objects.
	 *
	 * @var Sensei_Progress_Data_Store_Interface[]
	 */
	private $data_stores;

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Sensei_Progress_Manager constructor. Private so it can only be initialized internally.
	 */
	private function __construct() {
		$this->data_stores = [
			'table'    => new Sensei_Progress_Data_Store_Table(),
			'comments' => new Sensei_Progress_Data_Store_Comments(),
		];

		$this->data_stores['hybrid'] = new Sensei_Progress_Data_Store_Hybrid( $this->data_stores );
	}

	/**
	 * Query progress results.
	 *
	 * @param array $args Arguments used in query.
	 *
	 * @return Sensei_Progress_Data_Results
	 */
	public function query( $args = [] ) {
		$data_source = null;
		if ( isset( $args['post_id'] ) ) {
			$parent_post_id = isset( $args['parent_post_id'] ) ? $args['parent_post_id'] : null;
			$data_source    = $this->get_data_store( $args['post_id'], $parent_post_id );
		}

		if ( ! $data_source ) {
			return $this->query_all( $args );
		}

		return $data_source->query( $args );
	}

	/**
	 * Query progress results from both data sources.
	 *
	 * @param array $args Arguments used in query.
	 *
	 * @return Sensei_Progress_Data_Results
	 */
	private function query_all( $args = [] ) {
		$data_stores = $this->get_data_stores();

		if ( ! isset( $args['number'] ) || ! empty( $args['count'] ) ) {
			return $data_stores['table']->query( $args )->merge( $data_stores['comments']->query( $args ) );
		}

		$table_results = $data_stores['table']->query( $args );

		$number = (int) $args['number'];
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		$number -= count( $table_results->get_results() );
		if ( $number > 0 ) {
			$offset        -= $table_results->get_total_found();
			$args['number'] = $number;
			$args['offset'] = $offset;

			$comments_results = $data_stores['comments']->query( $args );
		} else {
			$args['count'] = true;
			unset( $args['number'], $args['offset'] );

			$comments_results = $data_stores['comments']->query( $args );
		}

		return $table_results->merge( $comments_results );
	}

	/**
	 * Get the record by type.
	 *
	 * @param string $type Type of record.
	 *
	 * @return string Class name for record.
	 * @throws Exception When type does not exist.
	 */
	public function get_record_class_name( $type ) {
		$map = [
			'course' => Sensei_Progress_Course::class,
			'lesson' => Sensei_Progress_Lesson::class,
		];

		if ( ! isset( $map[ $type ] ) ) {
			throw new Exception( sprintf( 'Unknown progress record type: %s', $type ) );
		}

		return $map[ $type ];
	}

	/**
	 * Get a specific progress item. If the item doesn't exist, it will return a fresh result.
	 *
	 * @param string   $type           Type of progress item (course, lesson).
	 * @param int      $user_id        User ID.
	 * @param int      $post_id        Post ID.
	 * @param int|null $parent_post_id Post parent ID.
	 *
	 * @return Sensei_Progress
	 */
	public function get_progress( $type, $user_id, $post_id, $parent_post_id = null ) {
		$data_store = $this->get_data_store( $post_id, $parent_post_id );

		if ( ! $data_store ) {
			return null;
		}

		$progress_results = $data_store->query(
			[
				'user_id'        => (int) $user_id,
				'post_id'        => (int) $post_id,
				'parent_post_id' => (int) $parent_post_id,
				'type'           => $type,
				'number'         => 1,
			]
		);

		if ( ! empty( $progress_results->get_results() ) ) {
			return $progress_results->get_results()[0];
		}

		$record_class = $this->get_record_class_name( $type );

		return new $record_class(
			$user_id,
			$post_id,
			$parent_post_id,
			self::INITIAL_PROGRESS_STATUS
		);
	}

	/**
	 * Save progress object.
	 *
	 * @param Sensei_Progress $progress Progress object.
	 *
	 * @return bool
	 */
	public function save( Sensei_Progress $progress ) {
		$progress->set_date_modified( new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) );

		if ( $progress->get_data_store() ) {
			return $progress->get_data_store()->save( $progress );
		}

		$data_store = $this->get_data_store( $progress->get_post_id(), $progress->get_parent_post_id() );
		if ( ! $data_store ) {
			return false;
		}

		return $data_store->save( $progress );
	}

	/**
	 * Get the progress data store for a post.
	 *
	 * @param int      $post_id        Post ID for the progress record.
	 * @param int|null $parent_post_id Parent post ID for the progress record.
	 *
	 * @return Sensei_Progress_Data_Store_Interface|false
	 */
	public function get_data_store( $post_id, $parent_post_id = null ) {
		// @todo should this a blog wide option?
		$default_data_store = 'table';

		$course_id = $this->get_course_id( $post_id, $parent_post_id );

		$course_data_store = get_post_meta( $course_id, self::COURSE_DATA_STORE_META, true );
		if ( ! $course_data_store ) {
			$course_data_store = $default_data_store;
		}

		$data_stores = $this->get_data_stores();
		if ( isset( $data_stores[ $course_data_store ] ) ) {
			return $data_stores[ $course_data_store ];
		}

		return false;
	}

	/**
	 * Check if a data store is set to a particular data store key.
	 *
	 * @param string                               $test_data_store_key Data store key to check for.
	 * @param Sensei_Progress_Data_Store_Interface $data_store          Data store object to test.
	 *
	 * @return bool
	 */
	public function is_data_store( $test_data_store_key, Sensei_Progress_Data_Store_Interface $data_store ) {
		$data_stores = $this->get_data_stores();

		return ! isset( $data_stores[ $test_data_store_key ] ) || $data_stores[ $test_data_store_key ] !== $data_store;
	}

	/**
	 * Helper to get a course ID.
	 *
	 * @param int      $post_id        Post ID.
	 * @param int|null $parent_post_id Parent post ID.
	 *
	 * @return int|null
	 */
	public function get_course_id( $post_id, $parent_post_id = null ) {
		if ( $parent_post_id && 'course' === get_post_type( $parent_post_id ) ) {
			return $parent_post_id;
		} elseif ( 'course' === get_post_type( $post_id ) ) {
			return $post_id;
		} elseif ( 'lesson' === get_post_type( $post_id ) ) {
			return (int) get_post_meta( $post_id, '_lesson_course', true );
		}

		return null;
	}

	/**
	 * Get the data stores.
	 *
	 * @return Sensei_Progress_Data_Store_Interface[]
	 */
	public function get_data_stores() {
		return $this->data_stores;
	}
}

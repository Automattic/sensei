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
	const COURSE_DATA_STORE_META = '_progress_data_store';

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
	 * Get the record by type.
	 *
	 * @param string $type Type of record.
	 *
	 * @return string Class name for record.
	 */
	public function get_record_class_name( $type ) {
		$map = [
			'course' => Sensei_Progress_Course::class,
			'lesson' => Sensei_Progress_Lesson::class,
		];

		return isset( $map[ $type ] ) ? $map[ $type ] : null;
	}

	/**
	 * Get a specific progress item.
	 *
	 * @param int      $user_id        User ID.
	 * @param string   $type           Type of progress item (course, lesson).
	 * @param int      $post_id        Post ID.
	 * @param int|null $parent_post_id Post parent ID.
	 *
	 * @return Sensei_Progress|null
	 */
	public function get_progress( $user_id, $type, $post_id, $parent_post_id = null ) {
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

		return null;
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
		// @todo make this a blog wide option.
		$default_data_store = 'comments';

		$course_id = null;
		if ( $parent_post_id && 'course' === get_post_type( $parent_post_id ) ) {
			$course_id = $parent_post_id;
		} elseif ( 'course' === get_post_type( $post_id ) ) {
			$course_id = $post_id;
		} elseif ( 'lesson' === get_post_type( $post_id ) ) {
			$course_id = (int) get_post_meta( $post_id, '_lesson_course', true );
		}

		if ( empty( $course_id ) ) {
			return false;
		}

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
	 * Get the data stores.
	 *
	 * @return Sensei_Progress_Data_Store_Interface[]
	 */
	public function get_data_stores() {
		return $this->data_stores;
	}
}

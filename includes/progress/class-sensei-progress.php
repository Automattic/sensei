<?php
/**
 * File containing the abstract class Sensei_Progress.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for progress objects.
 */
abstract class Sensei_Progress {

	/**
	 * Post ID of the object this progress record is associated with.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Post ID of the parent object for this record, usually the course ID if a lesson or quiz.
	 *
	 * @var int
	 */
	private $parent_post_id;

	/**
	 * User ID for this progress record.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Progress record status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Date this record was first created.
	 *
	 * @var DateTimeImmutable
	 */
	private $date_created;

	/**
	 * Date this record was last modified.
	 *
	 * @var DateTimeImmutable
	 */
	private $date_modified;

	/**
	 * Meta data associated with this record.
	 *
	 * @var array|callable
	 */
	private $data;

	/**
	 * Data store owning this record.
	 *
	 * @var Sensei_Progress_Data_Store_Interface
	 */
	private $data_store;

	/**
	 * Unique identifier for progress record in data store.
	 *
	 * @var int
	 */
	private $data_store_id;

	/**
	 * Sensei_Progress constructor.
	 *
	 * @param int                                       $user_id        User ID.
	 * @param int                                       $post_id        Post ID for the course, lesson, or quiz.
	 * @param int|null                                  $parent_post_id Parent post ID, usually the course ID for lesson or quizzes.
	 * @param string                                    $status         Progress record status.
	 * @param array|callable                            $data           Meta data. This can be an array or a lazy callable
	 *                                                                  that returns an array the first time it is called.
	 * @param DateTimeImmutable|null                    $date_created   Date record was first created.
	 * @param DateTimeImmutable|null                    $date_modified  Date record was last modified.
	 * @param Sensei_Progress_Data_Store_Interface|null $data_store     Data store.
	 * @param int|null                                  $data_store_id  Unique identifier for the data store.
	 */
	final public function __construct(
		$user_id,
		$post_id,
		$parent_post_id,
		$status,
		$data = [],
		DateTimeImmutable $date_created = null,
		DateTimeImmutable $date_modified = null,
		Sensei_Progress_Data_Store_Interface $data_store = null,
		$data_store_id = null
	) {
		$this->user_id        = $user_id;
		$this->post_id        = $post_id;
		$this->parent_post_id = $parent_post_id;
		$this->status         = $status;
		$this->data           = $data;
		$this->date_created   = isset( $date_created ) ? $date_created : new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$this->date_modified  = isset( $date_modified ) ? $date_modified : new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$this->data_store     = $data_store;
		$this->data_store_id  = $data_store_id;
	}

	/**
	 * Help with deprecated WP comment properties.
	 *
	 * @param string $name Property name.
	 */
	public function __get( $name ) {
		$map = [
			'comment_ID'       => [ $this, 'get_data_store_id' ],
			'comment_post_ID'  => [ $this, 'get_post_id' ],
			'comment_date'     => function() {
				return $this->get_date_created()->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
			},
			'comment_date_gmt' => function() {
				return $this->get_date_created()->format( 'Y-m-d H:i:s' );
			},
			'comment_approved' => [ $this, 'get_status' ],
			'comment_type'     => function() {
				return 'sensei_' . $this->get_record_type() . '_status';
			},
			'user_id'          => [ $this, 'get_user_id' ],
		];

		if ( isset( $map[ $name ] ) ) {
			_doing_it_wrong( __CLASS__, esc_html( sprintf( 'Progress record is being accessed using a deprecated property (%s)', $name ) ), '[STORAGE_MILESTONE]' );

			return call_user_func( $map[ $name ] );
		}

		return null;
	}

	/**
	 * Get the record type (course, lesson).
	 *
	 * @return string
	 */
	abstract public function get_record_type();

	/**
	 * Get the unique identifier used by the data store.
	 *
	 * @return int
	 */
	public function get_data_store_id() {
		return $this->data_store_id;
	}

	/**
	 * Get the data store storing this record.
	 *
	 * @return Sensei_Progress_Data_Store_Interface|null
	 */
	public function get_data_store() {
		return $this->data_store;
	}

	/**
	 * Set the data store record storage reference.
	 *
	 * @param Sensei_Progress_Data_Store_Interface|null $data_store Data store object.
	 * @param int|null                                  $id         Unique identifier in data store.
	 */
	public function set_storage_ref( Sensei_Progress_Data_Store_Interface $data_store, $id ) {
		$this->data_store = $data_store;
		$this->id         = $id;
	}

	/**
	 * Copy the record for storage in a new data store.
	 *
	 * @return static
	 */
	public function copy() {
		return new static( $this->user_id, $this->post_id, $this->parent_post_id, $this->status, $this->data );
	}

	/**
	 * Get the post ID for the course, lesson, and quiz.
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Set the post ID for the course, lesson, and quiz.
	 *
	 * @param int $post_id Post ID.
	 */
	public function set_post_id( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Get the parent post ID, usually the course ID for lesson or quizzes.
	 *
	 * @return int
	 */
	public function get_parent_post_id() {
		return $this->parent_post_id;
	}

	/**
	 * Set the parent post ID, usually the course ID for lesson or quizzes.
	 *
	 * @param int $parent_post_id Parent post ID.
	 */
	public function set_parent_post_id( $parent_post_id ) {
		$this->parent_post_id = $parent_post_id;
	}

	/**
	 * Get the user ID for this record.
	 *
	 * @return int User ID.
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Set the user ID for this record.
	 *
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get the date this record was created.
	 *
	 * @return DateTimeImmutable
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Set the date this record was created.
	 *
	 * @param DateTimeImmutable $date_created Date created.
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * Get the date this record was modified.
	 *
	 * @return DateTimeImmutable
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}

	/**
	 * Set the date this record was last modified.
	 *
	 * @param DateTimeImmutable $date_modified Date modified.
	 */
	public function set_date_modified( $date_modified ) {
		$this->date_modified = $date_modified;
	}

	/**
	 * Get the record status.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Set the record status.
	 *
	 * @param string $status
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * Get the data associated with this record.
	 *
	 * @return array
	 */
	public function get_data() {
		// Load lazy data.
		if ( is_callable( $this->data ) ) {
			$this->data = call_user_func( $this->data );
		}

		return $this->data;
	}

	/**
	 * Set the data associated with this record.
	 *
	 * @param array $data
	 */
	public function set_data( $data ) {
		$this->data = $data;
	}
}

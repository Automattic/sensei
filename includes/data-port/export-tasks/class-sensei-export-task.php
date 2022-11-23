<?php
/**
 * File containing the Sensei_Export_Task class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export content to a CSV file for the given type.
 */
abstract class Sensei_Export_Task
	extends Sensei_Data_Port_Task
	implements Sensei_Data_Port_Task_Interface {

	const STATE_COMPLETED_POSTS = 'completed-posts';
	const STATE_ABORTED         = 'aborted';

	/**
	 * Output CSV file name.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Number of posts per batch.
	 *
	 * @var int
	 */
	private $batch_size = 30;

	/**
	 * Number of posts exported.
	 *
	 * @var int
	 */
	private $completed_posts = 0;

	/**
	 * Total number of posts to be exported.
	 *
	 * @var int
	 */
	private $total_posts = 0;

	/**
	 * Flag if task was aborted.
	 *
	 * @var boolean
	 */
	private $is_aborted = false;

	/**
	 * Query of posts in the current batch.
	 *
	 * @var WP_Query
	 */
	private $query;

	/**
	 * Create an export task for a content type.
	 *
	 * Creates an output file if it doesn't exist, and loads a batch of posts.
	 *
	 * @param Sensei_Data_Port_Job $job The job.
	 */
	public function __construct( Sensei_Data_Port_Job $job ) {
		parent::__construct( $job );

		$files = $this->get_job()->get_files();
		$type  = $this->get_content_type();

		if ( empty( $files[ $type ] ) ) {
			$this->add_file_to_job( $this->create_csv_file() );
			$files = $this->get_job()->get_files();
		}

		$task_state            = $this->get_job()->get_state( $type );
		$this->completed_posts = isset( $task_state[ self::STATE_COMPLETED_POSTS ] ) ? $task_state[ self::STATE_COMPLETED_POSTS ] : 0;

		if ( isset( $task_state[ self::STATE_ABORTED ] ) ) {
			$this->is_aborted = $task_state[ self::STATE_ABORTED ];
		}

		$this->query = new WP_Query(
			[
				'post_type'      => $type,
				'posts_per_page' => $this->batch_size,
				'offset'         => $this->completed_posts,
				'post_status'    => 'any',
				'orderby'        => 'ID',
			]
		);

		$this->total_posts = $this->query->found_posts > 0 ? $this->query->found_posts : $this->completed_posts;

		$this->file = get_attached_file( $files[ $type ] );

	}

	/**
	 * Run export task.
	 *
	 * @throws Exception Caught exception when empty file is passed in tests.
	 */
	public function run() {
		$posts = $this->query->posts;
		$job   = $this->get_job();

		try {
			if ( empty( $this->file ) ) {
				throw new Exception( 'Missing output file' );
			}

			$output_file = new SplFileObject( $this->file, 'a' );
		} catch ( Exception $e ) {
			$this->is_aborted = true;

			$job->set_state(
				$this->get_content_type(),
				[
					self::STATE_ABORTED => $this->is_aborted,
				]
			);

			$this->get_job()->add_log_entry(
				// translators: Placeholder is the content file being exported.
				sprintf( __( 'Error exporting the %s file.', 'sensei-lms' ), $this->get_content_type() ),
				Sensei_Data_Port_Job::LOG_LEVEL_ERROR,
				[
					'type' => $this->get_content_type(),
					'code' => 'sensei_data_port_job_export_file',
				]
			);

			return;
		}

		foreach ( $posts as $post ) {
			$serialized_posts = $this->get_serialized_post( $post );

			$output_file->fputcsv( $serialized_posts );
			$this->completed_posts++;
		}
		$output_file = null;

		$job->set_state(
			$this->get_content_type(),
			[
				self::STATE_COMPLETED_POSTS => $this->completed_posts,
			]
		);
	}

	/**
	 * Get serialized post.
	 *
	 * @param WP_Post $post The post.
	 *
	 * @return string[]
	 */
	private function get_serialized_post( $post ) {
		$columns     = $this->get_post_fields( $post );
		$column_keys = array_keys( $this->get_type_schema()->get_schema() );

		return array_map(
			function( $column ) use ( $columns ) {
				if ( ! isset( $columns[ $column ] ) ) {
					return '';
				}

				return $columns[ $column ];
			},
			$column_keys
		);
	}

	/**
	 * Create a new CSV file with column headers.
	 *
	 * @return string Temporary filename.
	 */
	private function create_csv_file() {
		$columns = $this->get_type_schema()->get_schema();
		$headers = array_map( 'ucwords', array_keys( $columns ) );

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$filename = wp_tempnam( 'sensei-export-csv-' . $this->get_content_type() );
		$file     = new SplFileObject( $filename, 'w' );
		$file->fputcsv( $headers );
		$file = null;

		return $filename;
	}

	/**
	 * Content type of the task.
	 *
	 * @return string
	 */
	abstract public function get_content_type();

	/**
	 * Collect exported fields for the post.
	 *
	 * @param WP_Post $post
	 *
	 * @return array The columns data per key.
	 */
	abstract protected function get_post_fields( $post );

	/**
	 * Schema for the content type.
	 *
	 * @return Sensei_Data_Port_Schema
	 */
	abstract protected function get_type_schema();


	/**
	 * Returns true if the task is completed.
	 *
	 * @return boolean
	 */
	public function is_completed() {
		return $this->completed_posts === $this->total_posts || $this->is_aborted;
	}

	/**
	 * Returns the completion ratio of this task. The ration has the following format:
	 *
	 * {
	 *
	 * @type integer $completed Number of completed actions.
	 * @type integer $total Number of total actions.
	 * }
	 *
	 * @return array
	 */
	public function get_completion_ratio() {
		return [
			'total'     => $this->total_posts,
			'completed' => $this->completed_posts,
		];
	}

	/**
	 * Attach the file to the job.
	 *
	 * @param string $tmp_file
	 */
	public function add_file_to_job( $tmp_file ) {
		$type     = $this->get_content_type();
		$date     = gmdate( 'Y-m-d' );
		$filename = sanitize_file_name( get_bloginfo( 'name' ) . '-' . ucwords( $type ) . 's-' . $date . '.csv' );
		$this->get_job()->save_file( $type, $tmp_file, $filename );
		if ( file_exists( $tmp_file ) ) {
			unlink( $tmp_file );
		}
	}

}

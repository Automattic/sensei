<?php
/**
 * File containing the class \Sensei\Student_Progress\Quiz_Progress\Repositories\Tables_Based_Quiz_Progress_Repository.
 *
 * @package sensei
 */

namespace Sensei\Student_Progress\Quiz_Progress\Repositories;

use DateTimeImmutable;
use Sensei\Student_Progress\Quiz_Progress\Models\Quiz_Progress;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tables_Based_Quiz_Progress_Repository
 *
 * @since $$next-version$$
 */
class Tables_Based_Quiz_Progress_Repository implements Quiz_Progress_Repository_Interface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Tables_Based_Course_Progress_Repository constructor.
	 *
	 * @param wpdb $wpdb WordPress database object.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function create( int $quiz_id, int $user_id ): Quiz_Progress {
		$current_datetime = current_datetime();
		$this->wpdb->insert(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'post_id'        => $quiz_id,
				'user_id'        => $user_id,
				'parent_post_id' => null,
				'type'           => 'quiz',
				'status'         => Quiz_Progress::STATUS_IN_PROGRESS,
				'started_at'     => $current_datetime->getTimestamp(),
				'completed_at'   => null,
				'created_at'     => $current_datetime->getTimestamp(),
				'updated_at'     => $current_datetime->getTimestamp(),
			],
			[
				'%d',
				'%d',
				null,
				'%s',
				'%s',
				'%d',
				null,
				'%d',
				'%d',
			]
		);
		$id = (int) $this->wpdb->insert_id;

		return new Quiz_Progress(
			$id,
			$quiz_id,
			$user_id,
			Quiz_Progress::STATUS_IN_PROGRESS,
			$current_datetime,
			null,
			$current_datetime,
			$current_datetime
		);
	}

	public function get( int $quiz_id, int $user_id ): ?Quiz_Progress {
		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			'SELECT * FROM ' . $table_name . ' WHERE post_id = %d AND user_id = %d AND type = %s',
			$quiz_id,
			$user_id,
			'quiz'
		);

		$row = $this->wpdb->get_row( $query );
		if ( ! $row ) {
			return null;
		}

		return new Quiz_Progress(
			(int) $row->id,
			(int) $row->post_id,
			(int) $row->user_id,
			$row->status,
			new DateTimeImmutable( "@{$row->started_at}", wp_timezone() ),
			$row->completed_at ? new DateTimeImmutable( "@{$row->completed_at}", wp_timezone() ) : null,
			new DateTimeImmutable( "@{$row->created_at}", wp_timezone() ),
			new DateTimeImmutable( "@{$row->updated_at}", wp_timezone() )
		);
	}

	public function has( int $quiz_id, int $user_id ): bool {
		$table_name = $this->wpdb->prefix . 'sensei_lms_progress';
		$query      = $this->wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id = %d AND user_id = %d AND type = %s',
			$quiz_id,
			$user_id,
			'quiz'
		);

		$count = (int) $this->wpdb->get_var( $query );

		return $count > 0;
	}

	public function save( Quiz_Progress $quiz_progress ): void {
		$this->wpdb->update(
			$this->wpdb->prefix . 'sensei_lms_progress',
			[
				'status'       => $quiz_progress->get_status(),
				'started_at'   => $quiz_progress->get_started_at()->getTimestamp(),
				'completed_at' => $quiz_progress->get_completed_at() ? $quiz_progress->get_completed_at()->getTimestamp() : null,
				'updated_at'   => current_datetime()->getTimestamp(),
			],
			[
				'id' => $quiz_progress->get_id(),
			],
			[
				'%s',
				'%d',
				$quiz_progress->get_completed_at() ? '%d' : null,
				'%d',
			],
			[
				'%d',
			]
		);
	}
}

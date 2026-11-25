<?php
/**
 * Shared test data for progress planning and repair tests.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- These helpers create and clean up their own database test records.

use Sensei\Internal\Services\Progress_Storage_Settings;

/**
 * Create and clean up the records used by progress repair tests.
 */
trait Sensei_Progress_Reconciliation_Test_Helpers {

	use \Sensei_HPPS_Helpers;

	protected array $posts = array();
	protected int $student_id;
	protected array $settings;
	protected string $trigger = '';

	/**
	 * Create test records that fresh database reads can see.
	 */
	protected function set_up_reconciliation_fixtures(): void {
		global $wpdb;

		// Commit fixtures so independent connections can read them and execution
		// tests can use ordinary transaction statements. Cleanup removes only
		// the records owned by this test.
		$wpdb->query( 'COMMIT' );
		$wpdb->query( 'SET autocommit = 1' );
		$this->settings   = get_option( 'sensei-settings', array() );
		$this->student_id = self::factory()->user->create();
	}

	/**
	 * Delete the committed test records and restart the WordPress test transaction.
	 */
	protected function tear_down_reconciliation_fixtures(): void {
		global $wpdb;

		$wpdb->check_connection( false );
		$wpdb->query( 'ROLLBACK' );
		if ( $this->trigger ) {
			$wpdb->query( $wpdb->prepare( 'DROP TRIGGER IF EXISTS %i', $this->trigger ) );
		}
		// These records were committed, so WordPress cannot roll them back for us.
		// Delete only records belonging to this test's student.
		$submissions = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE user_id = %d', $wpdb->prefix . 'sensei_lms_quiz_submissions', $this->student_id ) );
		foreach ( $submissions as $submission_id ) {
			$answers = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE submission_id = %d', $wpdb->prefix . 'sensei_lms_quiz_answers', $submission_id ) );
			foreach ( $answers as $answer_id ) {
				$wpdb->delete( $wpdb->prefix . 'sensei_lms_quiz_grades', array( 'answer_id' => $answer_id ) );
			}
			$wpdb->delete( $wpdb->prefix . 'sensei_lms_quiz_answers', array( 'submission_id' => $submission_id ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sensei_lms_quiz_submissions', array( 'user_id' => $this->student_id ) );
		$wpdb->delete( $wpdb->prefix . 'sensei_lms_progress', array( 'user_id' => $this->student_id ) );
		foreach ( get_comments(
			array(
				'user_id' => $this->student_id,
				'status'  => 'all',
			)
		) as $comment ) {
			wp_delete_comment( $comment->comment_ID, true );
		}
		$this->save_settings( $this->settings );
		$this->restore_progress_storage_mode();
		foreach ( $this->posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $this->student_id );
		$wpdb->query( 'SET autocommit = 0' );
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Save the source setting directly so setup does not trigger table resets.
	 */
	protected function select_source( string $source ): void {
		$this->set_progress_storage_mode( $source );
		$this->save_settings(
			$this->settings + array(
				'experimental_progress_storage' => true,
				'experimental_progress_storage_synchronization' => true,
			)
		);
		$settings                                  = get_option( 'sensei-settings', array() );
		$settings['experimental_progress_storage'] = true;
		$settings['experimental_progress_storage_synchronization'] = true;
		$settings['experimental_progress_storage_repository']      = 'tables' === $source ? Progress_Storage_Settings::TABLES_STORAGE : Progress_Storage_Settings::COMMENTS_STORAGE;
		$this->save_settings( $settings );
	}

	protected function save_settings( array $settings ): void {
		global $wpdb;
		$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $settings ) ), array( 'option_name' => 'sensei-settings' ) );
		wp_cache_delete( 'sensei-settings', 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	}

	protected function post( string $type, int $parent_id = 0 ): int {
		$factory       = new \Sensei_Factory();
		$id            = $factory->$type->create( array( 'post_parent' => $parent_id ) );
		$this->posts[] = $id;
		if ( 'quiz' === $type && $parent_id ) {
			update_post_meta( $parent_id, '_lesson_quiz', $id );
			update_post_meta( $id, '_pass_required', 'on' );
		}
		return $id;
	}

	protected function progress( string $type, int $post_id, string $store, string $status ): int {
		global $wpdb;
		if ( 'comments' === $store ) {
			$id = wp_insert_comment(
				array(
					'comment_post_ID'  => $post_id,
					'user_id'          => $this->student_id,
					'comment_type'     => 'course' === $type ? 'sensei_course_status' : 'sensei_lesson_status',
					'comment_approved' => $status,
					'comment_date'     => '2024-01-02 12:00:00',
					'comment_date_gmt' => '2024-01-02 12:00:00',
				)
			);
			update_comment_meta( $id, 'start', '2024-01-01 12:00:00' );
			return (int) $id;
		}
		$wpdb->insert(
			$wpdb->prefix . 'sensei_lms_progress',
			array(
				'post_id'      => $post_id,
				'user_id'      => $this->student_id,
				'type'         => $type,
				'status'       => $status,
				'started_at'   => '2024-01-01 12:00:00',
				'completed_at' => in_array( $status, array( 'complete', 'passed', 'graded' ), true ) ? '2024-01-02 12:00:00' : null,
				'created_at'   => '2024-01-01 12:00:00',
				'updated_at'   => '2024-01-02 12:00:00',
			)
		);
		return (int) $wpdb->insert_id;
	}

	protected function source_rows( string $store, int $post_id ): array {
		global $wpdb;
		if ( 'tables' === $store ) {
			$progress    = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE user_id = %d ORDER BY id', $wpdb->prefix . 'sensei_lms_progress', $this->student_id ), ARRAY_A );
			$submissions = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE user_id = %d ORDER BY id', $wpdb->prefix . 'sensei_lms_quiz_submissions', $this->student_id ), ARRAY_A );
			$answers     = array();
			$grades      = array();
			foreach ( $submissions as $submission ) {
				$stored_answers = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE submission_id = %d ORDER BY id', $wpdb->prefix . 'sensei_lms_quiz_answers', $submission['id'] ), ARRAY_A );
				$answers        = array_merge( $answers, $stored_answers );
				foreach ( $stored_answers as $answer ) {
					$grades = array_merge( $grades, $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE answer_id = %d ORDER BY id', $wpdb->prefix . 'sensei_lms_quiz_grades', $answer['id'] ), ARRAY_A ) );
				}
			}
			return $progress || $submissions ? array(
				'progress'    => $progress,
				'submissions' => $submissions,
				'answers'     => $answers,
				'grades'      => $grades,
			) : array();
		}
		$comments = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE comment_post_ID = %d AND user_id = %d ORDER BY comment_ID', $wpdb->comments, $post_id, $this->student_id ), ARRAY_A );
		$metadata = array();
		foreach ( $comments as $comment ) {
			$metadata = array_merge( $metadata, $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE comment_id = %d ORDER BY meta_id', $wpdb->commentmeta, $comment['comment_ID'] ), ARRAY_A ) );
		}
		return array(
			'comments' => $comments,
			'metadata' => $metadata,
		);
	}

	/**
	 * Run each scenario with comments as the source, then tables as the source.
	 *
	 * @return array
	 */
	public static function directions(): array {
		return array(
			'comments to tables' => array( 'comments' ),
			'tables to comments' => array( 'tables' ),
		);
	}
}

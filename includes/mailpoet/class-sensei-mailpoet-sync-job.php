<?php
/**
 * File containing the class Sensei_MailPoet_Sync_Job.
 *
 * @since $$next-version$$
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background Job to sync MailPoet list students with Sensei site courses and groups students.
 */
class Sensei_MailPoet_Sync_Job extends Sensei_Background_Job_Batch {
	/**
	 * Constructor: Sets up the job.
	 *
	 * @param object $mailpoet_api_handle MailPoet API handle.
	 */
	public function __construct( $mailpoet_api_handle ) {
		parent::__construct();
		$this->mailpoet_api_handle = $mailpoet_api_handle;
	}

	/**
	 * Get the job batch size.
	 *
	 * @return int
	 */
	protected function get_batch_size() : int {
		return 15;
	}

	/**
	 * Can multiple instances be enqueued at the same time?
	 *
	 * @return bool
	 */
	protected function allow_multiple_instances() : bool {
		return true;
	}

	/**
	 * Run batch MailPoet Sync.
	 *
	 * @param int $offset Current offset.
	 *
	 * @return bool Returns true if there is more to do.
	 */
	protected function run_batch( int $offset ) : bool {
		$sensei_mp_instance = Sensei_MailPoet::instance( $this->mailpoet_api_handle );

		$mailpoet_lists = $sensei_mp_instance->get_mailpoet_lists();
		$sensei_lists   = $sensei_mp_instance->get_sensei_lists();
		$current_batch  = array_slice( $sensei_lists, $offset, $this->get_batch_size() );

		$remaining = count( $sensei_lists ) - $offset;

		foreach ( $current_batch as $list ) {
			$list_name = Sensei_MailPoet_Repository::get_list_name( $list['name'], $list['post_type'] );
			// find list in MailPoet lists array. if not exists, create one.
			if ( ! array_key_exists( $list_name, $mailpoet_lists ) ) {
				$mp_list_id = $sensei_mp_instance->create_list( $list_name, $list['description'] );
			} else {
				$mp_list_id = $mailpoet_lists[ $list_name ]['id'];
			}

			if ( ! empty( $mp_list_id ) ) {
				$students    = Sensei_MailPoet_Repository::get_students( $list['id'], $list['post_type'] );
				$subscribers = array_filter(
					$sensei_mp_instance->get_mailpoet_subscribers( $mp_list_id ),
					static function( $subscriber ) {
						return 'unsubscribed' !== $subscriber['status'];
					}
				);

				$students    = array_column( $students, null, 'email' );
				$subscribers = array_column( $subscribers, null, 'email' );

				$sensei_mp_instance->sync_subscribers( $students, $subscribers, $mp_list_id );
			}
		}

		return $remaining > 0;
	}
}
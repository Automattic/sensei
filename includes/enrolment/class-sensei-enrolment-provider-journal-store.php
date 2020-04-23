<?php
/**
 * File containing the class Sensei_Enrolment_Provider_Journal_Store.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is responsible for storing provider metadata like logs and status history.
 */
class Sensei_Enrolment_Provider_Journal_Store implements JsonSerializable {
	const META_ENROLMENT_PROVIDERS_JOURNAL = 'sensei_enrolment_providers_journal';

	/**
	 * Flag for if a state the store has changed.
	 *
	 * @var bool
	 */
	private $has_changed = false;

	/**
	 * Journal objects for each course and provider. The format of the array is the following:
	 * [
	 *    $course_id => [
	 *        $provider_id => Sensei_Enrolment_Provider_Journal
	 *    ]
	 * ]
	 *
	 * @var Sensei_Enrolment_Provider_Journal[][]
	 */
	private $providers_journal;

	/**
	 * User ID that this store is used for.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Keeps track of instances of this class.
	 *
	 * @var self[]
	 */
	private static $instances = [];

	/**
	 * Class constructor.
	 *
	 * @param int $user_id   User ID.
	 */
	private function __construct( $user_id ) {
		$this->user_id           = $user_id;
		$this->providers_journal = [];
	}

	/**
	 * Get a journal store record for a user/course.
	 *
	 * @param int $user_id   User ID.
	 *
	 * @return self
	 */
	private static function get( $user_id ) {
		if ( ! isset( self::$instances[ $user_id ] ) ) {
			self::$instances[ $user_id ] = new self( $user_id );

			$provider_journal_stores = get_user_meta( $user_id, self::get_provider_journal_store_meta_key(), true );
			if ( ! empty( $provider_journal_stores ) ) {
				self::$instances[ $user_id ]->restore_from_json( $provider_journal_stores );
			}
		}

		return self::$instances[ $user_id ];
	}

	/**
	 * Restore a provider journal store from a serialized JSON string.
	 *
	 * @param string $json_string JSON representation of enrolment state.
	 */
	private function restore_from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );

		if ( ! $json_arr ) {
			return;
		}

		foreach ( $json_arr as $course_id => $course_journal ) {
			foreach ( $course_journal as $provider_id => $provider_journal_data ) {
				$provider_journal = Sensei_Enrolment_Provider_Journal::from_serialized_array( $provider_journal_data );

				if ( $provider_journal ) {
					$this->providers_journal[ $course_id ][ $provider_id ] = $provider_journal;
				}
			}
		}
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->providers_journal;
	}

	/**
	 * Persist this store. If the store isn't changed, this method has no effect.
	 *
	 * @return bool
	 */
	public function save() {
		/**
		 * Enables journal storage for Sensei.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $enable_journal True to enable.
		 */
		if ( ! apply_filters( 'sensei_enable_enrolment_provider_journal', false ) ) {
			return false;
		}

		if ( ! $this->has_changed ) {
			return true;
		}

		$result = update_user_meta( $this->user_id, self::get_provider_journal_store_meta_key(), wp_slash( wp_json_encode( $this ) ) );

		if ( ! $result || is_wp_error( $result ) ) {
			return false;
		}

		$this->has_changed = false;

		return true;
	}

	/**
	 * Save all stores that need it.
	 *
	 * As this isn't a singleton, Sensei_Course_Enrolment_Manager hooks this into `shutdown` in its `init` method.
	 */
	public static function persist_all() {
		foreach ( self::$instances as $user_id => $instance ) {
			$instance->save();
		}
	}

	/**
	 * Register a possible update in the course enrolment status of a provider for a user. If there was no actual change
	 * on the status, this method has no effect. Changes start being registered only after a user is enrolled.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Results $enrolment_results The enrolment results.
	 * @param int                                      $user_id          The user which the change applies to.
	 * @param int                                      $course_id        The course which the change applies to.
	 */
	public static function register_possible_enrolment_change( $enrolment_results, $user_id, $course_id ) {
		$journal_store = self::get( $user_id );

		// Register the status of the user for the first time only if he is enrolled.
		if ( ! isset( $journal_store->providers_journal[ $course_id ] ) && ! $enrolment_results->is_enrolment_provided() ) {
			return;
		}

		// Loop through all providers to update any changes in status.
		$has_changed = false;
		foreach ( $enrolment_results->get_provider_results() as $provider_id => $is_enrolled ) {
			if ( ! isset( $journal_store->providers_journal[ $course_id ][ $provider_id ] ) ) {
				$journal_store->providers_journal[ $course_id ][ $provider_id ] = Sensei_Enrolment_Provider_Journal::create();
			}

			$has_changed = $journal_store->providers_journal[ $course_id ][ $provider_id ]->update_enrolment_status( $is_enrolled ) || $has_changed;
		}

		// Mark any removed providers as deleted in enrolment history.
		$current_snapshot = self::get_enrolment_snanpshot( $user_id, $course_id );

		$removed_providers = array_diff( array_keys( $current_snapshot ), array_keys( $enrolment_results->get_provider_results() ) );
		foreach ( $removed_providers as $removed_provider ) {
			$has_changed = $journal_store->providers_journal[ $course_id ][ $removed_provider ]->delete_enrolment_status() || $has_changed;
		}

		$journal_store->has_changed = $has_changed || $journal_store->has_changed;
	}

	/**
	 * Get a snapshot of the providers' enrolment status for a user and course at a specific timestamp. If no timestamp
	 * is provided the current snapshot is returned.
	 *
	 * @param int       $user_id     The user to return the snapshot for.
	 * @param int       $course_id   The course to return the snapshot for.
	 * @param int|float $timestamp   The timestamp of the snapshot. If omitted the current snapshot is returned.
	 *
	 * @return array
	 */
	public static function get_enrolment_snanpshot( $user_id, $course_id, $timestamp = null ) {
		$timestamp     = null === $timestamp ? microtime( true ) : (float) $timestamp;
		$journal_store = self::get( $user_id );

		$snapshot = [];

		if ( ! isset( $journal_store->providers_journal[ $course_id ] ) ) {
			return $snapshot;
		}

		foreach ( $journal_store->providers_journal[ $course_id ] as $provider_id => $journal ) {
			$status = $journal->get_status_at( $timestamp );

			if ( null !== $status['enrolment_status'] ) {
				$snapshot[ $provider_id ] = $status['enrolment_status'];
			}
		}

		return $snapshot;
	}

	/**
	 * Get the enrolment status history of a provider for a specified user and course.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider  The provider.
	 * @param int                                        $user_id   The user id.
	 * @param int                                        $course_id The course id.
	 *
	 * @return array The history of the provider. Each element of the array has the format:
	 *               [ 'timestamp' => Timestamp of the status change, 'enrolment_status' => true|false|null ]
	 */
	public static function get_provider_history( Sensei_Course_Enrolment_Provider_Interface $provider, $user_id, $course_id ) {
		$journal_store = self::get( $user_id );

		return isset( $journal_store->providers_journal[ $course_id ][ $provider->get_id() ] ) ?
			$journal_store->providers_journal[ $course_id ][ $provider->get_id() ]->get_history() :
			[];
	}

	/**
	 * Add a log message to a provider.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider  The provider.
	 * @param int                                        $user_id   The user id.
	 * @param int                                        $course_id The course id.
	 * @param string                                     $message   The message to be added.
	 */
	public static function add_provider_log_message( Sensei_Course_Enrolment_Provider_Interface $provider, $user_id, $course_id, $message ) {
		$journal_store = self::get( $user_id );

		if ( ! isset( $journal_store->providers_journal[ $course_id ][ $provider->get_id() ] ) ) {
			$journal_store->providers_journal[ $course_id ][ $provider->get_id() ] = Sensei_Enrolment_Provider_Journal::create();
		}

		$journal_store->providers_journal[ $course_id ][ $provider->get_id() ]->add_log_message( $message );
		$journal_store->has_changed = true;
	}

	/**
	 * Get the log messages of a provider.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider  The provider.
	 * @param int                                        $user_id   The user id.
	 * @param int                                        $course_id The course id.
	 *
	 * @return array The message log of the provider. Each element of the array has the format:
	 *               [ 'timestamp' => Timestamp of the message, 'message' => The actual message ]
	 */
	public static function get_provider_logs( Sensei_Course_Enrolment_Provider_Interface $provider, $user_id, $course_id ) {
		$journal_store = self::get( $user_id );

		return isset( $journal_store->providers_journal[ $course_id ][ $provider->get_id() ] ) ?
			$journal_store->providers_journal[ $course_id ][ $provider->get_id() ]->get_logs() :
			[];
	}

	/**
	 * Get the provider journal store meta key.
	 *
	 * @return string
	 */
	public static function get_provider_journal_store_meta_key() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . self::META_ENROLMENT_PROVIDERS_JOURNAL;
	}
}

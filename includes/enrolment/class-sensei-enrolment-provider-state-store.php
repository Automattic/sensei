<?php
/**
 * File containing the class Sensei_Enrolment_Provider_State_Store.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is class is responsible for managing the enrollment providers' snapshots.
 */
class Sensei_Enrolment_Provider_State_Store implements JsonSerializable {
	const SNAPSHOT_HISTORY_SIZE = 30;

	const META_PREFIX_ENROLMENT_PROVIDERS_STATE = 'sensei_enrolment_providers_state_';

	/**
	 * Flag for if a state store has changed.
	 *
	 * @var bool
	 */
	private $has_changed;

	/**
	 * The snapshots of the enrolment providers.
	 *
	 * @var Sensei_Enrolment_Provider_State_Snapshot[]
	 */
	private $snapshots;

	/**
	 * User ID that this store is used for.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Course post ID that this store is used for.
	 *
	 * @var int
	 */
	private $course_id;

	/**
	 * Keeps track of instances of this class.
	 *
	 * @var self[][]
	 */
	private static $instances = [];

	/**
	 * Class constructor.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 */
	private function __construct( $user_id, $course_id ) {
		$this->user_id   = $user_id;
		$this->course_id = $course_id;
		$this->snapshots = [];
		$this->has_changed = false;
	}

	/**
	 * Get a state store record for a user/course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return self
	 */
	private static function get( $user_id, $course_id ) {
		if ( ! isset( self::$instances[ $user_id ] ) ) {
			self::$instances[ $user_id ] = [];
		}

		if ( ! isset( self::$instances[ $user_id ][ $course_id ] ) ) {
			self::$instances[ $user_id ][ $course_id ] = new self( $user_id, $course_id );

			$provider_state_stores = get_user_meta( $user_id, self::get_providers_state_meta_key( $course_id ), true );
			if ( ! empty( $provider_state_stores ) ) {
				self::$instances[ $user_id ][ $course_id ]->restore_from_json( $provider_state_stores );
			}
		}

		return self::$instances[ $user_id ][ $course_id ];
	}

	/**
	 * Restore a provider state store from a serialized JSON string.
	 *
	 * @param string $json_string JSON representation of enrolment state.
	 */
	private function restore_from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );
		if ( ! $json_arr ) {
			return;
		}

		$this->snapshots = array_filter( array_map( 'Sensei_Enrolment_Provider_State_Snapshot::from_serialized_array', $json_arr ) );
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->snapshots;
	}

	/**
	 * Persist this store.
	 *
	 * @return bool
	 */
	public function save() {
		if ( ! $this->has_changed ) {
			return true;
		}

		$result = update_user_meta( $this->user_id, self::get_providers_state_meta_key( $this->course_id ), wp_slash( wp_json_encode( $this ) ) );

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
		foreach ( self::$instances as $user_id => $course_instances ) {
			foreach ( $course_instances as $instance ) {
				$instance->save();
			}
		}
	}

	/**
	 * Get the enrolment provider state meta key.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return string
	 */
	private static function get_providers_state_meta_key( $course_id ) {
		return self::META_PREFIX_ENROLMENT_PROVIDERS_STATE . $course_id;
	}

	/**
	 * Register a log message.
	 *
	 * @param $user_id   The user which relates to this message.
	 * @param $course_id The course which relates to this message.
	 * @param $message   The message.
	 */
	public static function register_log_message( $user_id, $course_id, $message) {
		$state_store = self::get( $user_id, $course_id );
		$new_snapshot = $state_store->get_current_snapshot()->update_message( $message );
		$state_store->add_snapshot( $new_snapshot );
	}

	/**
	 * Register a possible update in the status of a provider for a user and a course. If there was no actual change on
	 * the status, this method has no effect.
	 *
	 * @param $enrolment_provider The provider which has a status update.
	 * @param $user_id            The user which the change applies to.
	 * @param $course_id          The course which the change applies to.
	 */
	public static function register_possible_enrolment_change( Sensei_Course_Enrolment_Provider_Interface $enrolment_provider, $user_id, $course_id, $is_enrolled ) {
		$state_store = self::get( $user_id, $course_id );
		$new_snapshot = $state_store->get_current_snapshot()->update_status( $enrolment_provider->get_id(), $is_enrolled);

		if ( null !== $new_snapshot ) {
			$state_store->add_snapshot( $new_snapshot );
		}
	}

	/**
	 * Returns the current snapshot.
	 *
	 * @return Sensei_Enrolment_Provider_State_Snapshot
	 */
	private function get_current_snapshot() {
		if ( empty( $this->snapshots ) ) {
			return Sensei_Enrolment_Provider_State_Snapshot::create();
		} else {
			return $this->snapshots[0];
		}
	}

	/**
	 * Adds a new snapshot to history.
	 * @param $snapshot
	 */
	private function add_snapshot( $snapshot ) {
		$this->has_changed = true;
		array_unshift( $this->snapshots, $snapshot);
		array_splice( $this->snapshots, self::SNAPSHOT_HISTORY_SIZE );
	}
}

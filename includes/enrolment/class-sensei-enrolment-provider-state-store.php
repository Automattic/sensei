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
 * Stores set of all the enrolment provider state objects for a course and user.
 */
class Sensei_Enrolment_Provider_State_Store implements JsonSerializable {
	const HISTORY_SIZE = 30;

	const META_PREFIX_ENROLMENT_PROVIDERS_STATE = 'sensei_enrolment_providers_state_';

	/**
	 * Flag for if a state store has changed.
	 *
	 * @var bool
	 */
	private $has_changed;

	/**
	 * State objects for the providers.
	 *
	 * @var Sensei_Enrolment_Provider_State[]
	 */
	private $provider_states;

	/**
	 * The history of the status of enrolment providers.
	 *
	 * @var Sensei_Enrolment_Provider_State_Snapshot[]
	 */
	private $history;

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
		$this->user_id         = $user_id;
		$this->course_id       = $course_id;
		$this->provider_states = [];
		$this->history         = [];
		$this->has_changed     = false;
	}

	/**
	 * Get a state store record for a user/course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course post ID.
	 *
	 * @return self
	 */
	public static function get( $user_id, $course_id ) {
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

		$provider_states = [];

		if ( isset( $json_arr['s'] ) ) {

			foreach ( $json_arr['s'] as $provider_id => $provider_state_data ) {
				$provider_state_data = Sensei_Enrolment_Provider_State::from_serialized_array( $this, $provider_state_data );
				if ( ! $provider_state_data ) {
					continue;
				}

				$provider_states[ $provider_id ] = $provider_state_data;
			}
		}

		$this->provider_states = $provider_states;

		if ( isset( $json_arr['h'] ) ) {
			$this->history = array_filter( array_map( 'Sensei_Enrolment_Provider_State_Snapshot::from_serialized_array', $json_arr['h'] ) );
		}
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			's' => $this->provider_states,
			'h' => $this->history,
		];
	}

	/**
	 * Get the state object for a provider.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider Provider object.
	 *
	 * @return Sensei_Enrolment_Provider_State
	 */
	public function get_provider_state( Sensei_Course_Enrolment_Provider_Interface $provider ) {
		$provider_id = $provider->get_id();

		if ( ! isset( $this->provider_states[ $provider_id ] ) ) {
			$this->provider_states[ $provider_id ] = Sensei_Enrolment_Provider_State::create( $this );
		}

		return $this->provider_states[ $provider_id ];
	}

	/**
	 * Mark a state store as changed so it is stored in the database.
	 *
	 * @param bool $has_changed True if provider states have changed.
	 */
	public function set_has_changed( $has_changed ) {
		$this->has_changed = (bool) $has_changed;
	}

	/**
	 * Get if a provider state store has changed and if it needs to be updated in the database.
	 */
	public function get_has_changed() {
		return $this->has_changed;
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
	 * Register a possible update in the status of a provider for a user and a course. If there was no actual change on
	 * the status, this method has no effect.
	 *
	 * @param array $provider_results An array with the format 'provider_id' => enrollment result.
	 * @param int   $user_id          The user which the change applies to.
	 * @param int   $course_id        The course which the change applies to.
	 */
	public static function register_possible_enrolment_change( $provider_results, $user_id, $course_id ) {
		$state_store = self::get( $user_id, $course_id );

		$new_snapshot = Sensei_Enrolment_Provider_State_Snapshot::duplicate( $state_store->get_current_snapshot() );
		$has_changed  = false;

		foreach ( $provider_results as $provider_id => $is_enrolled ) {
			$has_changed = $new_snapshot->update_status( $provider_id, $is_enrolled ) || $has_changed;
		}

		if ( $has_changed ) {
			$state_store->add_snapshot( $new_snapshot );
		}
	}

	/**
	 * Returns the current snapshot from history.
	 *
	 * @return Sensei_Enrolment_Provider_State_Snapshot
	 */
	private function get_current_snapshot() {
		if ( empty( $this->history ) ) {
			return Sensei_Enrolment_Provider_State_Snapshot::create();
		} else {
			return $this->history[0];
		}
	}

	/**
	 * Adds a new snapshot entry to the history.
	 *
	 * @param Sensei_Enrolment_Provider_State_Snapshot $snapshot The snapshot to add.
	 */
	private function add_snapshot( $snapshot ) {

		/**
		 * Filter the maximum amount of historical entries that are going to be stored for each user and course.
		 *
		 * @since 3.0.0
		 *
		 * @param int  $history_size Default history size.
		 */
		$history_size = apply_filters( 'sensei_enrolment_history_size', self::HISTORY_SIZE );

		$this->has_changed = true;
		array_unshift( $this->history, $snapshot );
		array_splice( $this->history, $history_size );
	}
}

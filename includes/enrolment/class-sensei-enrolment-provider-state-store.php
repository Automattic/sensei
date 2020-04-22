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
 * Stores set of all the enrolment provider state objects for all courses for a user.
 */
class Sensei_Enrolment_Provider_State_Store implements JsonSerializable {
	const META_ENROLMENT_PROVIDERS_STATE = 'sensei_enrolment_providers_state';

	/**
	 * Flag for if a state store has changed.
	 *
	 * @var bool
	 */
	private $has_changed = false;

	/**
	 * State objects for the providers.
	 *
	 * @var Sensei_Enrolment_Provider_State[][]
	 */
	private $provider_states = [];

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
	 * @param int $user_id User ID.
	 */
	private function __construct( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * Get a state store record for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return self
	 */
	public static function get( $user_id ) {
		if ( ! isset( self::$instances[ $user_id ] ) ) {
			self::$instances[ $user_id ] = new self( $user_id );

			$provider_state_stores = get_user_meta( $user_id, self::get_provider_state_store_meta_key(), true );
			if ( ! empty( $provider_state_stores ) ) {
				self::$instances[ $user_id ]->restore_from_json( $provider_state_stores );
			}
		}

		return self::$instances[ $user_id ];
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
		foreach ( $json_arr as $course_id => $providers ) {
			if ( ! is_numeric( $course_id ) ) {
				continue;
			}

			$course_id                     = (string) $course_id;
			$provider_states[ $course_id ] = [];

			foreach ( $providers as $provider_id => $provider_state_data ) {
				$provider_state_data = Sensei_Enrolment_Provider_State::from_array( $this, $provider_state_data );
				if ( ! $provider_state_data ) {
					continue;
				}

				$provider_states[ $course_id ][ $provider_id ] = $provider_state_data;
			}
		}

		$this->set_provider_states( $provider_states );
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		$course_states = $this->provider_states;

		foreach ( $course_states as $course_id => $provider_states ) {
			foreach ( $provider_states as $provider_id => $provider_state ) {
				if ( ! $provider_state->has_data() ) {
					unset( $course_states[ $course_id ][ $provider_id ] );
				}
			}

			if ( empty( $course_states[ $course_id ] ) ) {
				unset( $course_states[ $course_id ] );
			}
		}

		return $course_states;
	}

	/**
	 * Get the user ID.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Set the provider states.
	 *
	 * @param Sensei_Enrolment_Provider_State[] $provider_states State objects for all providers.
	 */
	private function set_provider_states( $provider_states ) {
		$this->provider_states = $provider_states;
	}

	/**
	 * Get the state object for a provider.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider  Provider object.
	 * @param int                                        $course_id Course post ID.
	 *
	 * @return Sensei_Enrolment_Provider_State
	 */
	public function get_provider_state( Sensei_Course_Enrolment_Provider_Interface $provider, $course_id ) {
		$provider_id = $provider->get_id();

		$course_id = (string) $course_id;

		if ( ! isset( $this->provider_states[ $course_id ] ) ) {
			$this->provider_states[ $course_id ] = [];
		}

		if ( ! isset( $this->provider_states[ $course_id ][ $provider_id ] ) ) {
			$this->provider_states[ $course_id ][ $provider_id ] = Sensei_Enrolment_Provider_State::create( $this );
		}

		return $this->provider_states[ $course_id ][ $provider_id ];
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
		if ( ! $this->get_has_changed() ) {
			return true;
		}

		$result = update_user_meta( $this->get_user_id(), self::get_provider_state_store_meta_key(), wp_slash( wp_json_encode( $this ) ) );

		if ( ! $result || is_wp_error( $result ) ) {
			return false;
		}

		$this->set_has_changed( false );

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
	 * Get the provider state store meta key.
	 *
	 * @return string
	 */
	public static function get_provider_state_store_meta_key() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . self::META_ENROLMENT_PROVIDERS_STATE;
	}
}

<?php
/**
 * File containing the class Sensei_Course_Enrolment_Provider_State_Set.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores set of all the enrolment provider state objects for a course and user.
 */
class Sensei_Course_Enrolment_Provider_State_Store implements JsonSerializable {
	/**
	 * Flag for if a state set has changed.
	 *
	 * @var bool
	 */
	private $has_changed = false;

	/**
	 * State objects for the providers.
	 *
	 * @var Sensei_Course_Enrolment_Provider_State[]
	 */
	private $provider_states = [];

	/**
	 * Class constructor.
	 */
	private function __construct() {}

	/**
	 * Restore a provider state set from a serialized JSON string.
	 *
	 * @param string $json_string JSON representation of enrolment state.
	 *
	 * @return self|false
	 */
	public static function from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );
		if ( ! $json_arr ) {
			return false;
		}

		$self = new self();

		$provider_states = [];
		foreach ( $json_arr as $provider_id => $provider_state_data ) {
			$provider_state_data = Sensei_Course_Enrolment_Provider_State::from_serialized_array( $self, $provider_state_data );
			if ( ! $provider_state_data ) {
				continue;
			}

			$provider_states[ $provider_id ] = $provider_state_data;
		}

		$self->set_provider_states( $provider_states );

		return $self;
	}

	/**
	 * Create a fresh state set record.
	 *
	 * @return self
	 */
	public static function create() {
		return new self();
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->provider_states;
	}

	/**
	 * Set the provider states.
	 *
	 * @param Sensei_Course_Enrolment_Provider_State[] $provider_states State objects for all providers.
	 */
	private function set_provider_states( $provider_states ) {
		$this->provider_states = $provider_states;
	}

	/**
	 * Get the state object for a provider.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $provider Provider object.
	 *
	 * @return Sensei_Course_Enrolment_Provider_State
	 */
	public function get_provider_state( Sensei_Course_Enrolment_Provider_Interface $provider ) {
		$provider_id = $provider->get_id();

		if ( ! isset( $this->provider_states[ $provider_id ] ) ) {
			$this->provider_states[ $provider_id ] = Sensei_Course_Enrolment_Provider_State::create( $this );
		}

		return $this->provider_states[ $provider_id ];
	}

	/**
	 * Mark a state set as changed so it is stored in the database.
	 *
	 * @param bool $has_changed True if provider states have changed.
	 */
	public function set_has_changed( $has_changed ) {
		$this->has_changed = (bool) $has_changed;
	}

	/**
	 * Get if a provider state set has changed and if it needs to be updated in the database.
	 */
	public function get_has_changed() {
		return $this->has_changed;
	}
}

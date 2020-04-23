<?php
/**
 * File containing the class Sensei_Enrolment_Provider_State.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the state for a course enrolment provider.
 */
class Sensei_Enrolment_Provider_State implements JsonSerializable {
	/**
	 * State store storing this provider state.
	 *
	 * @var Sensei_Enrolment_Provider_State_Store
	 */
	private $state_store;

	/**
	 * Provider data storage.
	 *
	 * @var array
	 */
	private $provider_data = [];

	/**
	 * Class constructor.
	 *
	 * @param Sensei_Enrolment_Provider_State_Store $state_store      State store storing this provider state.
	 * @param array                                 $provider_data    Basic storage for provider data.
	 */
	private function __construct( Sensei_Enrolment_Provider_State_Store $state_store, $provider_data = [] ) {
		$this->state_store   = $state_store;
		$this->provider_data = $provider_data;
	}

	/**
	 * Restore a course enrolment state record from data restored from a serialized JSON string.
	 *
	 * @param Sensei_Enrolment_Provider_State_Store $state_store State store storing this provider state object.
	 * @param array                                 $data        Data to initialize object from.
	 *
	 * @return self|false
	 */
	public static function from_array( Sensei_Enrolment_Provider_State_Store $state_store, $data ) {
		$provider_data = array_map( [ __CLASS__, 'sanitize_data' ], $data );
		$provider_data = array_filter( $provider_data, [ __CLASS__, 'filter_null_values' ] );

		return new self( $state_store, $provider_data );
	}

	/**
	 * Helper method to filter out null values.
	 *
	 * @param mixed $value Value to filter.
	 *
	 * @return bool
	 */
	private static function filter_null_values( $value ) {
		return null !== $value;
	}

	/**
	 * Create a fresh state storage record.
	 *
	 * @param Sensei_Enrolment_Provider_State_Store $state_store State store storing this provider state object.
	 *
	 * @return self
	 */
	public static function create( Sensei_Enrolment_Provider_State_Store $state_store ) {
		return new self( $state_store );
	}

	/**
	 * Sanitize data value from provider storage.
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return mixed
	 */
	private static function sanitize_data( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ __CLASS__, 'sanitize_data' ], $value );
		}

		// These values are considered safe.
		if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) ) {
			return $value;
		}

		// We don't allow object storage.
		if ( is_object( $value ) ) {
			return null;
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->provider_data;
	}

	/**
	 * Get a value in the provider's storage.
	 *
	 * @param string $key Key for the value to retrieve.
	 *
	 * @return mixed
	 */
	public function get_stored_value( $key ) {
		if ( ! isset( $this->provider_data[ $key ] ) ) {
			return null;
		}

		return $this->provider_data[ $key ];
	}

	/**
	 * Set a value in the provider's storage.
	 *
	 * @param string $key   Key for the value to set.
	 * @param mixed  $value Value to set.
	 */
	public function set_stored_value( $key, $value ) {
		if (
			! isset( $this->provider_data[ $key ] )
			|| $value !== $this->provider_data[ $key ]
		) {
			$this->state_store->set_has_changed( true );
		}

		if ( null === $value ) {
			unset( $this->provider_data[ $key ] );
		} else {
			$this->provider_data[ $key ] = self::sanitize_data( $value );
		}
	}

	/**
	 * Immediately persist a provider state. If you don't immediately need a change to be saved, you don't need
	 * to call this method. It will automatically be stored during `shutdown`.
	 *
	 * @see Sensei_Enrolment_Provider_State_Store::persist_all()
	 *
	 * @return bool
	 */
	public function save() {
		return $this->state_store->save();
	}

	/**
	 * Checks if there is any data in the state.
	 *
	 * @return bool
	 */
	public function has_data() {
		return ! empty( $this->provider_data );
	}
}

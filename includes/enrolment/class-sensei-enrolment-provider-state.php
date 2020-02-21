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
	const MAX_LOG_ENTRIES = 30;

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
	 * Log messages.
	 *
	 * @var array
	 */
	private $logs = [];

	/**
	 * Class constructor.
	 *
	 * @param Sensei_Enrolment_Provider_State_Store $state_store      State store storing this provider state.
	 * @param array                                 $provider_data    Basic storage for provider data.
	 * @param array                                 $logs             Log messages.
	 */
	private function __construct( Sensei_Enrolment_Provider_State_Store $state_store, $provider_data = [], $logs = [] ) {
		$this->state_store   = $state_store;
		$this->provider_data = $provider_data;
		$this->logs          = $logs;
	}

	/**
	 * Restore a course enrolment state record from data restored from a serialized JSON string.
	 *
	 * @param Sensei_Enrolment_Provider_State_Store $state_store State store storing this provider state object.
	 * @param array                                 $data        Serialized state of object.
	 *
	 * @return self|false
	 */
	public static function from_serialized_array( Sensei_Enrolment_Provider_State_Store $state_store, $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		$provider_data = isset( $data['d'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_data' ], $data['d'] ) ) : [];
		$logs          = isset( $data['l'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_logs' ], $data['l'] ) ) : [];

		return new self( $state_store, $provider_data, $logs );
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
	 * Sanitize a log entry.
	 *
	 * @param  array $log_entry Non-sanitized log entry.
	 *
	 * @return array
	 */
	private static function sanitize_logs( $log_entry ) {
		if ( ! is_array( $log_entry ) ) {
			return null;
		}

		if ( 2 !== count( $log_entry ) ) {
			return null;
		}

		$log_entry[0] = intval( $log_entry[0] );
		$log_entry[1] = sanitize_text_field( $log_entry[1] );

		return $log_entry;
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
		return [
			'd' => $this->provider_data,
			'l' => $this->logs,
		];
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
	 * Log a message for a provider.
	 *
	 * @param string $message  Message to log.
	 */
	public function add_log_message( $message ) {
		$this->logs[] = [
			time(),
			sanitize_text_field( $message ),
		];

		if ( count( $this->logs ) > self::MAX_LOG_ENTRIES ) {
			// Take the last `self::MAX_LOG_ENTRIES` entries.
			$this->logs = array_slice( $this->logs, -1 * self::MAX_LOG_ENTRIES );
		}

		$this->state_store->set_has_changed( true );
	}

	/**
	 * Get the log messages ordered by time (descending; oldest first).
	 *
	 * @return array {
	 *     @var $0 Time for the log entry.
	 *     @var $1 Log message.
	 * }
	 */
	public function get_logs() {
		return $this->logs;
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
}

<?php
/**
 * File containing the class Sensei_Course_Enrolment_Provider_State.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the state for a course enrolment provider.
 */
class Sensei_Course_Enrolment_Provider_State implements JsonSerializable {
	/**
	 * State set storing this provider state.
	 *
	 * @var Sensei_Course_Enrolment_Provider_State_Set
	 */
	private $state_set;

	/**
	 * Course enrolment state.
	 *
	 * @var bool
	 */
	private $enrolment_status;

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
	 * @param Sensei_Course_Enrolment_Provider_State_Set $state_set        State set storing this provider state.
	 * @param array                                      $provider_data    Basic storage for provider data.
	 * @param array                                      $logs             Log messages.
	 */
	private function __construct( \Sensei_Course_Enrolment_Provider_State_Set $state_set, $provider_data = [], $logs = [] ) {
		$this->state_set     = $state_set;
		$this->provider_data = $provider_data;
		$this->logs          = $logs;
	}

	/**
	 * Restore a course enrolment state record from data restored from a serialized JSON string.
	 *
	 * @param \Sensei_Course_Enrolment_Provider_State_Set $state_set State set storing this provider state object.
	 * @param array                                       $data      Serialized state of object.
	 *
	 * @return self|false
	 */
	public static function from_serialized_array( \Sensei_Course_Enrolment_Provider_State_Set $state_set, $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		$provider_data = isset( $data['d'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_data' ], $data['d'] ) ) : [];
		$logs          = isset( $data['l'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_logs' ], $data['l'] ) ) : [];

		return new self( $state_set, $provider_data, $logs );
	}

	/**
	 * Create a fresh state storage record.
	 *
	 * @param \Sensei_Course_Enrolment_Provider_State_Set $state_set State set storing this provider state object.
	 *
	 * @return self
	 */
	public static function create( Sensei_Course_Enrolment_Provider_State_Set $state_set ) {
		return new self( $state_set );
	}

	/**
	 * Sanitize the logs.
	 *
	 * @param  array $logs Non-sanitized log entries.
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

		$log_entry[0] = floatval( $log_entry[0] );
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
	 * @param string $value Value to set.
	 */
	public function set_stored_value( $key, $value ) {
		if (
			! isset( $this->provider_data[ $key ] )
			|| $value !== $this->provider_data[ $key ]
		) {
			$this->state_set->set_has_changed( true );
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
	public function log_message( $message ) {
		$this->logs[] = [
			microtime( true ),
			sanitize_text_field( $message ),
		];

		$this->state_set->set_has_changed( true );
	}

	/**
	 * Get the log messages.
	 *
	 * @return array {
	 *     @var $0 Microtime for the log entry.
	 *     @var $1 Log message.
	 * }
	 */
	public function get_logs() {
		return $this->logs;
	}
}

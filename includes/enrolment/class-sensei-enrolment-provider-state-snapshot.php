<?php
/**
 * File containing the class Sensei_Enrolment_Provider_State_Snapshot.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores a snapshot of the state of all enrolment providers.
 */
class Sensei_Enrolment_Provider_State_Snapshot implements JsonSerializable {

	/**
	 * Timestap of the snapshot.
	 *
	 * @var int
	 */
	private $timestamp;

	/**
	 * An array of the status of all enrolment providers.
	 *
	 * [
	 *      'provider_id' => [ 'enrolment_status' => true|false ]
	 * ]
	 *
	 * @var array
	 */
	private $providers_status;

	/**
	 * Log message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Class constructor.
	 *
	 * @param array  $providers_status    The providers' status.
	 * @param array  $message             Log message.
	 */
	private function __construct( $providers_status, $message ) {
		$this->timestamp        = time();
		$this->providers_status = $providers_status;
		$this->message          = $message;
	}

	/**
	 * Restore a snapshot from a JSON string.
	 *
	 * @param array $data        Serialized state of object.
	 *
	 * @return self|false
	 */
	public static function from_serialized_array( $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		$timestamp        = isset( $data['timestamp'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_data' ], $data['timestamp'] ) ) : [];
		$providers_status = isset( $data['providers'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_data' ], $data['providers'] ) ) : [];
		$message          = isset( $data['message'] ) ? array_filter( array_map( [ __CLASS__, 'sanitize_data' ], $data['message'] ) ) : [];

		return new self( $timestamp, $providers_status, $message );
	}

	/**
	 * Create a fresh snapshot.
	 *
	 * @param array  $providers_status    The providers' status.
	 * @param array  $message             Log message.
	 *
	 * @return self
	 */
	public static function create( $provider_status = [], $message = '') {
		return new self( $provider_status, $message );
	}


	/**
	 * Sanitize data from the JSON string.
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
			'timestamp' => $this->timestamp,
			'providers' => $this->providers_status,
			'message'   => $this->message,
		];
	}

	/**
	 * Create a new snapshot by updating the message.
	 *
	 * @param $message The message
	 *
	 * @return Sensei_Enrolment_Provider_State_Snapshot
	 */
	public function update_message( $message ) {
		return new self( $this->providers_status, $message );
	}

	/**
	 * Create a new snapshot by updating a provider's status and deleting any existing message. If there is no update
	 * on the status null is returned.
	 *
	 * @param $provider_id The provider to update the status for.
	 *
	 * @return Sensei_Enrolment_Provider_State_Snapshot|null
	 */
	public function update_status( $provider_id, $is_enrolled ) {
		$current_status = $this->providers_status;

		if ( isset( $current_status[ $provider_id ][ 'enrolment_status' ] ) && $is_enrolled === $current_status[ $provider_id ][ 'enrolment_status' ] ) {
			return null;
		}

		$current_status[ $provider_id ][ 'enrolment_status' ] = $is_enrolled;

		return new self( $current_status, '' );
	}
}

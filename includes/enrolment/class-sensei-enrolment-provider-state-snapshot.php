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
 * A snapshot of the state of all enrolment providers.
 */
class Sensei_Enrolment_Provider_State_Snapshot implements JsonSerializable {

	/**
	 * Timestamp of the snapshot.
	 *
	 * @var int
	 */
	private $timestamp;

	/**
	 * An array of the status of all enrolment providers.
	 *
	 * [
	 *      'provider_id' => [ 'es' => true|false ]
	 * ]
	 *
	 * @var array
	 */
	private $providers_status;

	/**
	 * Class constructor.
	 *
	 * @param array   $providers_status The providers' status.
	 * @param integer $timestamp        The timestamp of the snapshot.
	 */
	private function __construct( $providers_status, $timestamp = null ) {
		$this->timestamp        = null === $timestamp ? time() : $timestamp;
		$this->providers_status = $providers_status;
	}

	/**
	 * Restore a snapshot from a JSON string.
	 *
	 * @param array $data Serialized state of object.
	 *
	 * @return self|false
	 */
	public static function from_serialized_array( $data ) {

		if ( empty( $data ) ) {
			return false;
		}

		return new self( $data['p'], $data['t'] );
	}

	/**
	 * Create an empty snapshot.
	 *
	 * @param array $providers_status The providers' status.
	 *
	 * @return self
	 */
	public static function create( $providers_status = [] ) {
		return new self( $providers_status );
	}

	/**
	 * Create a duplicate of a snapshot.
	 *
	 * @param Sensei_Enrolment_Provider_State_Snapshot $other The snapshot to copy.
	 *
	 * @return self
	 */
	public static function duplicate( Sensei_Enrolment_Provider_State_Snapshot $other ) {
		return new self( $other->providers_status );
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			't' => $this->timestamp,
			'p' => $this->providers_status,
		];
	}

	/**
	 * Update the status of a provider.
	 *
	 * @param string $provider_id The provider to update the status for.
	 * @param bool   $is_enrolled The enrolment status.
	 *
	 * @return bool True if the value was actually updated. False otherwise.
	 */
	public function update_status( $provider_id, $is_enrolled ) {

		if ( isset( $this->providers_status[ $provider_id ]['es'] ) && $is_enrolled === $this->providers_status[ $provider_id ]['es'] ) {
			return false;
		}

		$this->providers_status[ $provider_id ]['es'] = $is_enrolled;

		return true;
	}

	/**
	 * Set the active providers of the snapshot
	 *
	 * @param array $active_provider_ids The active providers id.
	 *
	 * @return bool True if the providers were actually updated. False otherwise.
	 */
	public function set_active_providers( $active_provider_ids ) {

		$removed_providers = array_diff( array_keys( $this->providers_status ), $active_provider_ids );

		if ( empty( $removed_providers ) ) {
			return false;
		}

		foreach ( $removed_providers as $index => $provider_id ) {
			unset( $this->providers_status[ $provider_id ] );
		}

		return true;
	}
}

<?php
/**
 * File containing the class Sensei_Enrolment_Provider_Journal.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class represents a journal for a single provider.
 */
class Sensei_Enrolment_Provider_Journal implements JsonSerializable {
	const DEFAULT_HISTORY_SIZE        = 30;
	const DEFAULT_MESSAGE_LOG_SIZE    = 30;
	const HISTORY_TIMESTAMP_PRECISION = 0.001;

	/**
	 * The history of the provider's status. A null enrolment status marks a deletion of a provider. Each element of
	 * the array has the format:
	 *     [
	 *         'timestamp' => Timestamp of the status change,
	 *          'enrolment_status' => true|false|null
	 *     ]
	 *
	 * @var array
	 */
	private $history;

	/**
	 * The message log of the provider. Each element of the array has the format:
	 *     [
	 *         'timestamp' => Timestamp of the message,
	 *          'message' => The actual message
	 *     ]
	 *
	 * @var array
	 */
	private $message_log;

	/**
	 * Class constructor.
	 *
	 * @param array $history     The status history.
	 * @param array $message_log The message log.
	 */
	private function __construct( $history, $message_log ) {
		$this->history     = $history;
		$this->message_log = $message_log;
	}

	/**
	 * Restore a journal from a JSON string.
	 *
	 * @param array $data Serialized state of object.
	 *
	 * @return self|false
	 */
	public static function from_serialized_array( $data ) {

		if ( empty( $data ) ) {
			return false;
		}

		$history     = isset( $data['h'] ) ? array_filter( array_map( [ __CLASS__, 'deserialize_history_entry' ], $data['h'] ) ) : [];
		$message_log = isset( $data['l'] ) ? array_filter( array_map( [ __CLASS__, 'deserialize_log_entry' ], $data['l'] ) ) : [];

		return new self( $history, $message_log );
	}

	/**
	 * Create an empty journal.
	 *
	 * @return self
	 */
	public static function create() {
		return new self( [], [] );
	}

	/**
	 * Sanitize a log entry.
	 *
	 * @param  array $log_entry Non-sanitized log entry.
	 *
	 * @return array
	 */
	private static function deserialize_log_entry( $log_entry ) {
		if ( ! is_array( $log_entry ) ) {
			return false;
		}

		if ( 2 !== count( $log_entry ) ) {
			return false;
		}

		if ( ! isset( $log_entry['t'], $log_entry['m'] ) ) {
			return false;
		}

		return [
			'timestamp' => (int) $log_entry['t'],
			'message'   => sanitize_text_field( $log_entry['m'] ),
		];
	}

	/**
	 * Helper method to deserialize a history entry.
	 *
	 * @param array $entry The serialized history entry.
	 *
	 * @return array|bool
	 */
	private static function deserialize_history_entry( $entry ) {
		if ( ! isset( $entry['t'] ) || ! array_key_exists( 's', $entry ) ) {
			return false;
		}

		return [
			'timestamp'        => (float) $entry['t'],
			'enrolment_status' => null === $entry['s'] ? null : (bool) $entry['s'],
		];
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		$history     = array_map( [ __CLASS__, 'serialize_history_entry' ], $this->history );
		$message_log = array_map( [ __CLASS__, 'serialize_log_entry' ], $this->message_log );

		$result = [];

		if ( ! empty( $history ) ) {
			$result['h'] = $history;
		}

		if ( ! empty( $message_log ) ) {
			$result['l'] = $message_log;
		}

		return $result;
	}

	/**
	 * Helper method to serialize a history entry.
	 *
	 * @param array $entry The deserialized history entry.
	 *
	 * @return array
	 */
	private function serialize_history_entry( $entry ) {
		return [
			't' => $entry['timestamp'],
			's' => $entry['enrolment_status'],
		];
	}

	/**
	 * Helper method to serialize a message log entry.
	 *
	 * @param array $entry The serialized log entry.
	 *
	 * @return array
	 */
	private function serialize_log_entry( $entry ) {
		return [
			't' => $entry['timestamp'],
			'm' => $entry['message'],
		];
	}

	/**
	 * Adds a message to the log.
	 *
	 * @param string $message The message.
	 */
	public function add_log_message( $message ) {
		/**
		 * Filter the maximum amount of log messages that are going to be stored for each user, course and provider.
		 *
		 * @since 3.0.0
		 *
		 * @param int  $message_log_size Default message log size.
		 */
		$message_log_size = apply_filters( 'sensei_enrolment_message_log_size', self::DEFAULT_MESSAGE_LOG_SIZE );

		$message_entry = [
			'timestamp' => time(),
			'message'   => sanitize_text_field( $message ),
		];
		array_unshift( $this->message_log, $message_entry );
		array_splice( $this->message_log, $message_log_size );
	}

	/**
	 * Update the current enrolment status. If the status is not changed, this method has no effect.
	 *
	 * @param bool $enrolment_status The enrolment status.
	 *
	 * @return bool True if there was an update, false otherwise.
	 */
	public function update_enrolment_status( $enrolment_status ) {

		if ( empty( $this->history ) || $this->history[0]['enrolment_status'] !== $enrolment_status ) {
			$this->add_status(
				[
					'timestamp'        => microtime( true ),
					'enrolment_status' => $enrolment_status,
				]
			);

			return true;
		}

		return false;
	}

	/**
	 * Delete the enrolment status. A deleted enrolment status is marked by null.
	 *
	 * @return bool True if there was a deletion, false otherwise.
	 */
	public function delete_enrolment_status() {
		if ( ! empty( $this->history ) && null !== $this->history[0]['enrolment_status'] ) {
			$this->add_status(
				[
					'timestamp'        => microtime( true ),
					'enrolment_status' => null,
				]
			);

			return true;
		}

		return false;
	}

	/**
	 * Get the enrolment status at a specified timestamp.
	 *
	 * @param int $timestamp The timestamp to retrieve the status for.
	 *
	 * @return array The historical status entry.
	 */
	public function get_status_at( $timestamp ) {

		foreach ( $this->history as $status ) {
			if ( $status['timestamp'] < $timestamp || abs( $status['timestamp'] - $timestamp ) < self::HISTORY_TIMESTAMP_PRECISION ) {
				return $status;
			}
		}

		return [
			'timestamp'        => $timestamp,
			'enrolment_status' => null,
		];
	}

	/**
	 * Get the status history.
	 *
	 * @return array
	 */
	public function get_history() {
		return $this->history;
	}

	/**
	 * Get the message log.
	 *
	 * @return array
	 */
	public function get_logs() {
		return $this->message_log;
	}

	/**
	 * Helper method to add a status entry to the history.
	 *
	 * @param array $status The history entry.
	 */
	private function add_status( $status ) {
		/**
		 * Filter the maximum amount of historical entries that are going to be stored for each user, course and provider.
		 *
		 * @since 3.0.0
		 *
		 * @param int  $history_size Default history size.
		 */
		$history_size = apply_filters( 'sensei_enrolment_history_size', self::DEFAULT_HISTORY_SIZE );

		array_unshift( $this->history, $status );
		array_splice( $this->history, $history_size );
	}
}

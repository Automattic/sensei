<?php
/**
 * File containing the class Sensei_Course_Access_Log.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores a log entry for course access checks.
 */
final class Sensei_Course_Access_Log implements JsonSerializable {
	/**
	 * Records access log records.
	 *
	 * @var array
	 */
	private $access_log_results = [];

	/**
	 * Time the log was created.
	 *
	 * @var float
	 */
	private $time;

	/**
	 * Version of access providers. This is a hash of all the access provider versions.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Sensei_Course_Access_Log constructor.
	 *
	 * @param float  $time       Time this log entry was started.
	 * @param array  $access_log Access log result.
	 * @param string $version    Version of the access providers that produced this access result.
	 */
	private function __construct( $time, $access_log = [], $version = null ) {
		$this->time               = $time;
		$this->access_log_results = $access_log;
		$this->version            = $version;
	}

	/**
	 * Start a new access log.
	 *
	 * @return Sensei_Course_Access_Log
	 */
	public static function create() {
		return new self( microtime( true ) );
	}

	/**
	 * Restore a course access log from a serialized JSON string.
	 *
	 * @param string $json_string JSON representation of log.
	 * @return Sensei_Course_Access_Log|bool
	 */
	public static function from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );
		if ( ! $json_arr ) {
			return false;
		}

		$time       = isset( $json_arr['t'] ) ? floatval( $json_arr['t'] ) : microtime( true );
		$version    = isset( $json_arr['v'] ) ? sanitize_text_field( $json_arr['v'] ) : -1;
		$access_log = isset( $json_arr['l'] ) ? array_map( 'intval', $json_arr['l'] ) : [];

		return new self( $time, $access_log, $version );
	}

	/**
	 * Return object that can be serialized by `json_encode()`.
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			't' => $this->time,
			'v' => $this->get_version(),
			'l' => $this->access_log_results,
		];
	}

	/**
	 * Records access check into log.
	 *
	 * @param string $access_provider_id Access provider identifier.
	 * @param int    $result             Result of check. See return for `\Sensei_Course_Access_Provider_Interface::has_access`.
	 */
	public function record_access_check( $access_provider_id, $result ) {
		$this->access_log_results[ $access_provider_id ] = $result;
	}

	/**
	 * Get the access log result.
	 *
	 * @return array
	 */
	public function get_access_log_results() {
		return $this->access_log_results;
	}

	/**
	 * Returns the result of all the access checks.
	 *
	 * @return bool|null
	 */
	public function has_access() {
		$access_log_results = $this->get_access_log_results();

		if ( in_array( true, $access_log_results, true ) ) {
			return true;
		}

		if ( in_array( false, $access_log_results, true ) ) {
			return false;
		}

		return null;
	}

	/**
	 * Get the version of the access providers at the time this log was made.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Run once all the access providers have been checked.
	 */
	public function finalize_log() {
		if ( ! isset( $this->version ) ) {
			$access_log    = $this->get_access_log_results();
			$this->version = Sensei_Course_Access::hash_course_access_provider_versions( array_keys( $access_log ) );
		}
	}
}

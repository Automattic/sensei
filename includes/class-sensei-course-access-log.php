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
 * Stores a log entry for course access checks between a particular user and course.
 */
final class Sensei_Course_Access_Log implements JsonSerializable {
	/**
	 * Access check results from providers.
	 *
	 * @var array
	 */
	private $provider_access = [];

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
	 * @param array  $provider_access Course access check results from all providers.
	 * @param string $version         Version of the access providers that produced this access log.
	 * @param float  $time            Time this log entry was started.
	 */
	public function __construct( $provider_access, $version, $time = null ) {
		$this->provider_access = $provider_access;
		$this->version         = $version;
		$this->time            = isset( $time ) ? $time : microtime( true );
	}

	/**
	 * Restore a course access log from a serialized JSON string.
	 *
	 * @param string $json_string JSON representation of log.
	 *
	 * @return Sensei_Course_Access_Log|bool
	 */
	public static function from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );
		if ( ! $json_arr ) {
			return false;
		}

		$provider_access = isset( $json_arr['a'] ) ? array_map( 'boolval', $json_arr['a'] ) : [];
		$version         = isset( $json_arr['v'] ) ? sanitize_text_field( $json_arr['v'] ) : -1;
		$time            = isset( $json_arr['t'] ) ? floatval( $json_arr['t'] ) : null;

		return new self( $provider_access, $version, $time );
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
			'a' => $this->provider_access,
		];
	}

	/**
	 * Get the access check results from all providers.
	 *
	 * @return array
	 */
	public function get_provider_access() {
		return $this->provider_access;
	}

	/**
	 * Returns the result of all the access checks. Used by `Sensei_Course_Access::has_access()`, do not call directly.
	 *
	 * @access private
	 *
	 * @return bool|null
	 */
	public function is_access_provided() {
		$access_record_results = $this->get_provider_access();

		// If one provider is granting access, they have access to the course.
		if ( in_array( true, $access_record_results, true ) ) {
			return true;
		}

		// If no provider granted access and they have one provider blocking access, they DO NOT have access to the course.
		if ( in_array( false, $access_record_results, true ) ) {
			return false;
		}

		// @todo This will just return false once we add the manual access provider.
		// If there are no providers, return `null` and let Sensei use its default access check.
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
	 * Get the time this access log was generated.
	 *
	 * @return string
	 */
	public function get_time() {
		return $this->time;
	}
}

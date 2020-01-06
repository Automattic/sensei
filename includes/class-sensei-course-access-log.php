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
 * Stores a log entry for course access checks for a particular user and course.
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
	 * @param float  $time            Time this log entry was started.
	 * @param array  $provider_access Course access check results from all providers.
	 * @param string $version         Version of the access providers that produced this access log.
	 */
	private function __construct( $time, $provider_access = [], $version = null ) {
		$this->time            = $time;
		$this->provider_access = $provider_access;
		$this->version         = $version;
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
	 * Sanitizes the values loaded into `self::$provider_access`.
	 *
	 * @param mixed $result Un-sanitized access result.
	 * @return null|int
	 */
	public static function sanitize_access_result( $result ) {
		if ( null === $result ) {
			return null;
		}

		return intval( $result );
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

		$time            = isset( $json_arr['t'] ) ? floatval( $json_arr['t'] ) : microtime( true );
		$version         = isset( $json_arr['v'] ) ? sanitize_text_field( $json_arr['v'] ) : -1;
		$provider_access = isset( $json_arr['a'] ) ? array_map( [ __CLASS__, 'sanitize_access_result' ], $json_arr['a'] ) : [];

		return new self( $time, $provider_access, $version );
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
	 * Records provider access check result.
	 *
	 * @param string $access_provider_id Access provider identifier.
	 * @param int    $result             Result of check. See return for `\Sensei_Course_Access_Provider_Interface::has_access`.
	 */
	public function record_access_check( $access_provider_id, $result ) {
		$this->provider_access[ $access_provider_id ] = $result;
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
	 * Returns the result of all the access checks.
	 *
	 * @return bool|null
	 */
	public function has_access() {
		$access_record_results = $this->get_provider_access();

		// If one provider is granting access, they have access to the course.
		if ( in_array( true, $access_record_results, true ) ) {
			return true;
		}

		// If no provider granted access and they have one provider blocking access, they DO NOT have access to the course.
		if ( in_array( false, $access_record_results, true ) ) {
			return false;
		}

		// If all providers returned `null` or there are no providers, return `null` and let Sensei use its default access check.
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
	 *
	 * @param string $access_providers_version Hash of the access providers versions used to generate this log.
	 */
	public function finalize_log( $access_providers_version ) {
		if ( ! isset( $this->version ) ) {
			$this->version = $access_providers_version;
		}
	}
}

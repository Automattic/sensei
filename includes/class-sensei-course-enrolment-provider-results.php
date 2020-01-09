<?php
/**
 * File containing the class Sensei_Course_Enrolment_Provider_Results.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores a record for course enrolment checks between a particular user and course.
 */
final class Sensei_Course_Enrolment_Provider_Results implements JsonSerializable {
	/**
	 * Course enrolment results from providers.
	 *
	 * @var array
	 */
	private $provider_results = [];

	/**
	 * Time the results were generated.
	 *
	 * @var float
	 */
	private $time;

	/**
	 * Version of enrolment providers. This is a hash of all the enrolment provider versions.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Sensei_Course_Enrolment_Provider_Results constructor.
	 *
	 * @param array  $provider_results Course enrolment check results from all providers.
	 * @param string $version          Version of the enrolment providers that produced this set of results.
	 * @param float  $time             Time this set of results were recorded.
	 */
	public function __construct( $provider_results, $version, $time = null ) {
		$this->provider_results = $provider_results;
		$this->version          = $version;
		$this->time             = isset( $time ) ? $time : microtime( true );
	}

	/**
	 * Restore a course enrolment result record from a serialized JSON string.
	 *
	 * @param string $json_string JSON representation of enrolment results.
	 *
	 * @return Sensei_Course_Enrolment_Provider_Results|bool
	 */
	public static function from_json( $json_string ) {
		$json_arr = json_decode( $json_string, true );
		if ( ! $json_arr ) {
			return false;
		}

		$provider_results = isset( $json_arr['r'] ) ? array_map( 'boolval', $json_arr['r'] ) : [];
		$version          = isset( $json_arr['v'] ) ? sanitize_text_field( $json_arr['v'] ) : -1;
		$time             = isset( $json_arr['t'] ) ? floatval( $json_arr['t'] ) : null;

		return new self( $provider_results, $version, $time );
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
			'r' => $this->provider_results,
		];
	}

	/**
	 * Get the enrolment check results from all providers.
	 *
	 * @return array
	 */
	public function get_provider_results() {
		return $this->provider_results;
	}

	/**
	 * Returns the result of all the enrolment checks. Used by `Sensei_Course_Enrolment::is_enroled()`, do not call directly.
	 *
	 * @access private
	 *
	 * @return bool|null
	 */
	public function is_enrolment_provided() {
		$provider_results = $this->get_provider_results();

		// If one provider is allowing enrolment, they are enroled in the course.
		if ( in_array( true, $provider_results, true ) ) {
			return true;
		}

		// @todo Remove this once we have a core provider, such as manual enrolment.
		// If there are no providers, return `null` and let Sensei handle it.
		if ( empty( $provider_results ) ) {
			return null;
		}

		// The student is not enrolled in the course.
		return false;
	}

	/**
	 * Get the version of the enrolment providers at the time these results were generated.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the time these results were generated.
	 *
	 * @return string
	 */
	public function get_time() {
		return $this->time;
	}
}

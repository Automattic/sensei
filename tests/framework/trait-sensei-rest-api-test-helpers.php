<?php
/**
 * File with trait Sensei_Scheduler_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers used in REST API tests.
 *
 * @since 3.6.0
 */
trait Sensei_REST_API_Test_Helpers {
	/**
	 * Assert the response matches the schema.
	 *
	 * @param array $schema Endpoint response schema.
	 * @param array $result Request response body.
	 */
	public function assertMeetsSchema( $schema, $result ) {
		$this->assertTrue( true === rest_validate_value_from_schema( $result, $schema ), 'Result does not match schema' );
	}

	/**
	 * Get response code and status.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Response $response Request response object.
	 *
	 * @return array Associative array containing the status and error code.
	 */
	public function getResponseAndStatusCode( WP_REST_Response $response ): array {
		return [
			'status_code' => $response->get_status(),
			'error_code'  => $response->get_data()['code'] ?? null,
		];
	}

	/**
	 * Get response status code and data
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Response $response Response object.
	 *
	 * @return array Array containing the status code and data.
	 */
	public function getResponseStatusAndData( WP_REST_Response $response ): array {
		return [
			'status_code' => $response->get_status(),
			'data'        => $response->get_data(),
		];
	}
}

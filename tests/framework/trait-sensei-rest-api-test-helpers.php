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
		// If we made it this far, faux-assert always true.
		$this->assertTrue( rest_validate_value_from_schema( $result, $schema ), 'Result does not match schema' );
	}
}

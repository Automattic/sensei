<?php
/**
 * File with trait Sensei_Scheduler_Test_Helpers.
 *
 * @package sensei-tests
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Using PHPUnit conventions.

use Swaggest\JsonSchema\Schema;

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
		// We only include `autoload.php` when PHPUnit can be installed, which is on PHP 7.2+.
		if ( ! class_exists( Schema::class ) ) {
			$this->markTestSkipped( 'Test requires a higher version of PHP' );
			return;
		}

		// Object (key based arrays) should be `stdClass` objects for validation.
		$normalized_result = json_decode( wp_json_encode( $result ) );
		$normalized_schema = json_decode( wp_json_encode( $schema ) );

		$schema = Schema::import( $normalized_schema );

		try {
			$schema->in( $normalized_result );
		} catch ( \Exception $e ) {
			// Cheeky way to bail and show error message.
			$this->assertTrue( false, $e->getMessage() );
		}

		// If we made it this far, faux-assert always true.
		$this->assertTrue( true );
	}
}

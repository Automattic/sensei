<?php
/**
 * This file contains the Sensei_Home_Remote_Data_API class.
 *
 * @package sensei
 */


/**
 * Tests for Sensei_Home_Remote_Data_API class.
 *
 * @covers Sensei_Home_Remote_Data_API
 */
class Sensei_Home_Remote_Data_API_Test extends WP_UnitTestCase {
	/**
	 * Tests to make sure we are using the correct endpoint.
	 */
	public function testFetchCorrectPluginData() {
		$http_requests = $this->trackHttpRequests( [] );
		$provider      = new Sensei_Home_Remote_Data_API( 'dinosaurs', '1.0.0' );
		$remote_data   = $provider->fetch();

		$this->stopTrackingHttpRequests();

		$this->assertEquals( 1, $http_requests->count() );
		$expected_url   = Sensei_Home_Remote_Data_API::API_BASE_URL . 'dinosaurs.json';
		$requested_path = strtok( $http_requests[0]['url'], '?' );
		$this->assertEquals( $expected_url, $requested_path );
	}

	/**
	 * Tests to make sure we can override the primary plugin.
	 */
	public function testFetchCorrectPluginDataWithOverride() {
		add_filter(
			'sensei_home_remote_data_primary_plugin_slug',
			function() {
				return 'penguins';
			}
		);

		$http_requests = $this->trackHttpRequests( [] );
		$provider      = new Sensei_Home_Remote_Data_API( 'dinosaurs', '1.0.0' );
		$remote_data   = $provider->fetch();

		$this->stopTrackingHttpRequests();
		remove_all_filters( 'sensei_home_remote_data_primary_plugin' );

		$this->assertEquals( 1, $http_requests->count() );
		$expected_url   = Sensei_Home_Remote_Data_API::API_BASE_URL . 'penguins.json';
		$requested_path = strtok( $http_requests[0]['url'], '?' );
		$this->assertEquals( $expected_url, $requested_path );
	}

	/**
	 * Tests to make sure the cache is used when hydrated.
	 */
	public function testFetchCacheUsed() {
		$http_requests = $this->trackHttpRequests(
			function() {
				return [
					'hit' => uniqid(),
				];
			}
		);

		$provider     = new Sensei_Home_Remote_Data_API( 'dinosaurs', '1.0.0' );
		$first_fetch  = $provider->fetch();
		$second_fetch = $provider->fetch();
		$this->stopTrackingHttpRequests();

		$this->assertIsArray( $first_fetch );
		$this->assertIsArray( $second_fetch );

		$this->assertEquals( $first_fetch['hit'], $second_fetch['hit'], 'The two requests should return the same unique ID' );
	}

	/**
	 * Tests to make sure the max age is respected.
	 */
	public function testFetchCacheMaxAgeRespected() {
		$http_requests = $this->trackHttpRequests(
			function() {
				return [
					'hit' => uniqid(),
				];
			}
		);

		// Test max age of 60 seconds.
		$max_age = 60;
		$url     = Sensei_Home_Remote_Data_API::API_BASE_URL . '/test.json';

		$provider = $this->getMockBuilder( Sensei_Home_Remote_Data_API::class )
			->setConstructorArgs( [ 'dinosaurs', '1.0.0' ] )
			->setMethods( [ 'get_api_url' ] )
			->getMock();
		$provider->expects( $this->any() )->method( 'get_api_url' )->willReturn( $url );

		// Clone to avoid local caches.
		$provider_b = clone $provider;

		$first_fetch = $provider->fetch( $max_age );

		// Artificially change the fetched time of the cached data.
		$this->artificiallyChangeCache( $url, [ '_fetched' => time() - $max_age - 1 ] );

		$second_fetch = $provider_b->fetch( $max_age );
		$this->stopTrackingHttpRequests();

		$this->assertIsArray( $first_fetch );
		$this->assertIsArray( $second_fetch );

		$this->assertNotEquals( $first_fetch['hit'], $second_fetch['hit'] );
	}

	/**
	 * Tests to make sure local cache is used when hydrated.
	 */
	public function testFetchLocalCacheUsed() {
		$http_requests = $this->trackHttpRequests(
			function() {
				return [
					'hit' => uniqid(),
				];
			}
		);

		// Test max age of 60 seconds.
		$max_age = 60;
		$url     = Sensei_Home_Remote_Data_API::API_BASE_URL . '/test.json';

		$provider = $this->getMockBuilder( Sensei_Home_Remote_Data_API::class )
			->setConstructorArgs( [ 'dinosaurs', '1.0.0' ] )
			->setMethods( [ 'get_api_url' ] )
			->getMock();
		$provider->expects( $this->any() )->method( 'get_api_url' )->willReturn( $url );

		$first_fetch = $provider->fetch( $max_age );

		// Artificially mark the cache as bad.
		$this->artificiallyChangeCache( $url, [ '_bad' => true ] );

		$second_fetch = $provider->fetch( $max_age );
		$this->stopTrackingHttpRequests();

		$this->assertIsArray( $first_fetch );
		$this->assertIsArray( $second_fetch );

		$this->assertEquals( $first_fetch['hit'], $second_fetch['hit'] );
		$this->assertFalse( isset( $second_fetch['_bad'] ), 'The second fetch should not be from our tainted cache.' );
	}

	/**
	 * Tests to make sure errors are returned when the API is down for multiple requests.
	 */
	public function testFetchErrorReturnedBeforeRetry() {
		$this->trackHttpRequests(
			function() {
				return '<html><body>Internal Server Error</body></html>';
			}
		);

		// Test max age of 60 seconds.
		$max_age = 60;
		$url     = Sensei_Home_Remote_Data_API::API_BASE_URL . '/test.json';

		$provider = $this->getMockBuilder( Sensei_Home_Remote_Data_API::class )
			->setConstructorArgs( [ 'dinosaurs', '1.0.0' ] )
			->setMethods( [ 'get_api_url' ] )
			->getMock();
		$provider->expects( $this->any() )->method( 'get_api_url' )->willReturn( $url );

		// Clone the provider to avoid local cache issues.
		$provider_b = clone $provider;

		$first_fetch = $provider->fetch( $max_age );
		$this->stopTrackingHttpRequests();
		$this->assertWPError( $first_fetch );

		// From now on, all requests will succeed.
		$this->trackHttpRequests(
			function() {
				return [ 'hit' => uniqid() ];
			}
		);

		$second_fetch = $provider_b->fetch( $max_age );
		$this->assertWPError( $second_fetch );
		$this->assertEquals( $first_fetch->get_error_code(), $second_fetch->get_error_code(), 'The error codes should be the same' );

		$third_fetch_with_retry = $provider_b->fetch( $max_age, true );
		$this->stopTrackingHttpRequests();

		$this->assertIsArray( $third_fetch_with_retry );
		$this->assertArrayHasKey( 'hit', $third_fetch_with_retry );

	}

	/**
	 * Artificially change the fetched time of the cached data.
	 *
	 * @param string $url     The URL used to generate cache key.
	 * @param array  $changes Changes to make to the cache.
	 */
	private function artificiallyChangeCache( $url, $changes ) {
		$cache_key = Sensei_Home_Remote_Data_API::CACHE_KEY_PREFIX . md5( $url );
		$cached    = get_transient( $cache_key );

		set_transient( $cache_key, array_merge( $cached, $changes ) );
	}

	/**
	 * Track HTTP requests.
	 *
	 * @param mixed $response The response to give. Return `false` to not preempt the HTTP request.
	 */
	private function trackHttpRequests( $response = false ) {
		$tracker = new \SplStack();

		add_filter(
			'pre_http_request',
			function( $preempt, $args, $url ) use ( $tracker, $response ) {
				// We are only tracking requests related to the remote data helper.
				if ( 0 !== strpos( $url, Sensei_Home_Remote_Data_API::API_BASE_URL ) ) {
					return $preempt;
				}

				$tracker[] = [
					'args' => $args,
					'url'  => $url,
				];

				if ( is_callable( $response ) ) {
					$response = $response( $args, $url );
				}

				$serial_response = is_string( $response ) ? $response : wp_json_encode( $response );

				return [ 'body' => $serial_response ];
			},
			10,
			3
		);

		return $tracker;
	}

	/**
	 * Stop tracking HTTP requests.
	 */
	private function stopTrackingHttpRequests() {
		remove_all_filters( 'pre_http_request' );
	}
}

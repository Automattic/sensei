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
		$provider      = new Sensei_Home_Remote_Data_API( 'dinosaurs' );
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
		$provider      = new Sensei_Home_Remote_Data_API( 'dinosaurs' );
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

		$provider     = new Sensei_Home_Remote_Data_API( 'dinosaurs' );
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
			->setConstructorArgs( [ 'dinosaurs' ] )
			->setMethods( [ 'get_api_url' ] )
			->getMock();
		$provider->expects( $this->exactly( 2 ) )->method( 'get_api_url' )->willReturn( $url );

		$first_fetch = $provider->fetch( $max_age );

		// Artificially change the fetched time of the cached data.
		$this->artificiallyChangeCacheFetched( $url, time() - $max_age - 1 );

		$second_fetch = $provider->fetch( $max_age );
		$this->stopTrackingHttpRequests();

		$this->assertIsArray( $first_fetch );
		$this->assertIsArray( $second_fetch );

		$this->assertNotEquals( $first_fetch['hit'], $second_fetch['hit'] );
	}

	/**
	 * Artificially change the fetched time of the cached data.
	 *
	 * @param string $url             The URL used to generate cache key.
	 * @param int    $fetched_cahange The time to set the fetched time to.
	 */
	private function artificiallyChangeCacheFetched( $url, $fetched_change ) {
		$cache_key          = Sensei_Home_Remote_Data_API::CACHE_KEY_PREFIX . md5( $url );
		$cached             = get_transient( $cache_key );
		$cached['_fetched'] = $cached['_fetched'] - $fetched_change;
		set_transient( $cache_key, $cached );
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

				return [ 'body' => wp_json_encode( $response ) ];
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

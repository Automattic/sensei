<?php
/**
 * This file contains the Sensei_Home_News_Provider_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Home_News_Provider class.
 *
 * @covers Sensei_Home_News_Provider
 */
class Sensei_Home_News_Provider_Test extends WP_UnitTestCase {
	/**
	 * Assert that all items returned by the provider are correctly formatted.
	 */
	public function testFormattedCorrectly() {
		$remote_data_api = $this->getRemoteDataMock( $this->getMixedReesponse() );

		$news_provider = new Sensei_Home_News_Provider( $remote_data_api );
		$news          = $news_provider->get();

		$this->assertArrayHasKey( 'items', $news );
		$this->assertArrayHasKey( 'more_url', $news );
		$this->assertIsArray( $news['items'] );

		foreach ( $news['items'] as $item ) {
			$this->assertIsArray( $item );
			$this->assertArrayHasKey( 'title', $item );
			$this->assertIsString( $item['title'] );
			$this->assertArrayHasKey( 'url', $item );
			$this->assertIsString( $item['url'] );
			$this->assertArrayHasKey( 'date', $item );
			$this->assertIsString( $item['date'] );
			$this->assertCount( 3, array_keys( $item ) );
		}
	}

	/**
	 * Tests invalid entries are filtered out.
	 */
	public function testInvalidItemsNotIncluded() {
		$remote_data_api = $this->getRemoteDataMock( $this->getMixedReesponse() );

		$news_provider = new Sensei_Home_News_Provider( $remote_data_api );
		$news          = $news_provider->get();

		$this->assertArrayHasKey( 'items', $news );
		$this->assertArrayHasKey( 'more_url', $news );
		$this->assertIsArray( $news['items'] );
		$this->assertCount( 4, $news['items'] );

		foreach ( $news['items'] as $item ) {
			$this->assertIsArray( $item );
			$this->assertNotEquals( 'https://example.com/null/', $item['url'] ?? '' );
			$this->assertNotEquals( 'No URL', $item['title'] ?? '' );
		}
	}

	/**
	 * Get example response with mixed valid/invalid items.
	 */
	private function getMixedReesponse() {
		return [
			'news' => [
				'items'    => [
					[
						'title' => 'Introducing Interactive Videos For WordPress',
						'url'   => 'https://example.com/inroducing-interactive-videos/',
						'date'  => '2022-08-31T21:41:38',
					],
					[
						'title' => 'New Block Visibility, Scheduled Content, and Group Features',
						'url'   => 'https://example.com/conditional-content/',
						'date'  => '2022-08-09T20:51:51',
					],
					[
						'title' => 'Student Groups &#038; Cohorts Are Now In Sensei',
						'url'   => 'https://example.com/student-groups-cohorts/',
						'date'  => '2022-07-18T21:28:11',
					],
					[
						'title' => 'New! Make Interactive Lesson Content Required',
						'url'   => 'https://example.com/new-make-interactive-lesson-content-required/',
						'date'  => '2022-06-09T21:18:55',
					],
					[
						'title' => null,
						'url'   => 'https://example.com/null/',
						'date'  => '2022-06-09T21:18:55',
					],
					[
						'title' => 'No Date',
						'url'   => 'https://example.com/null/',
					],
					[
						'title' => 'No URL',
						'date'  => '2022-06-09T21:18:55',
					],
				],
				'more_url' => 'https://example.com',
			],
		];
	}

	/**
	 * The remote data API mock builder.
	 *
	 * @param mixed $response Resonse from remote data API.
	 *
	 * @return Sensei_Home_Remote_Data_API
	 */
	private function getRemoteDataMock( $response ) {
		$remote_data_api = $this->getMockBuilder( Sensei_Home_Remote_Data_API::class )
			->setMethods( [ 'fetch' ] )
			->setConstructorArgs( [ 'example-plugin' ] )
			->getMock();

		$remote_data_api->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( $response );

		return $remote_data_api;
	}
}

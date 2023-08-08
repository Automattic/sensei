<?php
/**
 * This file contains the Sensei_Home_Notices_Provider_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Home_Notices_Provider class.
 *
 * @covers Sensei_Home_Notices_Provider
 */
class Sensei_Home_Notices_Provider_Test extends WP_UnitTestCase {
	public function testGet_GivenSimpleFilterResponse_ReturnsFilterValue() {
		// Arrange.
		$notices_provider = new Sensei_Home_Notices_Provider( null, 'screen-id' );

		$test_data = $this->getSimpleResponse();

		add_filter(
			'sensei_admin_notices',
			function() use ( $test_data ) {
				return $test_data;
			}
		);

		// Act.
		$notices = $notices_provider->get();

		// Assert.
		$this->assertEquals( wp_json_encode( $test_data ), wp_json_encode( $notices ) );
	}

	public function testGet_GivenMixedNotices_ReturnsHomeNoticesOnly() {
		// Arrange.
		$notices_provider = new Sensei_Home_Notices_Provider( null, 'screen-id' );
		add_filter(
			'sensei_admin_notices',
			function() {
				return $this->getMixedReesponse();
			}
		);

		// Act.
		$notices = $notices_provider->get();

		// Assert
		$this->assertCount( 2, $notices );
		$this->assertArrayHasKey( Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX . 'test-notice', $notices );
		$this->assertArrayHasKey( Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX . 'test-notice-2', $notices );
		$this->assertArrayNotHasKey( 'test-notice', $notices );
	}

	public function testGet_GivenSenseiAdminNoticeResponse_ReturnsAdminNoticeResponse() {
		// Arrange.
		$test_response      = $this->getSimpleResponse();
		$admin_notices_mock = $this->createMock( Sensei_Admin_Notices::class );

		$admin_notices_mock
			->expects( $this->once() )
			->method( 'get_notices_to_display' )
			->willReturn( $test_response );

		$notices_provider = new Sensei_Home_Notices_Provider( $admin_notices_mock, null );

		// Act.
		$notices = $notices_provider->get();

		// Assert.
		$this->assertEquals( wp_json_encode( $test_response ), wp_json_encode( $notices ) );
	}


	public function testGetBadgeCount_GivenSenseiAdminNoticeResponse_ReturnsAdminNoticeCount() {
		// Arrange.
		$test_response      = $this->getSimpleResponse();
		$admin_notices_mock = $this->createMock( Sensei_Admin_Notices::class );

		$admin_notices_mock
			->expects( $this->once() )
			->method( 'get_notices_to_display' )
			->willReturn( $test_response );

		$notices_provider = new Sensei_Home_Notices_Provider( $admin_notices_mock, null );

		// Act.
		$notices = $notices_provider->get_badge_count();

		// Assert.
		$this->assertEquals( count( $test_response ), wp_json_encode( $notices ) );
	}

	/**
	 * Provides a very simple response.
	 *
	 * @return array
	 */
	private function getSimpleResponse() {
		return [
			Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX . 'test-notice' => [
				'level'       => 'info',
				'heading'     => null,
				'message'     => 'A test notice',
				'info_link'   => false,
				'actions'     => [],
				'dismissible' => false,
				'parent_id'   => null,
			],
		];
	}

	/**
	 * Get example response with mixed valid/invalid items.
	 */
	private function getMixedReesponse() {
		return [
			Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX . 'test-notice' => [
				'level'       => 'info',
				'heading'     => null,
				'message'     => 'A test notice A',
				'info_link'   => false,
				'actions'     => [],
				'dismissible' => false,
				'parent_id'   => null,
			],
			Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX . 'test-notice-2' => [
				'level'       => 'info',
				'heading'     => 'A header',
				'message'     => 'A test notice B',
				'info_link'   => false,
				'actions'     => [],
				'dismissible' => false,
				'parent_id'   => null,
			],
			'a-foreign-notice' => [
				'level'       => 'info',
				'heading'     => null,
				'message'     => 'A test notice C',
				'info_link'   => false,
				'actions'     => [],
				'dismissible' => false,
				'parent_id'   => null,
			],
		];
	}
}

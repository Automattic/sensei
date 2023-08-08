<?php
/**
 * This file contains the Sensei_Home_Notices_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Home_Notices class.
 *
 * @covers Sensei_Home_Notices
 */
class Sensei_Home_Notices_Test extends WP_UnitTestCase {
	/**
	 * Sets up an environment with a mix of licensed/unlicensed, activated/deactivated, and up-to-date/needs-update plugins.
	 */
	public function set_up() {
		parent::set_up();

		wp_cache_set(
			'plugins',
			[
				'' => [
					'sensei-lms/sensei-lms.php'           => [
						'Name'    => 'Sensei LMS',
						'Version' => '3.0.0',
					],
					'sensei-pro/sensei-pro.php'           => [
						'Name'    => 'Sensei Pro',
						'Version' => '1.0.0',
					],
					'sensei-certificates/woothemes-sensei-certificates.php' => [
						'Name'    => 'Sensei Certificates',
						'Version' => '2.0.0',
					],
					'sensei-ultimate/sensei-ultimate.php' => [
						'Name'    => 'Sensei Ultimate',
						'Version' => '20.0.0',
					],
					'sensei-cool/sensei-cool.php'         => [
						'Name'    => 'Sensei Cool',
						'Version' => '10.0.0',
					],
					'sensei-future/sensei-future.php'     => [
						'Name'    => 'Sensei Future',
						'Version' => '2000.0.0',
					],
					'acme/acme.php'                       => [
						'Name'    => 'Acme',
						'Version' => '2.0.0',
					],
				],
			],
			'plugins'
		);

		add_filter(
			'pre_option_active_plugins',
			function() {
				return [
					'sensei-lms/sensei-lms.php',
					'sensei-pro/sensei-pro.php',
					'sensei-cool/sensei-cool.php',
					'sensei-future/sensei-future.php',
					'acme/acme.php',
				];
			}
		);

		add_filter( 'sensei_home_is_plugin_licensed_sensei-cool', '__return_true' );
	}

	public function tear_down() {
		remove_all_filters( 'pre_option_active_plugins' );
		remove_filter( 'sensei_home_is_plugin_licensed_sensei-cool', '__return_true' );

		parent::tear_down();
	}

	public function testAddReviewNotice_GivenUnderprivUser_ReturnsEmptyArray() {
		// Arrange.
		update_option( 'sensei_installed_at', strtotime( '-1 year' ) );
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'editor' ] );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_review_notice( [] );

		// Assert.
		$this->assertEmpty( $notices );
	}


	public function testAddReviewNotice_GivenDisabledRemoteAPI_ReturnsEmptyArray() {
		// Arrange.
		update_option( 'sensei_installed_at', strtotime( '-1 year' ) );
		$remote_data_mock = $this->getRemoteDataMock( [] );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
		grant_super_admin( $user->ID );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_review_notice( [] );

		// Assert.
		$this->assertEmpty( $notices );
	}

	public function testAddReviewNotice_GivenAdministrator_ReturnsCorrectNotices() {
		// Arrange.
		$notice_id = 'sensei_home_sensei_review';
		update_option( 'sensei_installed_at', strtotime( '-1 year' ) );
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
		grant_super_admin( $user->ID );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_review_notice( [] );

		// Assert.
		$this->assertArrayHasKey( $notice_id, $notices );
		$this->assertStringContainsString( 'Are you enjoying', $notices[ $notice_id ]['message'] );
		$this->assertEquals( 'Yes', $notices[ $notice_id ]['actions'][0]['label'] );
		$this->assertEquals( 'No', $notices[ $notice_id ]['actions'][1]['label'] );
	}

	public function testAddReviewNotice_GivenAdministratorYesResponse_ReturnsReviewAnswer() {
		// Arrange.
		$notice_id = 'sensei_home_sensei_review_yes';
		update_option( 'sensei_installed_at', strtotime( '-1 year' ) );
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
		grant_super_admin( $user->ID );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_review_notice( [] );

		// Assert.
		$this->assertArrayHasKey( $notice_id, $notices );
		$this->assertStringContainsString( 'Great to hear', $notices[ $notice_id ]['message'] );
		$this->assertEquals( 'https://review_url', $notices[ $notice_id ]['info_link']['url'] );
		$this->assertEquals( 'sensei_home_sensei_review', $notices[ $notice_id ]['parent_id'] );
	}


	public function testAddReviewNotice_GivenAdministratorNoResponse_ReturnsFeedbackAnswer() {
		// Arrange.
		$notice_id = 'sensei_home_sensei_review_no';
		update_option( 'sensei_installed_at', strtotime( '-1 year' ) );
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
		grant_super_admin( $user->ID );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_review_notice( [] );

		// Assert.
		$this->assertArrayHasKey( $notice_id, $notices );
		$this->assertStringContainsString( 'Let us know how we can improve your experience', $notices[ $notice_id ]['message'] );
		$this->assertEquals( 'https://feedback_url', $notices[ $notice_id ]['info_link']['url'] );
		$this->assertEquals( 'sensei_home_sensei_review', $notices[ $notice_id ]['parent_id'] );
	}

	public function testAddUpdateNotices_GivenUnderprivUser_ReturnsEmptyArray() {
		// Arrange.
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'editor' ] );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_update_notices( [] );

		// Assert.
		$this->assertEmpty( $notices );
	}

	public function testAddUpdateNotices_GivenMixStatusPlugins_ReturnsCorrectNotices() {
		// Arrange.
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
		grant_super_admin( $user->ID );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_update_notices( [] );

		// Assert.
		$this->assertCount( 5, $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-lms_update_1000.0.0', $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-pro_update_1000.0.0', $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-certificates_update_1000.0.0', $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-ultimate_update_1000.0.0', $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-cool_update_1000.0.0', $notices );

		$this->assertStringContainsString( 'Please update', $notices['sensei_home_sensei-lms_update_1000.0.0']['message'] );
		$this->assertStringContainsString( 'Please activate the plugin license', $notices['sensei_home_sensei-pro_update_1000.0.0']['message'] );
		$this->assertStringContainsString( 'Please update', $notices['sensei_home_sensei-certificates_update_1000.0.0']['message'] );
		$this->assertStringContainsString( 'Please update', $notices['sensei_home_sensei-cool_update_1000.0.0']['message'] );
		$this->assertStringContainsString( 'Please activate the plugin in order', $notices['sensei_home_sensei-ultimate_update_1000.0.0']['message'] );
	}

	public function testAddUpdateNotices_GivenLocalUpdatesUnavailable_ReturnsUnlicensedOnly() {
		// Arrange.
		$remote_data_mock = $this->getRemoteDataMock( $this->getStandardResponse() );
		$notices          = $this->getNoticesMock( $remote_data_mock, false );
		$user             = $this->factory->user->create_and_get( [ 'role' => 'administrator' ] );
		grant_super_admin( $user->ID );
		wp_set_current_user( $user->ID );

		// Act.
		$notices = $notices->add_update_notices( [] );

		// Assert.
		$this->assertCount( 2, $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-pro_update_1000.0.0', $notices );
		$this->assertArrayHasKey( 'sensei_home_sensei-ultimate_update_1000.0.0', $notices );

		$this->assertStringContainsString( 'Please activate the plugin license', $notices['sensei_home_sensei-pro_update_1000.0.0']['message'] );
		$this->assertStringContainsString( 'Please activate the plugin in order', $notices['sensei_home_sensei-ultimate_update_1000.0.0']['message'] );
	}

	/**
	 * Get the standard response with some versions.
	 *
	 * @return array
	 */
	private function getStandardResponse() {
		return [
			'versions' => [
				'plugins' => [
					'sensei-lms'          => [
						'version'   => '1000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-lms',
						'licensed'  => false,
					],
					'sensei-pro'          => [
						'version'   => '1000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-pro',
						'licensed'  => true,
					],
					'sensei-certificates' => [
						'version'   => '1000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-certificates',
						'licensed'  => false,
					],
					'sensei-ultimate'     => [
						'version'   => '1000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-ultimate',
						'licensed'  => true,
					],
					'sensei-dinosaurs'    => [
						'version'   => '1000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-dinosaurs',
						'licensed'  => false,
					],
					'sensei-future'       => [
						'version'   => '2000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-future',
						'licensed'  => false,
					],
					'sensei-cool'         => [
						'version'   => '1000.0.0',
						'changelog' => 'https://example.com/changelog/sensei-cool',
						'licensed'  => true,
					],
				],
			],
			'reviews'  => [
				'show_after'   => '10 days',
				'feedback_url' => 'https://feedback_url',
				'review_url'   => 'https://review_url',
			],
		];
	}

	/**
	 * Get the notices mock.
	 *
	 * @param mixed $remote_data_mock       The remote data mock.
	 * @param bool  $local_update_available Whether there is a local update available.
	 *
	 * @return Sensei_Home_Notices
	 */
	private function getNoticesMock( $remote_data_mock, $local_update_available = true ) {
		$notices_mock = $this->getMockBuilder( Sensei_Home_Notices::class )
			->setConstructorArgs( [ $remote_data_mock, Sensei_Home::SCREEN_ID ] )
			->setMethods( [ 'is_plugin_update_available' ] )
			->getMock();

		$notices_mock->expects( $this->any() )->method( 'is_plugin_update_available' )->willReturn( $local_update_available );
		return $notices_mock;
	}

	/**
	 * The remote data API mock builder.
	 *
	 * @param mixed $response Response from remote data API.
	 *
	 * @return Sensei_Home_Remote_Data_API
	 */
	private function getRemoteDataMock( $response ) {
		$remote_data_api = $this->getMockBuilder( Sensei_Home_Remote_Data_API::class )
			->setMethods( [ 'fetch' ] )
			->setConstructorArgs( [ 'example-plugin', '1.0.0' ] )
			->getMock();

		$remote_data_api->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( $response );

		return $remote_data_api;
	}
}

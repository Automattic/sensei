<?php
/**
 * This file contains the Sensei_Admin_Notices_Test class.
 *
 * @package sensei
 */

/**
 * Tests for Sensei_Admin_Notices class.
 */
class Sensei_Admin_Notices_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;
	use Sensei_Clock_Helpers;

	public function tear_down() {
		$this->reset_clock();
		parent::tear_down();
	}

	/**
	 * Test the `min_wp` condition.
	 */
	public function testConditionMinWP() {
		$this->login_as_admin();

		global $wp_version;
		$current_wp_version = $wp_version;
		$wp_version         = '5.0.0';

		$all_notices = [
			'show-greater-than-49' => [
				'message'    => 'Only greater than 4.9',
				'conditions' => [
					[
						'type'    => 'min_wp',
						'version' => '4.9.0',
					],
				],
			],
			'show-exactly-500'     => [
				'message'    => 'Only greater or equal to 5.0.0',
				'conditions' => [
					[
						'type'    => 'min_wp',
						'version' => '5.0.0',
					],
				],
			],
			'hide-greater-than-51' => [
				'message'    => 'Only greater than 5.1',
				'conditions' => [
					[
						'type'    => 'min_wp',
						'version' => '5.1.0',
					],
				],
			],
		];

		$instance   = $this->getMockInstance( [ 'notices' => $all_notices ] );
		$notices    = $instance->get_notices_to_display();
		$wp_version = $current_wp_version;

		$this->assertArrayHasKey( 'show-greater-than-49', $notices );
		$this->assertArrayHasKey( 'show-exactly-500', $notices );
		$this->assertArrayNotHasKey( 'hide-greater-than-51', $notices );
	}

	/**
	 * Test the `user_cap` condition.
	 */
	public function testConditionUserCap() {
		$all_notices = [
			'show-to-editors' => [
				'type'       => 'user',
				'message'    => 'Include editors',
				'conditions' => [
					[
						'type'    => 'user_cap',
						'version' => 'edit_posts',
					],
				],
			],
			'only-to-admins'  => [
				'message'    => 'Only show this to admins',
				'conditions' => [
					[
						'type'    => 'user_cap',
						'version' => 'edit_posts',
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $all_notices ] );

		$this->login_as_editor();
		$notices_as_editor = $instance->get_notices_to_display();
		$this->assertArrayHasKey( 'show-to-editors', $notices_as_editor );
		$this->assertArrayNotHasKey( 'only-to-admins', $notices_as_editor );

		$this->login_as_admin();
		$notices_as_admin = $instance->get_notices_to_display();
		$this->assertArrayHasKey( 'show-to-editors', $notices_as_admin );
		$this->assertArrayHasKey( 'only-to-admins', $notices_as_admin );
	}

	/**
	 * Test the `user_cap` condition default on site-wide notices.
	 */
	public function testConditionUserCapOnSiteWideNotices() {
		$all_notices = [
			'show-to-users'  => [
				'type'    => 'user',
				'message' => 'Include all users',
			],
			'only-to-admins' => [
				'type'    => 'site-wide',
				'message' => 'Only show this to admins',
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $all_notices ] );

		$this->login_as_editor();
		$notices_as_editor = $instance->get_notices_to_display();
		$this->assertArrayHasKey( 'show-to-users', $notices_as_editor );
		$this->assertArrayNotHasKey( 'only-to-admins', $notices_as_editor );

		$this->login_as_admin();
		$notices_as_admin = $instance->get_notices_to_display();
		$this->assertArrayHasKey( 'show-to-users', $notices_as_admin );
		$this->assertArrayHasKey( 'only-to-admins', $notices_as_admin );
	}

	/**
	 * Test the `screen` condition.
	 */
	public function testConditionsScreen() {
		$this->login_as_admin();

		$all_notices = [
			'allow-on-wc'        => [
				'message'    => 'Important message to include on plugins and WC addons page.',
				'conditions' => [
					[
						'type'    => 'screens',
						'screens' => [ 'woocommerce_page_wc-addons', 'plugins', 'themes' ],
					],
					[
						'type'         => 'user_cap',
						'capabilities' => [ 'edit_others_posts' ],
					],
				],
			],
			'do-not-allow-on-wc' => [
				'message'    => 'Important message to include on plugins page.',
				'conditions' => [
					[
						'type'    => 'screens',
						'screens' => [ 'plugins', 'themes' ],
					],
					[
						'type'         => 'user_cap',
						'capabilities' => [ 'install_plugins' ],
					],
				],
			],
		];

		$instance_on_wc_addons = $this->getMockInstance(
			[
				'screen_id' => 'woocommerce_page_wc-addons',
				'notices'   => $all_notices,
			]
		);
		$notices_on_wc_addons  = $instance_on_wc_addons->get_notices_to_display();
		$this->assertArrayHasKey( 'allow-on-wc', $notices_on_wc_addons );
		$this->assertArrayNotHasKey( 'do-not-allow-on-wc', $notices_on_wc_addons );

		$instance_on_dashboard = $this->getMockInstance(
			[
				'notices'   => $all_notices,
				'screen_id' => 'dashboard',
			]
		);
		$notices_on_dashboard  = $instance_on_dashboard->get_notices_to_display();
		$this->assertEmpty( $notices_on_dashboard, 'No set notices should show on dashboard.' );
	}

	/**
	 * Makes sure notices without a screens condition only show on Sensei pages.
	 */
	public function testCheckNoScreensCondition() {
		$this->login_as_admin();

		$all_notices = [
			'on-sensei-pages' => [
				'message' => 'Important message to show on Sensei pages',
			],
		];

		$instance_on_dashboard = $this->getMockInstance(
			[
				'notices'   => $all_notices,
				'screen_id' => 'dashboard',
			]
		);

		$notices_on_dashboard = $instance_on_dashboard->get_notices_to_display();
		$this->assertEmpty( $notices_on_dashboard );

		$instance_on_edit_course = $this->getMockInstance(
			[
				'notices'   => $all_notices,
				'screen_id' => 'edit-course',
			]
		);

		$notices_on_edit_course = $instance_on_edit_course->get_notices_to_display();
		$this->assertArrayHasKey( 'on-sensei-pages', $notices_on_edit_course );
	}

	public function testGetNoticesToDisplay_WithDateRangeCondtionMet_ShowNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '-1 minute' ) )->format( 'c' ),
						'end_date'   => ( new DateTime( '+1 minute' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithDateRangeEndingMinuteAgo_HideNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '-2 minutes' ) )->format( 'c' ),
						'end_date'   => ( new DateTime( '-1 minute' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithDateRangeStartingInOneMinute_HideNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '+1 minute' ) )->format( 'c' ),
						'end_date'   => ( new DateTime( '+1 year' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithInvalidStartDateFormat_HideNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '-1 year' ) )->format( 'c' ) . ' MoonTime',
						'end_date'   => ( new DateTime( '+1 year' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithInvalidEndDateFormat_HideNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '-1 year' ) )->format( 'c' ),
						'end_date'   => ( new DateTime( '+1 year' ) )->format( 'c' ) . ' MoonTime',
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithPartialStartDateInFuture_HideNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '+1 hour' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithPartialStartDateInPast_ShowNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'       => 'date_range',
						'start_date' => ( new DateTime( '-1 hour' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithPartialEndDateInPast_HideNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'     => 'date_range',
						'end_date' => ( new DateTime( '-1 hour' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'notice-with-date-range', $notices_to_display );
	}

	public function testGetNoticesToDisplay_WithPartialEndDateInFuture_ShowNotice() {
		// Arrange.
		$this->login_as_admin();
		$notices = [
			'notice-with-date-range' => [
				'message'    => 'Important message to show on sites during a specific date range.',
				'conditions' => [
					[
						'type'     => 'date_range',
						'end_date' => ( new DateTime( '+1 hour' ) )->format( 'c' ),
					],
				],
			],
		];

		$instance = $this->getMockInstance( [ 'notices' => $notices ] );

		// Act.
		$notices_to_display = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayHasKey( 'notice-with-date-range', $notices_to_display );
	}

	/**
	 * Tests basic has/doesn't have plugins test.
	 */
	public function testConditionPlugins() {
		$this->login_as_admin();
		$all_notices = [
			'with-jetpack'        => [
				'message'    => 'Important message to show on on sites with Jetpack active',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [ 'jetpack/jetpack.php' => true ],
					],
				],
			],
			'with-no-woocommerce' => [
				'message'    => 'Important message to show on on sites without WooCommerce active',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [ 'woocommerce/woocommerce.php' => false ],
					],
				],
			],
			'with-woocommerce'    => [
				'message'    => 'Important message to show on on sites with WooCommerce active',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [ 'woocommerce/woocommerce.php' => true ],
					],
				],
			],
			'with-both'           => [
				'message'    => 'Important message to show on on sites with WooCommerce and Jetpack active',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [
							'woocommerce/woocommerce.php' => true,
							'jetpack/jetpack.php'         => true,
						],
					],
				],
			],
		];

		$instance_with_jetpack = $this->getMockInstance(
			[
				'notices' => $all_notices,
				'plugins' => [
					'jetpack/jetpack.php'       => [ 'Version' => '1.0.0' ],
					'sensei-lms/sensei-lms.php' => [ 'Version' => '1.0.0' ],
				],
			]
		);

		$notices_with_jetpack = $instance_with_jetpack->get_notices_to_display();
		$this->assertArrayHasKey( 'with-jetpack', $notices_with_jetpack );
		$this->assertArrayHasKey( 'with-no-woocommerce', $notices_with_jetpack );
		$this->assertArrayNotHasKey( 'with-woocommerce', $notices_with_jetpack );

		$instance_with_both = $this->getMockInstance(
			[
				'notices' => $all_notices,
				'plugins' => [
					'jetpack/jetpack.php'         => [ 'Version' => '1.0.0' ],
					'sensei-lms/sensei-lms.php'   => [ 'Version' => '1.0.0' ],
					'woocommerce/woocommerce.php' => [ 'Version' => '1.0.0' ],
				],
			]
		);

		$notices_with_both = $instance_with_both->get_notices_to_display();
		$this->assertArrayHasKey( 'with-jetpack', $notices_with_both );
		$this->assertArrayNotHasKey( 'with-no-woocommerce', $notices_with_both );
		$this->assertArrayHasKey( 'with-woocommerce', $notices_with_both );
	}


	/**
	 * Test the `installed_since` condition.
	 */
	public function testGetNoticesToDisplay_GivenInstalledSince_ValidatesStrings() {
		// Arrange.
		$current_datetime = new DateTimeImmutable( 'now', new DateTimeZone( 'GMT' ) );
		$this->set_clock_to( $current_datetime->getTimestamp() );

		$this->login_as_admin();

		update_option( 'sensei_installed_at', 10 );
		$all_notices = [
			'hide-since-9'  => [
				'message'    => 'Hide since 9',
				'conditions' => [
					[
						'type'            => 'installed_since',
						'installed_since' => 9,
					],
				],
			],
			'show-since-10' => [
				'message'    => 'Show since 10',
				'conditions' => [
					[
						'type'            => 'installed_since',
						'installed_since' => 10,
					],
				],
			],
			'show-since-11' => [
				'message'    => 'Show since 11',
				'conditions' => [
					[
						'type'            => 'installed_since',
						'installed_since' => 11,
					],
				],
			],
		];

		// Act.
		$instance = $this->getMockInstance( [ 'notices' => $all_notices ] );
		$notices  = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayNotHasKey( 'hide-since-9', $notices );
		$this->assertArrayHasKey( 'show-since-10', $notices );
		$this->assertArrayHasKey( 'show-since-11', $notices );
	}


	/**
	 * Test the `installed_since` condition with relative times.
	 */
	public function testGetNoticesToDisplay_GivenInstalledSinceString_ValidatesStrings() {
		// Arrange.
		$this->login_as_admin();
		update_option( 'sensei_installed_at', time() - 10 );
		$all_notices = [
			'show-since-9'  => [
				'message'    => 'Show since 9',
				'conditions' => [
					[
						'type'            => 'installed_since',
						'installed_since' => '9 seconds',
					],
				],
			],
			'show-since-10' => [
				'message'    => 'Show since 10',
				'conditions' => [
					[
						'type'            => 'installed_since',
						'installed_since' => '10 seconds',
					],
				],
			],
			'hide-since-11' => [
				'message'    => 'Hide since 11',
				'conditions' => [
					[
						'type'            => 'installed_since',
						'installed_since' => '11 seconds',
					],
				],
			],
		];

		// Act.
		$instance = $this->getMockInstance( [ 'notices' => $all_notices ] );
		$notices  = $instance->get_notices_to_display();

		// Assert.
		$this->assertArrayHasKey( 'show-since-9', $notices );
		$this->assertArrayHasKey( 'show-since-10', $notices );
		$this->assertArrayNotHasKey( 'hide-since-11', $notices );
	}

	/**
	 * Tests plugin conditions with version checks.
	 */
	public function testConditionPluginVersions() {
		$this->login_as_admin();
		$all_notices = [
			'with-jetpack-1-3' => [
				'message'    => 'Important message to show on on sites with Jetpack v1-3',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [
							'jetpack/jetpack.php' => [
								'min' => '1.0.0',
								'max' => '3.0.0',
							],
						],
					],
				],
			],
			'with-jetpack-0-4' => [
				'message'    => 'Important message to show on on sites with Jetpack v0-4',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [
							'jetpack/jetpack.php' => [
								'min' => '1.0.0',
								'max' => '4.0.0',
							],
						],
					],
				],
			],
			'with-jetpack-4'   => [
				'message'    => 'Important message to show on on sites with Jetpack v4',
				'conditions' => [
					[
						'type'    => 'plugins',
						'plugins' => [
							'jetpack/jetpack.php' => [
								'min' => '4.0.0',
								'max' => '4.99',
							],
						],
					],
				],
			],
		];

		$instance_with_jetpack_3 = $this->getMockInstance(
			[
				'notices' => $all_notices,
				'plugins' => [
					'jetpack/jetpack.php'       => [ 'Version' => '3.0.0' ],
					'sensei-lms/sensei-lms.php' => [ 'Version' => '1.0.0' ],
				],
			]
		);

		$notices_with_jetpack_3 = $instance_with_jetpack_3->get_notices_to_display();
		$this->assertArrayHasKey( 'with-jetpack-1-3', $notices_with_jetpack_3 );
		$this->assertArrayHasKey( 'with-jetpack-0-4', $notices_with_jetpack_3 );
		$this->assertArrayNotHasKey( 'with-jetpack-4', $notices_with_jetpack_3 );

		$instance_with_jetpack_4 = $this->getMockInstance(
			[
				'notices' => $all_notices,
				'plugins' => [
					'jetpack/jetpack.php'       => [ 'Version' => '4.0.0' ],
					'sensei-lms/sensei-lms.php' => [ 'Version' => '1.0.0' ],
				],
			]
		);

		$notices_with_jetpack_4 = $instance_with_jetpack_4->get_notices_to_display();
		$this->assertArrayNotHasKey( 'with-jetpack-1-3', $notices_with_jetpack_4 );
		$this->assertArrayHasKey( 'with-jetpack-0-4', $notices_with_jetpack_4 );
		$this->assertArrayHasKey( 'with-jetpack-4', $notices_with_jetpack_4 );
	}

	/**
	 * Get the mock instance.
	 *
	 * @return Sensei_Admin_Notices
	 */
	public function getMockInstance( $overrides ) {
		$overrides = array_merge(
			[
				'screen_id' => 'edit-course',
				'notices'   => [],
				'plugins'   => [],
			],
			$overrides
		);

		$mock = $this->getMockBuilder( 'Sensei_Admin_Notices' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_notices', 'get_screen_id', 'get_active_plugins' ] )
			->getMock();

		$mock->expects( $this->any() )
			->method( 'get_notices' )
			->willReturn( $overrides['notices'] );

		$mock->expects( $this->any() )
			->method( 'get_screen_id' )
			->willReturn( $overrides['screen_id'] );

		$mock->expects( $this->any() )
			->method( 'get_active_plugins' )
			->willReturn( $overrides['plugins'] );

		return $mock;
	}

}

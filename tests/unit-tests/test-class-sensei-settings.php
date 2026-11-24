<?php
/**
 * File with class for testing Sensei Settings.
 *
 * @package sensei-tests
 */

use Sensei\Internal\Action_Scheduler\Action_Scheduler;
use Sensei\Internal\Installer\Schema;
use Sensei\Internal\Migration\Migration_Job_Scheduler;
use Sensei\Internal\Student_Progress\Course_Progress\Repositories\Tables_Based_Course_Progress_Repository;

/**
 * Class for testing Sensei Settings.
 *
 * @covers \Sensei_Settings
 */
class Sensei_Settings_Test extends WP_UnitTestCase {
	private $synchronization_fixture;

	/**
	 * Tracking original request method.
	 *
	 * @var string
	 */
	protected $original_request_method;

	/**
	 * Tracking original screen ID.
	 *
	 * @var string
	 */
	protected $original_screen;

	/**
	 * Set up for tests.
	 */
	public function setUp(): void {
		Sensei_Test_Events::reset();
		$this->resetSimulateSettingsRequest();

		parent::setUp();
	}

	/**
	 * Tear down after tests.
	 */
	public function tearDown(): void {
		if ( null !== $this->synchronization_fixture ) {
			global $wpdb;

			$this->resetSimulateSettingsRequest();
			update_option( 'sensei-settings', $this->synchronization_fixture['option'] );
			Sensei()->settings->settings = $this->synchronization_fixture['settings'];

			if ( isset( $this->synchronization_fixture['progress'] ) ) {
				$progress = $this->synchronization_fixture['progress'];

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $wpdb->prefix . 'sensei_lms_progress', array( 'id' => $progress->get_id() ) );
				wp_delete_post( $progress->get_course_id(), true );
			}

			_as_reset();

			// Schema changes commit transactions, so persist the fixture cleanup too.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );
		}

		parent::tearDown();

		Sensei_Test_Events::reset();
		$this->resetSimulateSettingsRequest();
	}

	/**
	 * Test logging of changed settings.
	 *
	 * @covers Sensei_Settings::log_settings_update
	 */
	public function testLogChangedSettings() {
		$settings = Sensei()->settings;
		$new      = $settings->get_settings();

		// Change some settings in General.
		$new['sensei_delete_data_on_uninstall']              = ! $new['sensei_delete_data_on_uninstall'];
		$new['sensei_video_embed_html_sanitization_disable'] = ! $new['sensei_video_embed_html_sanitization_disable'];

		// Change some settings in Courses section.
		$new['course_archive_featured_enable'] = ! $new['course_archive_featured_enable'];
		$new['course_archive_more_link_text']  = $new['course_archive_more_link_text'] . '...';

		// Trigger update with new setting values. Ensure that we are simulating an update from the wp-admin UI.
		$this->simulateSettingsRequest();
		$old = $settings->get_settings();
		$settings->log_settings_update( $old, $new );

		// Ensure events were logged.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 2, $events );

		// Ensure General settings were logged.
		$this->assertEquals( 'sensei_settings_update', $events[0]['event_name'] );
		$this->assertEquals( 'default-settings', $events[0]['url_args']['view'] );
		$changed = explode( ',', $events[0]['url_args']['settings'] );
		sort( $changed );
		$this->assertEquals( [ 'sensei_delete_data_on_uninstall', 'sensei_video_embed_html_sanitization_disable' ], $changed );

		// Ensure Course settings were logged.
		$this->assertEquals( 'sensei_settings_update', $events[1]['event_name'] );
		$this->assertEquals( 'course-settings', $events[1]['url_args']['view'] );
		$changed = explode( ',', $events[1]['url_args']['settings'] );
		sort( $changed );
		$this->assertEquals( [ 'course_archive_featured_enable', 'course_archive_more_link_text' ], $changed );
	}

	/**
	 * Enabling HPPS or synchronization initializes missing tables.
	 *
	 * @covers Sensei_Settings::experimental_features_saved
	 * @dataProvider enabled_hpps_settings
	 */
	public function testExperimentalFeaturesSaved_HppsSettingEnabled_CreatesTables( string $setting ) {
		/* Arrange. */
		$settings = Sensei()->settings;

		$new                                  = $settings->get_settings();
		$new['experimental_progress_storage'] = true;
		$new[ $setting ]                      = true;

		$old             = $new;
		$old[ $setting ] = false;

		$this->simulateSettingsRequest();

		global $wpdb;
		// SHOW TABLES must inspect the recreated tables, rather than an older physical copy.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$tables = ( new Schema( Sensei()->feature_flags ) )->get_tables();
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		/* Act. */
		$settings->experimental_features_saved( $old, $new );

		/* Assert. */
		$created_tables = array();
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_col( "SHOW TABLES LIKE '{$table}'" );
			if ( in_array( $table, $results, true ) ) {
				$created_tables[] = $table;
			}
		}
		$this->assertEqualSets( $tables, $created_tables );
	}

	/**
	 * Both HPPS controls initialize the schema.
	 *
	 * @return array Settings that initialize the HPPS schema.
	 */
	public static function enabled_hpps_settings(): array {
		return array(
			'HPPS enabled'            => array( 'experimental_progress_storage' ),
			'synchronization enabled' => array( 'experimental_progress_storage_synchronization' ),
		);
	}

	/**
	 * Enabling synchronization must not discard existing HPPS progress.
	 *
	 * @covers Sensei_Settings::experimental_features_saved
	 */
	public function testExperimentalFeaturesSaved_SynchronizationEnabled_PreservesProgress(): void {
		/* Arrange. */
		global $wpdb;
		$this->synchronization_fixture = array(
			'option'   => get_option( 'sensei-settings' ),
			'settings' => Sensei()->settings->settings,
		);

		// The eraser discovers physical tables with SHOW TABLES.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		( new Schema( Sensei()->feature_flags ) )->create_tables();

		remove_filter( 'option_sensei-settings', array( Sensei_Unit_Tests_Bootstrap::instance(), 'enable_hpps_settings' ) );
		$settings = array( 'experimental_progress_storage' => true );
		update_option( 'sensei-settings', $settings );

		$course_id = ( new Sensei_Factory() )->course->create();
		$progress  = ( new Tables_Based_Course_Progress_Repository( $wpdb ) )->create( $course_id, 1 );
		$id        = $progress->get_id();

		$this->synchronization_fixture['progress'] = $progress;
		// phpcs:disable WordPress.DB -- Verify the persisted row rather than a cached model.
		$before = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}sensei_lms_progress WHERE id = {$id}", ARRAY_A );
		$this->simulateSettingsRequest();

		/* Act. */
		$settings['experimental_progress_storage_synchronization'] = true;
		update_option( 'sensei-settings', $settings );

		/* Assert. */
		$this->assertSame(
			$before,
			$wpdb->get_row( "SELECT * FROM {$wpdb->prefix}sensei_lms_progress WHERE id = {$id}", ARRAY_A ),
			'Enabling synchronization should preserve the progress record and its ID.'
		);
		// phpcs:enable WordPress.DB
	}

	public function testExperimentalFeaturesSaved_HppsSettingHasEnabled_LogsEvent() {
		/* Arrange. */
		$settings = Sensei()->settings;

		$new                                  = $settings->get_settings();
		$new['experimental_progress_storage'] = true;

		$old                                  = $settings->get_settings();
		$old['experimental_progress_storage'] = false;

		$this->simulateSettingsRequest();

		$has_logged_event = false;
		$sensei_log_event = function( $log_event, $event_name, $event_properties ) use ( &$has_logged_event ) {
			if ( 'hpps_status_change' === $event_name ) {
				$has_logged_event = true;
			}
		};
		add_action( 'sensei_log_event', $sensei_log_event, 10, 3 );

		/* Act. */
		$settings->experimental_features_saved( $old, $new );

		/* Assert. */
		$this->assertTrue( $has_logged_event );
	}

	public function testExperimentalFeaturesSaved_HppsRepositoryChanged_LogsEvent() {
		/* Arrange. */
		$settings = Sensei()->settings;

		$new                                  = $settings->get_settings();
		$new['experimental_progress_storage'] = true;
		$new['experimental_progress_storage_synchronization'] = true;
		$new['experimental_progress_storage_repository']      = 'comments';

		$old                                  = $settings->get_settings();
		$old['experimental_progress_storage'] = true;
		$old['experimental_progress_storage_synchronization'] = true;
		$old['experimental_progress_storage_repository']      = 'custom_tables';

		$this->simulateSettingsRequest();

		$has_logged_event = false;
		$sensei_log_event = function( $log_event, $event_name, $event_properties ) use ( &$has_logged_event ) {
			if ( 'hpps_repository_change' === $event_name ) {
				$has_logged_event = true;
			}
		};
		add_action( 'sensei_log_event', $sensei_log_event, 10, 3 );

		/* Act. */
		$settings->experimental_features_saved( $old, $new );

		/* Assert. */
		$this->assertTrue( $has_logged_event );
	}

	public function testBeforeExperimentalFeaturesSaved_HppsWasDisabled_DeletesOtherHppsSettings() {
		/* Arrange. */
		$settings = Sensei()->settings;

		$new                                  = $settings->get_settings();
		$new['experimental_progress_storage'] = false;
		$new['experimental_progress_storage_synchronization'] = true;
		$new['experimental_progress_storage_repository']      = 'custom_tables';

		$old                                  = $settings->get_settings();
		$old['experimental_progress_storage'] = true;
		$old['experimental_progress_storage_synchronization'] = true;
		$old['experimental_progress_storage_repository']      = 'custom_tables';

		$this->simulateSettingsRequest();

		/* Act. */
		$actual = $settings->before_experimental_features_saved( $new, $old );

		/* Assert. */
		$expected = array(
			'experimental_progress_storage_synchronization' => false,
			'experimental_progress_storage_repository' => false,
		);
		$actual   = array(
			'experimental_progress_storage_synchronization' => array_key_exists( 'experimental_progress_storage_synchronization', $actual ),
			'experimental_progress_storage_repository' => array_key_exists( 'experimental_progress_storage_repository', $actual ),
		);
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test logging of settings only on user update.
	 *
	 * @covers Sensei_Settings::log_settings_update
	 */
	public function testOnlyLogSettingsOnUserUpdate() {
		$settings = Sensei()->settings;
		$new      = $settings->get_settings();

		// Change a setting.
		$new['sensei_delete_data_on_uninstall'] = ! $new['sensei_delete_data_on_uninstall'];

		// Trigger update with new setting values.
		$old = $settings->get_settings();
		$settings->log_settings_update( $old, $new );

		// Ensure no events were logged.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 0, $events );
	}

	/**
	 * Test logging of array settings.
	 *
	 * @covers Sensei_Settings::log_settings_update
	 */
	public function testLogArraySettings() {
		$settings = Sensei()->settings;

		// Set "email_teachers" setting.
		$settings->set( 'email_teachers', [ 'teacher-started-course', 'teacher-completed-course' ] );

		// Get current settings.
		$new = $settings->get_settings();

		// Change the "email_teachers" setting.
		$new['email_teachers'] = [ '', 'teacher-started-course', 'teacher-completed-lesson' ];

		// Trigger update with new setting values.
		$this->simulateSettingsRequest();
		$old = $settings->get_settings();
		$settings->log_settings_update( $old, $new );

		// Ensure array setting change was logged.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 1, $events );

		$changed = explode( ',', $events[0]['url_args']['settings'] );
		sort( $changed );
		$this->assertEquals(
			[
				'teacher-completed-course',
				'teacher-completed-lesson',
			],
			$changed
		);
	}

	/**
	 * Test logging of non-string array settings.
	 *
	 * @covers Sensei_Settings::log_settings_update
	 */
	public function testLogNonStringArraySettings() {
		$settings = Sensei()->settings;

		// Set an array setting with non-string values.
		$settings->set( 'email_learners', [ 'a string', [ 'a sub-array' ] ] );

		// Get current settings.
		$new = $settings->get_settings();

		// Change the array setting.
		$new['email_learners'] = [ 'a different string', [ 'a different sub-array' ] ];

		// Trigger update with new setting values.
		$this->simulateSettingsRequest();
		$old = $settings->get_settings();
		$settings->log_settings_update( $old, $new );

		// Ensure array setting change was logged without values.
		$events = Sensei_Test_Events::get_logged_events();
		$this->assertCount( 1, $events );
		$changed = explode( ',', $events[0]['url_args']['settings'] );
		sort( $changed );
		$this->assertEquals( [ 'email_learners' ], $changed );
	}

	public function testGetSettings_Always_HasEmailCcAndBccSettings() {
		/** Arrange. */
		$settings = Sensei()->settings;

		/** Act. */
		$settings_array = $settings->get_settings();

		/** Assert. */
		$this->assertArrayHasKey( 'email_cc', $settings_array );
		$this->assertArrayHasKey( 'email_bcc', $settings_array );
	}

	public function testInitFields_Always_AddsEmailCcAndBccFields() {
		/** Arrange. */
		$settings         = Sensei()->settings;
		$settings->fields = array();

		/** Act. */
		$settings->init_fields();

		/** Assert. */
		$this->assertArrayHasKey( 'email_cc', $settings->fields );
		$this->assertArrayHasKey( 'email_bcc', $settings->fields );
	}

	/**
	 * Simulate Sensei settings update request.
	 */
	private function simulateSettingsRequest() {
		global $current_screen;

		$this->original_request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		$this->original_screen         = $current_screen;

		// Simulate the request.
		$_SERVER['REQUEST_METHOD'] = 'POST';
		set_current_screen( 'options' );
	}

	/**
	 * Reset values from simulating Sensei settings update request.
	 */
	private function resetSimulateSettingsRequest() {
		global $current_screen;

		if ( $this->original_request_method ) {
			$_SERVER['REQUEST_METHOD'] = $this->original_request_method;
		} else {
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}

		if ( $this->original_screen ) {
			$current_screen = $this->original_screen;
		} else {
			$current_screen = null;
		}

		$this->original_request_method = null;
		$this->original_screen         = null;
	}

	public function testMarkSectionAsVisited_SubscriberWithNonceGiven_DoesNotStoreTheSection() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['nonce']      = wp_create_nonce( 'sensei-mark-settings-section-visited' );
		$_POST['section_id'] = 'email-notification-settings';

		/* Act. */
		Sensei()->settings->mark_section_as_visited();

		/* Assert. */
		$this->assertSame( array(), get_option( Sensei_Settings::VISITED_SECTIONS_OPTION_KEY, array() ) );
	}

	public function testMarkSectionAsVisited_AdministratorWithNonceGiven_StoresTheSection() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$_POST['nonce']      = wp_create_nonce( 'sensei-mark-settings-section-visited' );
		$_POST['section_id'] = 'email-notification-settings';

		/* Act. */
		Sensei()->settings->mark_section_as_visited();

		/* Assert. */
		$this->assertSame( array( 'email-notification-settings' ), get_option( Sensei_Settings::VISITED_SECTIONS_OPTION_KEY, array() ) );
	}
}

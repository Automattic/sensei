<?php
/**
 * Tests for Sensei_Exit_Survey.
 *
 * @covers Sensei_Exit_Survey
 */
class Sensei_Exit_Survey_Test extends WP_UnitTestCase {
	use Sensei_Test_Login_Helpers;

	public function setUp(): void {
		parent::setUp();

		// The handler reports the deactivation to the tracking service.
		add_filter( 'pre_http_request', '__return_empty_array' );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', '__return_empty_array' );

		parent::tearDown();
	}

	public function testSaveExitSurvey_SubscriberWithNonceGiven_DoesNotStoreTheFeedback() {
		/* Arrange. */
		$this->login_as_student();
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'sensei_exit_survey' );
		$_POST['reason']      = 'other';

		/* Act. */
		try {
			( new Sensei_Exit_Survey() )->save_exit_survey();
		} catch ( WPDieException $e ) {
			unset( $e );
		}

		/* Assert. */
		$this->assertFalse( get_option( 'sensei_exit_survey_data' ) );
	}

	public function testSaveExitSurvey_AdministratorWithNonceGiven_StoresTheFeedback() {
		/* Arrange. */
		// Let site administrators activate and deactivate plugins on multisite.
		update_site_option( 'menu_items', array( 'plugins' => 1 ) );
		$this->login_as_admin();

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'sensei_exit_survey' );
		$_POST['reason']      = 'other';

		/* Act. */
		( new Sensei_Exit_Survey() )->save_exit_survey();

		/* Assert. */
		$this->assertSame( 'other', get_option( 'sensei_exit_survey_data' )['reason'] );
	}
}

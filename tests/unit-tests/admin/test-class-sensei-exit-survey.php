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
		revoke_super_admin( get_current_user_id() );

		parent::tearDown();
	}

	/**
	 * Users allowed to deactivate plugins can submit feedback.
	 *
	 * @covers Sensei_Exit_Survey::save_exit_survey
	 */
	public function testSaveExitSurvey_PluginAdministratorWithNonceGiven_StoresTheFeedback() {
		/* Arrange. */
		$this->login_as_admin();
		grant_super_admin( get_current_user_id() );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'sensei_exit_survey' );
		$_POST['reason']      = 'other';

		/* Act. */
		( new Sensei_Exit_Survey() )->save_exit_survey();

		/* Assert. */
		$this->assertSame( 'other', get_option( 'sensei_exit_survey_data' )['reason'] );
	}
}

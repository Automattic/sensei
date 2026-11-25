<?php
/**
 * Sensei REST API Extensions Controller Unit Tests.
 *
 * @covers Sensei_REST_API_Extensions_Controller
 */
class Sensei_REST_API_Extensions_Controller_Test extends WP_Test_REST_TestCase {
	use Sensei_Test_Login_Helpers;

	/**
	 * A server instance that we use in tests to dispatch requests.
	 *
	 * @var WP_REST_Server $server
	 */
	protected $server;

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );
	}

	public function tearDown(): void {
		revoke_super_admin( get_current_user_id() );

		parent::tearDown();
	}

	/**
	 * Plugin management permissions alone do not allow installation.
	 *
	 * @covers Sensei_REST_API_Extensions_Controller::can_user_install_plugins
	 */
	public function testInstallExtension_UserWithoutInstallCapabilityGiven_ReturnsForbidden() {
		/* Arrange. */
		$this->login_as_admin();
		grant_super_admin( get_current_user_id() );
		// Super admins bypass user capabilities unless mapping explicitly denies access.
		$deny_install = static function ( $caps, $cap ) {
			return 'install_plugins' === $cap ? array( 'do_not_allow' ) : $caps;
		};
		add_filter( 'map_meta_cap', $deny_install, 10, 2 );

		$request = new WP_REST_Request( 'POST', '/sensei-internal/v1/sensei-extensions/install' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'plugin' => 'woocommerce' ) ) );

		/* Act. */
		try {
			$response = $this->server->dispatch( $request );
		} finally {
			remove_filter( 'map_meta_cap', $deny_install, 10 );
		}

		/* Assert. */
		$this->assertSame( 'rest_cannot_install_plugins', $response->as_error()->get_error_code() );
	}

	/**
	 * Plugin administrators can install extensions.
	 *
	 * @covers Sensei_REST_API_Extensions_Controller::can_user_install_plugins
	 */
	public function testCanUserInstallPlugins_PluginAdministratorGiven_ReturnsTrue() {
		/* Arrange. */
		$this->login_as_admin();
		grant_super_admin( get_current_user_id() );

		/* Act. */
		$actual = ( new Sensei_REST_API_Extensions_Controller( 'sensei-internal/v1' ) )->can_user_install_plugins( new WP_REST_Request() );

		/* Assert. */
		$this->assertTrue( $actual );
	}
}

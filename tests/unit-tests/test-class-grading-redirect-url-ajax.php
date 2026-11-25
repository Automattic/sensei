<?php
/**
 * AJAX tests for the grading redirect URL endpoint (`get_redirect_url`).
 *
 * @group ajax-calls
 *
 * @covers Sensei_Grading::get_redirect_url
 */
class Sensei_Grading_Redirect_Url_AJAX_Test extends WP_Ajax_UnitTestCase {

	/**
	 * Factory for creating test data.
	 *
	 * @var Sensei_Factory
	 */
	protected $factory;

	/**
	 * HTTP status the handler died with, captured by the wrapped die handler.
	 *
	 * @var int|null
	 */
	private $die_status;

	public function setUp(): void {
		parent::setUp();
		$this->factory = new Sensei_Factory();
		add_filter( 'pre_http_request', '__return_empty_array' );

		// Sensei only hooks this handler when it loads in an admin context, which the
		// test bootstrap is not.
		add_action( 'wp_ajax_get_redirect_url', array( Sensei()->grading, 'get_redirect_url' ) );

		// The die handler of WP_Ajax_UnitTestCase only receives the message, so wrap it to
		// record the status the handler died with.
		add_filter(
			'wp_die_ajax_handler',
			function () {
				return function ( $message, $title = '', $args = array() ) {
					$this->die_status = $args['response'] ?? null;
					$this->dieHandler( $message );
				};
			},
			20
		);
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->factory->tearDown();
	}

	public function testGetRedirectUrl_CalledWithoutGradingCapability_RefusesTheRequest() {
		/* Arrange. */
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		/* Act. */
		$this->get_redirect_url( $this->factory->course->create(), $this->factory->lesson->create() );

		/* Assert. */
		$this->assertSame( 403, $this->die_status );
	}

	public function testGetRedirectUrl_CalledWithGradingCapability_ReturnsTheGradingUrl() {
		/* Arrange. */
		$lesson_id = $this->factory->lesson->create();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'teacher' ) ) );

		/* Act. */
		$response = $this->get_redirect_url( $this->factory->course->create(), $lesson_id );

		/* Assert. */
		$this->assertStringContainsString( 'lesson_id=' . $lesson_id, $response );
	}

	/**
	 * Dispatch the `get_redirect_url` AJAX action and return the raw response.
	 *
	 * @param int $course_id Course selected on the Grading screen.
	 * @param int $lesson_id Lesson selected on the Grading screen.
	 * @return string Response body.
	 */
	private function get_redirect_url( int $course_id, int $lesson_id ): string {
		$_GET['course_id'] = $course_id;
		$_GET['lesson_id'] = $lesson_id;

		try {
			// Stop is thrown when the handler dies without printing anything, as a denied
			// request does; Continue when it dies after printing the URL.
			$this->_handleAjax( 'get_redirect_url' );
		} catch ( WPAjaxDieStopException | WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return $this->_last_response;
	}
}

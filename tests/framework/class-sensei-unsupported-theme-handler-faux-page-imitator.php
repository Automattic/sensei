<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test shim for page imitator abstract class.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator
	extends Sensei_Unsupported_Theme_Handler_Page_Imitator
	implements Sensei_Unsupported_Theme_Handler_Interface {

	const TEST_CONTENT = '~~TEST CONTENT~~';
	const TEST_ACTION  = 'fuax-page-imitator-action';

	/**
	 * @var WP_Post $test_page
	 */
	private $test_page;

	/**
	 * Sensei_Unsupported_Theme_Handler_Faux_Page_Imitator constructor.
	 *
	 * @param WP_Post $test_page
	 */
	public function __construct( $test_page ) {
		$this->test_page = $test_page;
	}

	/**
	 * We can handle this request.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return true;
	}

	/**
	 * Set up handling for a faux page.
	 *
	 * This is done by manually rendering the content for the page, creating a
	 * dummy post object, setting its content to the rendered content we generated,
	 * and then forcing WordPress to render that post.
	 * Adapted from WooCommerce and bbPress.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		$test_page = $this->test_page;

		// Render the module page and output it as a Page.
		$content = $this->render_page();
		$this->output_content_as_page(
			$content,
			$test_page,
			array(
				'post_title' => sanitize_text_field( $test_page->post_title ),
				'post_name'  => $test_page->post_name,
			)
		);
	}

	/**
	 * Return the test content.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	private function render_page() {
		do_action( self::TEST_ACTION );
		return self::TEST_CONTENT;
	}

	/**
	 * Prepare the WP query object for the imitated request.
	 *
	 * @param WP_Query $wp_query
	 * @param WP_Post  $post_to_copy
	 * @param array    $post_params
	 */
	protected function prepare_wp_query( $wp_query, $post_to_copy, $post_params ) {
		$wp_query->query_vars['is_test'] = true;
	}

}

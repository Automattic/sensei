<?php
/**
 * File containing the class Sensei_Course_Outline_Block.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Outline_Block
 */
class Sensei_Course_Outline_Block {
	/**
	 * Register course outline block.
	 */
	public function register_block_type() {
		register_block_type(
			'sensei-lms/course-outline',
			[
				'render_callback' => [ $this, 'render' ],
				'editor_script'   => 'sensei-course-builder',
				'attributes'      => [
					'course_id' => [
						'type' => 'int',
					],
				],
				'supports'        => [],
			]
		);
	}

	/**
	 * Render course outline block.
	 *
	 * @param array  $attributes
	 * @param string $content
	 *
	 * @return string
	 */
	public function render( $attributes, $content ) {

		global $post;

		$loader   = new \Twig\Loader\ArrayLoader( [] );
		$twig     = new \Twig\Environment( $loader );
		$template = $twig->createTemplate( $content );

		// Call the same API as the block editor.
		$request  = new WP_REST_Request( 'GET', '/sensei-internal/v1/course-builder/course-lessons/' . $post->ID );
		$response = rest_do_request( $request );
		$server   = rest_get_server();
		$data     = $server->response_to_data( $response, false );

		return $template->render( [ 'data' => $data ] );
	}
}

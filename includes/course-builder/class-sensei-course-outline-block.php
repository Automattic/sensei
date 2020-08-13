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

		$templates = explode( '## template ##', $content );

		$loader = new \Twig\Loader\ArrayLoader( [] );
		$twig = new \Twig\Environment( $loader );
		$lesson_template = $twig->createTemplate( $templates[ 1 ] );

		$lessons = Sensei()->course_outline->course_lessons( $post->ID );

		$r = '<div style="border-left: 2px solid #32af7d; padding: 1rem; "><h1>Course outline</h1>';

		foreach ( $lessons->posts as $lesson ) {
			$r .= $lesson_template->render( [ 'href' => get_permalink( $lesson ), 'lessonTitle' => $lesson->post_title ] );
		}

		$r .= '</div>';
		return $r;
	}
}

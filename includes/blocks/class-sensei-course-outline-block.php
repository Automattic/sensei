<?php
/**
 * File containing the Sensei_Course_Outline_Block class.
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
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_assets' ] );
		add_action( 'init', [ $this, 'register_course_template' ], 101 );
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-course-outline-script', 'blocks/course-outline/index.js' );
		Sensei()->assets->enqueue( 'sensei-course-outline-style', 'blocks/course-outline/style.css' );
	}

	/**
	 * Register course template.
	 *
	 * @access private
	 */
	public function register_course_template() {
		$post_type_object = get_post_type_object( 'course' );

		$post_type_object->template = [
			[ 'sensei-lms/course-outline' ],
		];
	}

	/**
	 * Register course outline block.
	 *
	 * @access private
	 */
	public function register_block() {
		register_block_type(
			'sensei-lms/course-outline',
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Render dynamic block.
	 *
	 * @access private
	 *
	 * @param array $attributes Block attributes.
	 */
	public function render_callback( $attributes ) {
		// TODO: Fetch from API or method used in API.
		$data = [
			[
				'id'          => 1,
				'type'        => 'module',
				'title'       => 'Module 1',
				'description' => 'Module description 1',
				'lessons'     => [
					[
						'id'    => 2,
						'type'  => 'lesson',
						'title' => 'Lesson 2',
					],
					[
						'id'    => 3,
						'type'  => 'lesson',
						'title' => 'Lesson 3',
					],
				],
			],
			[
				'id'    => 9,
				'type'  => 'lesson',
				'title' => 'Lesson 9',
			],
			[
				'id'    => 10,
				'type'  => 'lesson',
				'title' => 'Lesson 10',
			],
			[
				'id'          => 4,
				'type'        => 'module',
				'title'       => 'Module 4',
				'description' => 'Module description 4',
				'lessons'     => [
					[
						'id'    => 5,
						'type'  => 'lesson',
						'title' => 'Lesson 5',
					],
				],
			],
			[
				'id'          => 6,
				'type'        => 'module',
				'title'       => 'Module 6',
				'description' => 'Module description 6',
				'lessons'     => [],
			],
			[
				'id'    => 7,
				'type'  => 'lesson',
				'title' => 'Lesson 7',
			],
		];

		$this->disable_course_legacy_content();

		$block_class = 'wp-block-sensei-lms-course-outline';
		if ( isset( $attributes['className'] ) ) {
			$block_class .= ' ' . $attributes['className'];
		}

		return '
			<section class="' . $block_class . '">
				' .
				implode(
					'',
					array_map(
						function( $block ) {
							if ( 'module' === $block['type'] ) {
								return $this->get_module_block_html( $block );
							}

							if ( 'lesson' === $block['type'] ) {
								return $this->get_lesson_block_html( $block );
							}
						},
						$data
					)
				)
				. '
			</section>
		';
	}

	/**
	 * Get lesson block HTML.
	 *
	 * @param array $block Block information.
	 *
	 * @return string Lesson HTML
	 */
	private function get_lesson_block_html( $block ) {
		return '
			<div class="wp-block-sensei-lms-course-outline-lesson">
			' . $block['title'] . '
			</div>
		';
	}

	/**
	 * Get module block HTML.
	 *
	 * @param array $block Block information.
	 *
	 * @return string Module HTML
	 */
	private function get_module_block_html( $block ) {
		return '
			<section class="wp-block-sensei-lms-course-outline-module">
				<header class="wp-block-sensei-lms-course-outline-module__name">
					' . $block['title'] . '
				</header>
				<div class="wp-block-sensei-lms-course-outline-module__description">
					' . $block['description'] . '
				</div>
				<div class="wp-block-sensei-lms-course-outline-module__lessons-title">
					' . __( 'Lessons', 'sensei-lms' ) . '
				</div>
				' .
				implode(
					'',
					array_map(
						[ $this, 'get_lesson_block_html' ],
						$block['lessons']
					)
				)
				. '
			</section>
		';
	}

	/**
	 * Disable course legacy content.
	 */
	private function disable_course_legacy_content() {
		// TODO: Check the best approach for backwards compatibility.
		remove_action( 'sensei_single_course_content_inside_after', 'course_single_lessons' );
		remove_action( 'sensei_single_course_content_inside_after', [ 'Sensei_Course', 'the_course_lessons_title' ], 9 );
		remove_action( 'sensei_single_course_content_inside_after', [ Sensei()->modules, 'load_course_module_content_template' ], 8 );
	}

}

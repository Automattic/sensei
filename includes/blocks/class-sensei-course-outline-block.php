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
	 * Attributes for inner blocks.
	 *
	 * @var array[]
	 */
	private $block_attributes = [
		'lesson' => [],
		'module' => [],
	];

	/**
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'init', [ $this, 'register_course_template' ], 101 );
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-single-course', 'blocks/single-course.css' );

		if ( ! is_admin() ) {
			Sensei()->assets->enqueue( 'sensei-single-course-frontend', 'blocks/course-outline/frontend.js' );
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
		if ( 'course' !== get_post_type() ) {
			return;
		}

		Sensei()->assets->enqueue( 'sensei-blocks', 'blocks/index.js' );
		Sensei()->assets->enqueue( 'sensei-single-course-editor', 'blocks/single-course.editor.css' );
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
	public function register_blocks() {
		register_block_type_from_metadata(
			Sensei()->assets->src_path( 'blocks/course-outline/course-block' ),
			[
				'render_callback' => [ $this, 'render_course_outline_block' ],
			]
		);

		register_block_type(
			'sensei-lms/course-outline-lesson',
			[
				'render_callback' => [ $this, 'process_lesson_block' ],
				'attributes'      => [
					'id' => [
						'type' => 'integer',
					],
				],
			]
		);

		register_block_type(
			'sensei-lms/course-outline-module',
			[
				'render_callback' => [ $this, 'process_module_block' ],
				'attributes'      => [
					'id' => [
						'type' => 'integer',
					],
				],
				'script'          => 'sensei-course-outline-frontend',
			]
		);
	}

	/**
	 * Extract attributes from module block.
	 *
	 * @param array $attributes
	 *
	 * @access private
	 * @return string
	 */
	public function process_lesson_block( $attributes ) {
		if ( ! empty( $attributes['id'] ) ) {
			$this->block_attributes['lesson'][ $attributes['id'] ] = $attributes;
		}

		return '';
	}

	/**
	 * Extract attributes from module block.
	 *
	 * @param array $attributes
	 *
	 * @access private
	 * @return string
	 */
	public function process_module_block( $attributes ) {
		if ( ! empty( $attributes['id'] ) ) {
			$this->block_attributes['module'][ $attributes['id'] ] = $attributes;
		}
		return '';
	}

	/**
	 * Add attributes from matching blocks to modules and lessons in course structure.
	 *
	 * @param array $structure Course structure.
	 */
	private function add_block_attributes( &$structure ) {
		if ( empty( $structure ) ) {
			return;
		}
		foreach ( $structure as &$block ) {
			$block['attributes'] = $this->block_attributes[ $block['type'] ][ $block['id'] ] ?? [];
			if ( ! empty( $block['lessons'] ) ) {
				self::add_block_attributes( $block['lessons'] );
			}
		}
	}

	/**
	 * Render Course Outline block.
	 *
	 * @access private
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string Block HTML.
	 */
	public function render_course_outline_block( $attributes ) {

		global $post;

		$structure = Sensei_Course_Structure::instance( $post->ID )->get( 'view' );

		$this->add_block_attributes( $structure );

		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $attributes );
		$css        = Sensei_Block_Helpers::build_styles(
			[
				'attributes' => $attributes,
			]
		);

		$icons = '<svg xmlns="http://www.w3.org/2000/svg" style="display: none">
			<symbol id="sensei-chevron-right" viewBox="0 0 24 24">
				<path d="M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" fill="" />
			</symbol>
			<symbol id="sensei-chevron-up" viewBox="0 0 24 24">
				<path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" fill="" />
			</symbol>
			<symbol id="sensei-checked" viewBox="0 0 24 24">
				<path d="M9 18.6L3.5 13l1-1L9 16.4l9.5-9.9 1 1z" fill="" />
			</symbol>
		</svg>';

		return '
			' . ( ! empty( $structure ) ? $icons : '' ) . '
			<section ' . Sensei_Block_Helpers::render_style_attributes( [ 'wp-block-sensei-lms-course-outline', $class_name ], $css ) . '>
				' .
			implode(
				'',
				array_map(
					function( $block ) use ( $post, $attributes ) {
						if ( 'module' === $block['type'] ) {
							return $this->render_module_block( $block, $post->ID, $attributes );
						}

						if ( 'lesson' === $block['type'] ) {
							return $this->render_lesson_block( $block );
						}
					},
					$structure
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
	protected function render_lesson_block( $block ) {
		$lesson_id = $block['id'];
		$classes   = [ 'wp-block-sensei-lms-course-outline-lesson' ];

		$completed = Sensei_Utils::user_completed_lesson( $lesson_id, get_current_user_id() );

		if ( $completed ) {
			$classes[] = 'completed';
		}

		$css = Sensei_Block_Helpers::build_styles( $block );

		return '
			<a href="' . esc_url( get_permalink( $lesson_id ) ) . '" ' . Sensei_Block_Helpers::render_style_attributes( $classes, $css ) . '>
				<svg class="wp-block-sensei-lms-course-outline-lesson__status">
					' . ( $completed ? '<use xlink:href="#sensei-checked"></use>' : '' ) . '
				</svg>
				<span>
					' . esc_html( $block['title'] ) . '
				</span>
				<svg class="wp-block-sensei-lms-course-outline-lesson__chevron"><use xlink:href="#sensei-chevron-right"></use></svg>
			</a>
		';
	}

	/**
	 * Get module block HTML.
	 *
	 * @param array $block              Module block attributes.
	 * @param int   $course_id          The course id.
	 * @param array $outline_attributes Outline block attributes.
	 *
	 * @return string Module HTML
	 */
	private function render_module_block( $block, $course_id, $outline_attributes ) {
		if ( empty( $block['lessons'] ) ) {
			return '';
		}

		$progress_indicator = $this->get_progress_indicator( $block['id'], $course_id );

		$class_name = Sensei_Block_Helpers::block_class_with_default_style( $block['attributes'] );

		$is_default_style = false !== strpos( $class_name, 'is-style-default' );
		$is_minimal_style = false !== strpos( $class_name, 'is-style-minimal' );

		$header_css = Sensei_Block_Helpers::build_styles(
			$block,
			[
				'mainColor' => $is_default_style ? 'background-color' : null,
			]
		);

		$style_header = '';

		if ( $is_minimal_style ) {

			$header_border_css = Sensei_Block_Helpers::build_styles(
				$block,
				[
					'mainColor' => 'background-color',
				]
			);

			$style_header = '<div ' . Sensei_Block_Helpers::render_style_attributes( 'wp-block-sensei-lms-course-outline-module__name__minimal-border', $header_border_css ) . '></div>';

		}

		return '
			<section class="wp-block-sensei-lms-course-outline-module ' . esc_attr( $class_name ) . '">
				<header ' . Sensei_Block_Helpers::render_style_attributes( 'wp-block-sensei-lms-course-outline-module__header', $header_css ) . '>
					<h2 class="wp-block-sensei-lms-course-outline-module__title">' . esc_html( $block['title'] ) . '</h2>
					' . $progress_indicator .
			( ! empty( $outline_attributes['collapsibleModules'] ) ?
				'<button type="button" class="wp-block-sensei-lms-course-outline__arrow">
						<svg><use xlink:href="#sensei-chevron-up"></use></svg>
						<span class="screen-reader-text">' . esc_html__( 'Toggle module content', 'sensei-lms' ) . '</span>
					</button>' : '' ) .
			'</header>
					' . $style_header . '
				<div class="wp-block-sensei-lms-collapsible">
					<div class="wp-block-sensei-lms-course-outline-module__description">
						' . wp_kses_post( $block['description'] ) . '
					</div>
							<h3 class="wp-block-sensei-lms-course-outline-module__lessons-title">
								' . esc_html__( 'Lessons', 'sensei-lms' ) . '
							</h3>
						' .
			implode(
				'',
				array_map(
					[ $this, 'render_lesson_block' ],
					$block['lessons']
				)
			)
			. '
				</div>
			</section>
		';
	}

	/**
	 * Get progress indicator HTML.
	 *
	 * @param array $module_id The module id.
	 * @param int   $course_id The course id.
	 *
	 * @return string Module HTML
	 */
	private function get_progress_indicator( $module_id, $course_id ) {

		$module_progress = Sensei()->modules->get_user_module_progress( $module_id, $course_id, get_current_user_id() );

		if ( empty( $module_progress ) ) {
			return '';
		}

		if ( $module_progress < 100 ) {
			$module_status   = __( 'In Progress', 'sensei-lms' );
			$indicator_class = '';
		} else {
			$module_status   = __( 'Completed', 'sensei-lms' );
			$indicator_class = 'completed';
		}

		return '
					<div
						class="wp-block-sensei-lms-course-outline-module__progress-indicator ' . $indicator_class . '"
					>
						<span class="wp-block-sensei-lms-course-outline-module__progress-indicator__text"> ' . esc_html( $module_status ) . ' </span>
					</div>
		';
	}

}

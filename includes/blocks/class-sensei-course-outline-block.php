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
		'course' => [],
	];

	/**
	 * Course outline parent block.
	 *
	 * @var Sensei_Course_Outline_Course_Block
	 */
	public $course;

	/**
	 * Course outline module block.
	 *
	 * @var Sensei_Course_Outline_Module_Block
	 */
	public $module;

	/**
	 * Course outline module block
	 *
	 * @var Sensei_Course_Outline_Lesson_Block
	 */
	public $lesson;

	/**
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'init', [ $this, 'register_course_template' ], 101 );
		add_action( 'init', [ $this, 'register_blocks' ] );

		$this->course = new Sensei_Course_Outline_Course_Block();
		$this->lesson = new Sensei_Course_Outline_Lesson_Block();
		$this->module = new Sensei_Course_Outline_Module_Block();
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
			[ 'sensei-lms/button-take-course' ],
			[ 'sensei-lms/course-progress' ],
			[ 'sensei-lms/button-contact-teacher' ],
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

		register_block_type_from_metadata(
			Sensei()->assets->src_path( 'blocks/course-outline/module-block' ),
			[
				'render_callback' => [ $this, 'process_module_block' ],
				'script'          => 'sensei-course-outline-frontend',
			]
		);

		register_block_type_from_metadata(
			Sensei()->assets->src_path( 'blocks/course-outline/lesson-block' ),
			[
				'render_callback' => [ $this, 'process_lesson_block' ],
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
	 * Get blocks to render, based on course structure.
	 */
	public function get_block_structure() {
		global $post;

		$structure = Sensei_Course_Structure::instance( $post->ID )->get( 'view' );

		$this->add_block_attributes( $structure );

		return [
			'post_id'    => $post->ID,
			'attributes' => $this->block_attributes['course'],
			'blocks'     => $structure,
		];

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
		$this->block_attributes['course'] = $attributes;

		$outline = $this->get_block_structure();

		return $this->course->render_course_outline_block( $outline );

	}

}

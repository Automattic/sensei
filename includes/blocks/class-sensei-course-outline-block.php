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
	 * Rendered HTML output for the block.
	 *
	 * @var string
	 */
	private $block_content;

	/**
	 * Sensei_Course_Outline_Block constructor.
	 */
	public function __construct() {
		add_filter( 'sensei_use_sensei_template', [ 'Sensei_Course_Outline_Block', 'skip_single_course_template' ] );

		add_action( 'init', [ $this, 'register_course_template' ], 101 );
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'init', [ $this, 'init' ] );

		$this->course = new Sensei_Course_Outline_Course_Block();
		$this->lesson = new Sensei_Course_Outline_Lesson_Block();
		$this->module = new Sensei_Course_Outline_Module_Block();
	}

	/**
	 * Initialize block instance.
	 *
	 * @access private
	 */
	public function init() {
		$this->block_content = null;
	}

	/**
	 * Disable single course template if there is an outline block present.
	 *
	 * @access private
	 *
	 * @param bool $enabled
	 *
	 * @return bool
	 */
	public static function skip_single_course_template( $enabled ) {
		return is_single() && 'course' === get_post_type() && has_block( 'sensei-lms/course-outline' )
			? false
			: $enabled;
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

		$context    = 'view';
		$attributes = $this->block_attributes['course'];
		$is_preview = is_preview() && $this->can_current_user_edit_course( $post->ID );

		if ( $is_preview ) {
			$context = 'edit';
		}

		$structure = Sensei_Course_Structure::instance( $post->ID )->get( $context );

		if ( $is_preview ) {
			$attributes['preview_drafts'] = $this->has_draft( $structure );
		}

		$this->add_block_attributes( $structure );

		return [
			'post_id'    => $post->ID,
			'attributes' => $attributes,
			'blocks'     => $structure,
		];

	}

	/**
	 * Check if the course has draft lessons or empty modules.
	 *
	 * @param array $blocks The course structure.
	 *
	 * @return bool Has draft lessons/modules.
	 */
	private function has_draft( $blocks ) {
		foreach ( $blocks as $block ) {
			switch ( $block['type'] ) {
				case 'lesson':
					if ( $block['draft'] ) {
						return true;
					}
					break;
				case 'module':
					if ( empty( $block['lessons'] ) ) {
						return true;
					}
					if ( $this->has_draft( $block['lessons'] ) ) {
						return true;
					}
					break;
			}
		}
		return false;
	}

	/**
	 * Check user permission for editing a course.
	 *
	 * @param int $course_id Course post ID.
	 *
	 * @return bool Whether the user can edit the course.
	 */
	private function can_current_user_edit_course( $course_id ) {
		return current_user_can( get_post_type_object( 'course' )->cap->edit_post, $course_id );
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
		if ( $this->block_content ) {
			return $this->block_content;
		}
		$this->block_attributes['course'] = $attributes;

		$outline = $this->get_block_structure();

		$this->block_content = $this->course->render_course_outline_block( $outline );
		return $this->block_content;

	}

}

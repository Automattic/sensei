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
		$this->course = new Sensei_Course_Outline_Course_Block();
		$this->lesson = new Sensei_Course_Outline_Lesson_Block();
		$this->module = new Sensei_Course_Outline_Module_Block();

		$this->register_blocks();

		add_action( 'wp', [ $this, 'frontend_notices' ] );
	}

	/**
	 * Resets block content.
	 *
	 * @access private
	 */
	public function clear_block_content() {
		$this->block_content = null;
	}

	/**
	 * Register course outline block.
	 *
	 * @access private
	 */
	public function register_blocks() {
		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-outline',
			[
				'render_callback' => [ $this, 'render_course_outline_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-outline/outline-block' )
		);

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-outline-module',
			[
				'render_callback' => [ $this, 'process_module_block' ],
				'script'          => 'sensei-course-outline-frontend',
			],
			Sensei()->assets->src_path( 'blocks/course-outline/module-block' )
		);

		Sensei_Blocks::register_sensei_block(
			'sensei-lms/course-outline-lesson',
			[
				'render_callback' => [ $this, 'process_lesson_block' ],
			],
			Sensei()->assets->src_path( 'blocks/course-outline/lesson-block' )
		);

	}

	/**
	 * Extract attributes from module block.
	 *
	 * @param array $attributes Block attributes.
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
	 * @param array $attributes Block attributes.
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

		if ( ! $post ) {
			return [];
		}

		$context    = 'view';
		$attributes = $this->block_attributes['course'];
		$is_preview = is_preview() && Sensei_Course::can_current_user_edit_course( $post->ID );

		if ( $is_preview ) {
			$context = 'edit';
		}

		$structure = Sensei_Course_Structure::instance( $post->ID )->get( $context );

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

	/**
	 * Initialize notices.
	 *
	 * @internal
	 *
	 * @since 4.20.1
	 */
	public function frontend_notices() {
		$post = get_post();

		if ( ! is_object( $post ) || 'course' !== $post->post_type || is_admin() ) {
			return;
		}

		$course_id       = $post->ID;
		$structure       = Sensei_Course_Structure::instance( $course_id )->get( 'view' );
		$has_draft       = $this->has_draft( $structure );
		$can_edit_course = Sensei_Course::can_current_user_edit_course( $course_id );

		// Notice for empty structure. Notice that draft lessons don't return for students, so it's considered empty.
		if ( empty( $structure ) ) {
			$message = __( 'There are no published lessons in this course yet.', 'sensei-lms' );

			if ( $can_edit_course ) {
				$link = add_query_arg(
					[
						'post_type'     => 'lesson',
						'lesson_course' => $course_id,
					],
					admin_url( 'edit.php' )
				);
				$cta  = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $link ),
					__( 'Add some now.', 'sensei-lms' )
				);

				$message = $message . ' ' . $cta;
			}

			Sensei()->notices->add_notice( $message, 'info', 'sensei-course-outline-no-content' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['draftcourse'] ) && 'true' === $_GET['draftcourse'] ) {
			// Notice when trying to register in a draft course.
			$message = __( 'Cannot register for an unpublished course.', 'sensei-lms' );

			if ( $can_edit_course ) {
				$cta = sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_edit_post_link( $course_id ) ?? '' ),
					__( 'publish the course', 'sensei-lms' )
				);

				$publish_message = sprintf(
					/* translators: %s: Link to publish the course. */
					__( 'Please %s first.', 'sensei-lms' ),
					$cta
				);

				$message = $message . ' ' . $publish_message;
			}

			Sensei()->notices->add_notice( $message, 'info', 'sensei-course-outline-drafts' );

		} elseif ( $has_draft ) {
			// Notice for draft lessons. It only happens for who can read private posts, otherwise draft lessons aren't returned.
			$message = __( 'Draft lessons are only visible in preview mode.', 'sensei-lms' );

			if ( $can_edit_course ) {
				$link = add_query_arg(
					[
						'post_status'   => 'draft',
						'post_type'     => 'lesson',
						'lesson_course' => $course_id,
					],
					admin_url( 'edit.php' )
				);
				$cta  = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $link ),
					__( 'your lessons', 'sensei-lms' )
				);

				$edit_lessons_message = sprintf(
					/* translators: %s: Edit course link. */
					__( "When you're ready, let's publish %s in order to make them available to your students.", 'sensei-lms' ),
					$cta
				);

				$message = $edit_lessons_message . ' ' . $message;
			}

			Sensei()->notices->add_notice( $message, 'info', 'sensei-course-outline-drafts' );
		}
	}

}

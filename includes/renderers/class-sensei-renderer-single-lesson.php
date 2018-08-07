<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 *
 * Renders a single Sensei lesson based on the given ID. The rendered result is
 * meant to be displayed on the frontend, and may be used by shortcodes or
 * other rendering code.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Renderer_Single_Lesson {

	/**
	 * @var int $lesson_id The ID of the lesson to render.
	 */
	private $lesson_id;

	/**
	 * @var bool $show_pagination Whether or not to render pagination links.
	 */
	private $show_pagination;

	/**
	 * @var WP_Query $lesson_page_query The query for the Lesson post.
	 */
	protected $lesson_page_query;

	/**
	 * @var WP_Post $global_post_ref Backup of the global $post variable.
	 */
	protected $global_post_ref;

	/**
	 * @var WP_Query $global_wp_query_ref Backup of the global $wp_query variable.
	 */
	protected $global_wp_query_ref;

	/**
	 * @var array $global_pages_ref Backup of the global $pages variable.
	 */
	protected $global_pages_ref;

	/**
	 * Setup the renderer object
	 *
	 * @since 1.12.0
	 *
	 * @param int   $lesson_id  The lesson ID.
	 * @param array $options {
	 *   @type bool show_pagination Whether to show pagination on the lesson page.
	 * }
	 */
	public function __construct( $lesson_id, $options = array() ) {
		$this->lesson_id = $lesson_id;
		$this->show_pagination = isset( $options['show_pagination'] ) ? $options['show_pagination'] : false;
		$this->setup_lesson_query();
	}

	/**
	 * Render and return the content. This will use the 'single-lesson.php'
	 * template, and will use an overridden version if it exists.
	 *
	 * @return string The rendered output.
	 */
	public function render() {
		// Set the wp_query to the current lessons query.
		global $wp_query, $post, $pages;

		$this->backup_global_vars();
		$this->set_global_vars();

		// Capture output.
		ob_start();
		add_filter( 'sensei_show_main_footer', '__return_false' );
		add_filter( 'sensei_show_main_header', '__return_false' );
		add_action( 'sensei_single_lesson_lessons_before', array( $this, 'set_global_vars' ), 1, 0 );
		Sensei_Templates::get_template( 'single-lesson.php' );
		if ( $this->show_pagination ) {
			do_action( 'sensei_pagination' );
		}
		$output = ob_get_clean();

		$this->reset_global_vars();

		return $output;
	}

	/**
	 * Create the lessons query.
	 */
	private function setup_lesson_query(){
		if ( empty( $this->lesson_id ) ) {
			return;
		}

		$args = array(
			'p'              => $this->lesson_id,
			'post_type'      => 'lesson',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		);

		$this->lesson_page_query = new WP_Query( $args );
	}

	/**
	 * Backup the globals that we will be modifying. Set them back with
	 * `reset_global_vars`.
	 */
	private function backup_global_vars() {
		global $wp_query, $post, $pages;

		$this->global_post_ref     = $post;
		$this->global_wp_query_ref = $wp_query;
		$this->global_pages_ref    = $pages;

	}

	/**
	 * Set global variables to the currently requested lesson. This is used
	 * internally and should not be called from external code.
	 *
	 * @access private
	 */
	public function set_global_vars() {
		global $wp_query, $post, $pages;

		$post           = get_post( $this->lesson_id );
		$pages          = array( $post->post_content );
		$wp_query       = $this->lesson_page_query;
		$wp_query->post = get_post( $this->lesson_id );
	}

	/**
	 * Reset global variables to what they were before calling
	 * `backup_global_vars`.
	 */
	private function reset_global_vars() {
		global $wp_query, $post, $pages;

		$wp_query       = $this->global_wp_query_ref;
		$post           = $this->global_post_ref;
		$pages          = $this->global_pages_ref;
	}
}

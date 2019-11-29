<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Renders a single Sensei post of any type based on the given ID. The rendered
 * result is meant to be displayed on the frontend, and may be used by
 * shortcodes or other rendering code.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Renderer_Single_Post implements Sensei_Renderer_Interface {

	/**
	 * @var int $post_id The ID of the post to render.
	 */
	private $post_id;

	/**
	 * @var string $template The filename of the template to render.
	 */
	private $template;

	/**
	 * @var bool $show_pagination Whether or not to render pagination links.
	 */
	private $show_pagination;

	/**
	 * @var WP_Query $post_query The query for the post.
	 */
	protected $post_query;

	/**
	 * @var WP_Post $global_post_ref Backup of the global $post variable.
	 */
	protected $global_post_ref;

	/**
	 * @var WP_Query $global_wp_query_ref Backup of the global $wp_query variable.
	 */
	protected $global_wp_query_ref;

	/**
	 * @var WP_Query $global_wp_the_query_ref Backup of the global $wp_the_query variable.
	 */
	protected $global_wp_the_query_ref;

	/**
	 * @var array $global_pages_ref Backup of the global $pages variable.
	 */
	protected $global_pages_ref;

	/**
	 * Setup the renderer object
	 *
	 * @since 1.12.0
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $template The template to use for rendering the post.
	 * @param array  $options  {
	 *   @type bool show_pagination Whether to show Sensei's pagination in the rendered output.
	 * }
	 */
	public function __construct( $post_id, $template, $options = array() ) {
		$this->post_id         = $post_id;
		$this->template        = $template;
		$this->show_pagination = isset( $options['show_pagination'] ) ? $options['show_pagination'] : false;
		$this->setup_post_query();
	}

	/**
	 * Render and return the content. This will use the given template, and
	 * will use an overridden version if it exists.
	 *
	 * @return string The rendered output.
	 */
	public function render() {
		$this->backup_global_vars();
		$this->set_global_vars();

		// Remove the header and footer.
		add_filter( 'sensei_show_main_footer', '__return_false' );
		add_filter( 'sensei_show_main_header', '__return_false' );

		// We'll make the assumption that the theme will display the title.
		add_filter( 'the_title', array( $this, 'hide_the_title' ), 100, 2 );

		// Capture output.
		ob_start();

		// Even though the header is not being displayed, the action hooked to it still needs to fire. Remove default wrapper.
		remove_action( 'sensei_before_main_content', array( Sensei()->frontend, 'sensei_output_content_wrapper' ) );
		do_action( 'sensei_before_main_content' );

		// Render the template.
		Sensei_Templates::get_template( $this->template );

		// Reset all filters.
		remove_filter( 'sensei_show_main_footer', '__return_false' );
		remove_filter( 'sensei_show_main_header', '__return_false' );
		remove_filter( 'the_title', array( $this, 'hide_the_title' ), 100 );

		// Render pagination if needed.
		if ( $this->show_pagination ) {
			do_action( 'sensei_pagination' );
		}
		$output = ob_get_clean();

		$this->reset_global_vars();

		return $output;
	}

	/**
	 * Create the posts query.
	 */
	private function setup_post_query() {
		if ( empty( $this->post_id ) ) {
			return;
		}

		$args = array(
			'p'         => $this->post_id,
			'post_type' => get_post_type( $this->post_id ),
		);

		$this->post_query = new WP_Query( $args );
	}

	/**
	 * Backup the globals that we will be modifying. Set them back with
	 * `reset_global_vars`.
	 */
	private function backup_global_vars() {
		global $wp_query, $wp_the_query, $post, $pages;

		$this->global_post_ref         = $post;
		$this->global_wp_query_ref     = $wp_query;
		$this->global_wp_the_query_ref = $wp_the_query;
		$this->global_pages_ref        = $pages;
	}

	/**
	 * Set global variables to the currently requested post. This is used
	 * internally and should not be called from external code.
	 *
	 * @access private
	 */
	public function set_global_vars() {
		global $wp_query, $wp_the_query, $post, $pages;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Used to render single post.
		$post         = get_post( $this->post_id );
		$pages        = array( $post->post_content );
		$wp_query     = $this->post_query;
		$wp_the_query = $wp_query;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Reset global variables to what they were before calling
	 * `backup_global_vars`.
	 */
	private function reset_global_vars() {
		global $wp_query, $wp_the_query, $post, $pages;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring preexisting state.
		$wp_query     = $this->global_wp_query_ref;
		$wp_the_query = $this->global_wp_the_query_ref;
		$post         = $this->global_post_ref;
		$pages        = $this->global_pages_ref;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Hide the title of this post on the page.
	 *
	 * @access private
	 *
	 * @param string $title   The incoming title.
	 * @param int    $post_id The incoming post ID.
	 *
	 * @return string Empty string if $post_id matches our post, $title otherwise.
	 */
	public function hide_the_title( $title, $post_id ) {
		if ( $this->post_id === $post_id ) {
			return '';
		}
		return $title;
	}
}

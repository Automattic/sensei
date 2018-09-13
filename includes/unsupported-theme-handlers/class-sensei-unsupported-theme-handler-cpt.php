<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sensei Unsupported Theme Handler for CPT's.
 *
 * Handles rendering the CPT page for themes that do not declare support for
 * Sensei.
 *
 * @author Automattic
 *
 * @since 1.12.0
 */
class Sensei_Unsupported_Theme_Handler_CPT implements Sensei_Unsupported_Theme_Handler_Interface {

	/**
	 * @var int The post ID.
	 */
	protected $post_id;

	/**
	 * @var string The post type to render.
	 */
	protected $post_type;

	/**
	 * @var array Additional options.
	 */
	protected $options;

	/**
	 * Construct the handler.
	 *
	 * @param string $post_type The post type to render.
	 * @param array  $options   An array of options. Currently supports:
	 *                            bool   show_pagination
	 *                            string template_filename
	 */
	public function __construct( $post_type, $options = array() ) {
		$this->post_type = $post_type;
		$this->options   = $options;
	}

	/**
	 * We can handle this request if it is for a page of the given type.
	 *
	 * @return bool
	 */
	public function can_handle_request() {
		return is_single() && get_post_type() === $this->post_type;
	}

	/**
	 * Set up handling for a single CPT page.
	 *
	 * @since 1.12.0
	 */
	public function handle_request() {
		$this->post_id = get_the_ID();

		add_filter( 'the_content', array( $this, 'cpt_page_content_filter' ) );

		// Handle some type-specific items.
		if ( 'lesson' === $this->post_type ) {
			// Use theme comments UI instead of Sensei's
			remove_action( 'sensei_pagination', array( 'Sensei_Lesson', 'output_comments' ), 90 );

			// Turn off theme comments if needed.
			if ( ! Sensei()->settings->get( 'lesson_comments' ) ) {
				add_filter( 'comments_open', '__return_false', 100 );
				add_filter( 'get_comments_number', '__return_false', 100 );
			}
		}

		if ( 'sensei_message' === $this->post_type ) {
			// Do not display the Message title.
			add_filter( 'the_title', array( $this, 'hide_the_title' ), 20, 2 );
		}
	}

	/**
	 * Filter the content and insert Sensei CPT content.
	 *
	 * @since 1.12.0
	 *
	 * @param string $content The raw post content.
	 *
	 * @return string The content to be displayed on the page.
	 */
	public function cpt_page_content_filter( $content ) {
		if ( ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'cpt_page_content_filter' ) );

		$renderer = $this->get_renderer();
		$content  = $renderer->render();

		return $content;
	}

	protected function get_option( $name, $default = null ) {
		return isset( $this->options[ $name ] ) ? $this->options[ $name ] : $default;
	}

	/**
	 * Get the renderer for this handler. Subclasses may override this to
	 * provide a custom object that implements Sensei_Renderer_Interface.
	 *
	 * @return Sensei_Renderer_Interface The renderer object to use.
	 */
	protected function get_renderer() {
		$show_pagination = $this->get_option( 'show_pagination', true );

		/**
		 * Whether to show pagination on the CPT page when displaying on a
		 * theme that does not explicitly support Sensei.
		 *
		 * @param  bool   $show_pagination The initial value.
		 * @param  string $post_type       The post type.
		 * @param  int    $post_id         The post ID.
		 * @return bool
		 */
		$show_pagination = apply_filters(
			'sensei_cpt_page_show_pagination',
			$show_pagination,
			$this->post_type,
			$this->post_id
		);

		return new Sensei_Renderer_Single_Post( $this->post_id, $this->get_template_filename(), array(
			'show_pagination' => $show_pagination,
		) );
	}

	/**
	 * Get the name of the template file. By default, this is
	 * "single-{post_type}.php", but could be supplied by an option in the
	 * constructor.
	 *
	 * @return string
	 */
	protected function get_template_filename() {
		return $this->get_option( 'template_filename', "single-{$this->post_type}.php" );
	}

	/**
	 * Use in the_title filter to hide the post title.
	 *
	 * @param string $title The original title.
	 * @param int    $id    The post ID.
	 *
	 * @return string The original title or empty string.
	 */
	public function hide_the_title( $title, $id ) {
		if ( is_main_query() && in_the_loop() && $id === $this->post_id ) {
			return '';
		}
		return $title;
	}

}

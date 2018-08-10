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
	 * @var string The post type to render.
	 */
	protected $post_type;

	/**
	 * Construct the handler.
	 *
	 * @param string $post_type The post type to render.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
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
		add_filter( 'the_content', array( $this, 'cpt_page_content_filter' ) );
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
		if ( ! is_main_query() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'cpt_page_content_filter' ) );

		$renderer = $this->get_renderer();
		$content  = $renderer->render();

		return $content;
	}

	/**
	 * Get the renderer for this handler. Subclasses may override this to
	 * provide a custom object that implements Sensei_Renderer_Interface.
	 *
	 * @return Sensei_Renderer_Interface The renderer object to use.
	 */
	protected function get_renderer() {
		$post_id = get_the_ID();

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
			true,
			$this->post_type,
			$post_id
		);

		return new Sensei_Renderer_Single_Post( $post_id, $this->get_template_filename(), array(
			'show_pagination' => $show_pagination,
		) );
	}

	protected function get_template_filename() {
		return "single-{$this->post_type}.php";
	}

}

<?php
/**
 * File containing the class Sensei_Editor_Wizard.
 *
 * @package sensei-lms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles editor wizards.
 *
 * @since 4.5.0
 */
class Sensei_Editor_Wizard {
	/**
	 * Instance of class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Sensei_Editor_Wizard constructor. Prevents other instances from being created outside of `self::instance()`.
	 */
	private function __construct() {
	}

	/**
	 * Fetches an instance of the class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_post_metas' ] );
		// Priority 9 to make sure it will run before the block editor nux on WPCOM. While this code is written, it uses priority 100.
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_wizard_assets' ], 9 );
	}

	/**
	 * Register post metas.
	 *
	 * @access private
	 */
	public function register_post_metas() {
		// A meta used to identify lessons created dynamically as new.
		register_post_meta(
			'lesson',
			'_new_post',
			[
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'boolean',
				'auth_callback' => function( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			]
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook_suffix The current admin page.
	 *
	 * @access private
	 *
	 * @deprecated 4.22.0 use Sensei_Editor_Wizard::enqueue_editor_wizard_assets instead.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __METHOD__, '4.22.0', 'Sensei_Editor_Wizard::enqueue_editor_wizard_assets' );

		$this->enqueue_editor_wizard_assets();
	}

	/**
	 * Enqueue editor wizard assets.
	 */
	public function enqueue_editor_wizard_assets() {
		global $pagenow;

		$post_type   = get_post_type();
		$post_id     = get_the_ID();
		$new_post    = get_post_meta( $post_id, '_new_post', true );
		$post_types  = [ 'course', 'lesson' ];
		$is_new_post = 'post-new.php' === $pagenow || $new_post;

		if ( $is_new_post && in_array( $post_type, $post_types, true ) ) {
			Sensei()->assets->enqueue( 'sensei-editor-wizard-script', 'admin/editor-wizard/index.js' );
			Sensei()->assets->enqueue( 'sensei-editor-wizard-style', 'admin/editor-wizard/style.css' );

			// Preload extensions (needed to identify if Sensei Pro is installed, and extension details).
			Sensei()->assets->preload_data( [ '/sensei-internal/v1/sensei-extensions?type=plugin' ] );
		}
	}
}

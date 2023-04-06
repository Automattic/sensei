<?php
/**
 * File containing the Post_Type class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Type
 *
 * @internal
 *
 * @since 4.12.0
 */
class Email_Post_Type {
	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Email_Post_Type constructor.
	 */
	private function __construct() {}


	/**
	 * Post type name.
	 */
	public const POST_TYPE = 'sensei_email';

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'load-edit.php', [ $this, 'maybe_redirect_to_listing' ] );
		add_action( 'map_meta_cap', [ $this, 'remove_cap_of_deleting_email' ], 10, 4 );
	}

	/**
	 * Remove the capability of deleting emails.
	 *
	 * @param string[] $caps    The user's actual capabilities.
	 * @param string   $cap     Capability name.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds the context to the cap. Typically the object ID.
	 *
	 * @since 4.12.0
	 *
	 * @access private
	 *
	 * @internal
	 *
	 * @return string[]
	 */
	public function remove_cap_of_deleting_email( $caps, $cap, $user_id, $args ) {
		if ( 'delete_post' === $cap && isset( $args[0] ) && self::POST_TYPE === get_post_type( $args[0] ) ) {
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}

	/**
	 * Register the Email post type.
	 *
	 * @internal
	 */
	public function register_post_type(): void {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			[
				'labels'       => [
					'name'               => __( 'Emails', 'sensei-lms' ),
					'singular_name'      => __( 'Email', 'sensei-lms' ),
					'add_new'            => __( 'Add New', 'sensei-lms' ),
					'add_new_item'       => __( 'Add New Email', 'sensei-lms' ),
					'edit_item'          => __( 'Edit Email', 'sensei-lms' ),
					'new_item'           => __( 'New Email', 'sensei-lms' ),
					'view_item'          => __( 'View Email', 'sensei-lms' ),
					'search_items'       => __( 'Search Emails', 'sensei-lms' ),
					'not_found'          => __( 'No emails found.', 'sensei-lms' ),
					'not_found_in_trash' => __( 'No emails found in trash.', 'sensei-lms' ),
					'parent_item_colon'  => __( 'Parent Email:', 'sensei-lms' ),
					'menu_name'          => __( 'Emails', 'sensei-lms' ),
					'name_admin_bar'     => __( 'Email', 'sensei-lms' ),
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'show_in_rest' => true, // Enables the Gutenberg editor.
				'hierarchical' => false,
				'rewrite'      => false,
				'supports'     => [ 'title', 'editor', 'author', 'revisions' ],
			]
		);
	}

	/**
	 * Redirect to the Email settings page if the user is trying to access the Email post type listing.
	 *
	 * @internal
	 * @access private
	 */
	public function maybe_redirect_to_listing(): void {
		if ( ! isset( $_GET['post_type'] ) || self::POST_TYPE !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( isset( $_GET['action'] ) && '-1' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=sensei-settings&tab=email-notification-settings' ), 301 );
		exit;
	}
}


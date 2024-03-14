<?php
/**
 * File containing the Email_List_Table_Actions class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class responsible for handling the email page template.
 *
 * @internal
 *
 * @since 4.12.0
 */
class Email_Page_Template {


	public const THEME         = 'sensei-email';
	public const SLUG          = 'single-sensei_email';
	public const ID            = self::THEME . '//' . self::SLUG;
	public const TEMPLATE_PATH = 'block-templates/email-template.php';

	/**
	 * The Email_Page_Template_Repository instance.
	 *
	 * @var Email_Page_Template_Repository
	 */
	private $repository;



	/**
	 * The constructor.
	 *
	 * @internal
	 *
	 * @param Email_Page_Template_Repository $repository The Email_Repository instance.
	 */
	public function __construct( Email_Page_Template_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Initialize the class and add hooks.
	 *
	 * @internal
	 */
	public function init(): void {
		add_filter( 'pre_get_block_file_template', [ $this, 'get_from_file' ], 10, 3 );
		add_filter( 'get_block_templates', [ $this, 'add_email_template' ], 10, 3 );
		add_filter( 'get_block_template', [ $this, 'get_template' ], 10, 3 );
	}

	/**
	 * Return sensei email's templates from file or db.
	 *
	 * @internal
	 *
	 * @param WP_Block_Template $template Original template.
	 * @param string            $id          The template id.
	 * @param string            $template_type     The template type "wp_template" or "wp_template_part".
	 *
	 * @return WP_Block_Template The original or the email template;
	 */
	public function get_template( $template, $id, $template_type ) {
		if ( 'wp_template' !== $template_type || ! $id || 0 !== strpos( $id, self::THEME ) ) {
			return $template;
		}

		$from_db = $this->repository->get( self::ID );

		if ( ! empty( $from_db ) ) {
			return $from_db;
		} else {
			return $this->repository->get_from_file( self::TEMPLATE_PATH, self::ID );
		}
	}

	/**
	 * Return sensei email's template from file when there is no template on the database. It is used specially by the pre_get_block_file_template hook
	 *
	 * @internal
	 *
	 * @param WP_Block_Template $template Original template.
	 * @param string            $id          The template id.
	 * @param string            $template_type     The template type "wp_template" or "wp_template_part".
	 *
	 * @return WP_Block_Template The original or the email template;
	 */
	public function get_from_file( $template, $id, $template_type ) {
		if ( 'wp_template' !== $template_type || ! $id || 0 !== strpos( $id, self::THEME ) ) {
			return $template;
		}

		return $this->repository->get_from_file( self::TEMPLATE_PATH, $id );
	}

	/**
	 * Add Sensei email template.
	 *
	 * @internal
	 *
	 * @param WP_Block_Template $query_result   Array of found block templates.
	 * @param array             $query          Arguments to retrieve templates.
	 * @param string            $template_type  wp_template or wp_template_part.
	 *
	 * @return WP_Block_Template The original or the email template.
	 */
	public function add_email_template( $query_result, $query, $template_type ) {
		if ( ! \Sensei_Course_Theme_Editor::is_site_editor_request() ) {
			return $query_result;
		}

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Returning early if it's Gutenberg's template lookup ajax request,
		// otherwise it shows the template in editor as default template.
		if ( $uri && strpos( $uri, '/wp-json/wp/v2/templates/lookup?slug' ) !== false ) {
			return $query_result;
		}

		if ( 'wp_template' !== $template_type || ! empty( $query['theme'] ) ) {
			return $query_result;
		}

		$post_type = $query['post_type'] ?? get_post_type();

		if ( ! empty( $post_type ) && Email_Post_Type::POST_TYPE !== $post_type ) {
			return $query_result;
		}

		$from_db = $this->repository->get( self::ID );

		if ( ! empty( $from_db ) ) {
			$query_result[] = $from_db;
		} else {  // Use the PHP email template.
			$query_result[] = $this->repository->get_from_file( self::TEMPLATE_PATH, self::ID );
		}

		return $query_result;
	}
}

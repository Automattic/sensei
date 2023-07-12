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
	 * Return sensei email's template from file when there is no template on the database.
	 *
	 * @internal
	 *
	 * @param WP_Block_Template $original_templates Original template.
	 * @param string            $query          Original query to load the template.
	 * @param string            $template_type     The template type "wp_template" or "wp_template_part".
	 *
	 * @return WP_Block_Template The original or the email template.
	 */
	public function add_email_template( $original_templates, $query, $template_type ) {
		if ( 'wp_template' !== $template_type || ! empty( $query['theme'] ) ) {
			return $original_templates;
		}

		$post_type = $query['post_type'] ?? get_post_type();

		if ( ! empty( $post_type ) && Email_Post_Type::POST_TYPE !== $post_type ) {
			return $original_templates;
		}

		$from_db = $this->repository->get( self::ID );

		if ( ! empty( $from_db ) ) {
			$original_templates[] = $from_db;
		} else {
			$original_templates[] = $this->repository->get_from_file( self::TEMPLATE_PATH, self::ID );
		}

		return $original_templates;
	}
}

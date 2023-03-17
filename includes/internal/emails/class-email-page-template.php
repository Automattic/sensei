<?php

/**
 * File containing the Email_List_Table_Actions class.
 *
 * @package sensei
 */

namespace Sensei\Internal\Emails;

if (!defined('ABSPATH')) {
	exit;
}




/**
 * The class responsible for handling the email page template.
 *
 * @internal
 *
 * @since $$next-version$$
 */
class Email_Page_Template
{

	public const THEME = 'sensei-email';
	public const SLUG = 'single-sensei_email';
	public const ID = self::THEME . '//' . self::SLUG;
	public const TEMPLATE_PATH = 'block-templates/email-template.php';

	/**
	 * The Email_Page_Template_Repository instance.
	 *
	 * @var Email_Page_Template_Repository
	 */
	private $repository;

	public function __construct(Email_Page_Template_Repository $repository)
	{

		$this->repository = $repository;
	}

	public function init(): void {
		add_filter('pre_get_block_file_template', [$this, 'get_from_file'], 10, 3);
		add_filter('get_block_templates',  [$this, 'add_email_template'], 10, 3);

		add_filter('get_block_template',  [$this, 'get_template'], 10, 3);


		add_filter( 'single_template',  [$this, 'my_filter'], 10, 3 );

	}

	public function my_filter($template, $type, $templates){

		return $templates;
	}

	public function get_template( $template, $id, $template_type ) {
		if ('wp_template' !== $template_type || !$id || 0 !== strpos($id, self::THEME)) {
			return $template;
		}
		if( $from_db = $this->repository->get(self::ID) ) {
			return $from_db;
		} else {
			return $this->repository->get_from_file(self::TEMPLATE_PATH, self::ID );
		}
	}


	public function get_from_file($template, $id, $template_type)
	{
		if ('wp_template' !== $template_type || !$id || 0 !== strpos($id, self::THEME)) {
			return $template;
		}

		return $this->repository->get_from_file(self::TEMPLATE_PATH, $id);
	}

	public function add_email_template($original_templates, $query, $template_type)
	{
		if ($template_type != 'wp_template' || ! empty($query['theme']) ) {
			return $original_templates;
		}

		if( $from_db = $this->repository->get(self::ID) ) {
			$original_templates[] = $from_db;
		} else {
			$original_templates[] = $this->repository->get_from_file(self::TEMPLATE_PATH, self::ID );
		}

		return $original_templates;
	}
}

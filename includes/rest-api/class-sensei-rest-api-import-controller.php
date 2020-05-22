<?php
/**
 * Import REST API Controller.
 *
 * @package Sensei\DataPort
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Sensei Import REST API endpoints.
 *
 * @package Sensei
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_REST_API_Import_Controller extends Sensei_REST_API_Data_Port_Controller {
	/**
	 * Routes prefix.
	 *
	 * @var string
	 */
	protected $rest_base = 'import';

	/**
	 * Get the handler class job this REST API controller handles.
	 *
	 * @return string
	 */
	protected function get_handler_class() {
		return Sensei_Import_Job::class;
	}

	/**
	 * Create a data port job for the current user.
	 *
	 * @return Sensei_Data_Port_Job
	 */
	protected function create_job()
	{
		return Sensei_Data_Port_Manager::instance()->create_import_job( get_current_user_id() );
	}
}

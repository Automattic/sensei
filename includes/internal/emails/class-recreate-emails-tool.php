<?php
/**
 * File containing Recreate_Emails_Tool class.
 *
 * @package sensei-lms
 * @since 3.7.0
 */

namespace Sensei\Internal\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Recreate_Emails_Tool class.
 *
 * @since $$next-version$$
 */
class Recreate_Emails_Tool implements \Sensei_Tool_Interface {

	/**
	 * Template_Wizard instance.
	 *
	 * @var Template_Wizard
	 */
	private $template_wizard;


	/**
	 * Recreate_Emails_Tool constructor.
	 *
	 * @param Template_Wizard $template_wizard Template_Wizard instance.
	 */
	public function __construct( Template_Wizard $template_wizard ) {
		$this->template_wizard = $template_wizard;
	}

	/**
	 * Initialize the tool.
	 */
	public function init() {
		add_filter( 'sensei_tools', [ $this, 'register_tool' ] );
	}

	/**
	 * Register the tool.
	 *
	 * @param array $tools List of tools.
	 *
	 * @return array
	 */
	public function register_tool( $tools ) {
		$tools[] = $this;
		return $tools;
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'recreate-emails';
	}

	/**
	 * Get the name of the tool.
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Re-create Emails', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __(
			'Forcefully recreate all emails. If you have any changes in default templates those change will be lost.',
			'sensei-lms'
		);
	}

	/**
	 * Run the tool.
	 */
	public function process() {
		$this->template_wizard->init();
		$result = $this->template_wizard->create_all( true );

		$message = $result
			? __( 'Emails were recreated successfully.', 'sensei-lms' )
			: __( 'There were errors while recreating emails.', 'sensei-lms' );
		\Sensei_Tools::instance()->add_user_message( $message, ! $result );
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return bool True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}

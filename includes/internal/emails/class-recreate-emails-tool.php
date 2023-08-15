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
 * @since 4.12.0
 */
class Recreate_Emails_Tool implements \Sensei_Tool_Interface {

	/**
	 * Template_Wizard instance.
	 *
	 * @var Email_Seeder
	 */
	private $seeder;

	/**
	 * Sensei_Tools instance.
	 *
	 * @var \Sensei_Tools
	 */
	private $tools;

	/**
	 * Recreate_Emails_Tool constructor.
	 *
	 * @param Email_Seeder  $seeder Email_Seeder instance.
	 * @param \Sensei_Tools $tools Sensei_Tools instance.
	 */
	public function __construct( Email_Seeder $seeder, \Sensei_Tools $tools ) {
		$this->seeder = $seeder;
		$this->tools  = $tools;
	}

	/**
	 * Initialize the tool.
	 */
	public function init(): void {
		add_filter( 'sensei_tools', [ $this, 'register_tool' ] );
	}

	/**
	 * Register the tool.
	 *
	 * @param array $tools List of tools.
	 *
	 * @return (mixed|static)[]
	 *
	 * @psalm-return array<mixed|static>
	 */
	public function register_tool( $tools ): array {
		$tools[] = $this;
		return $tools;
	}

	/**
	 * Get the ID of the tool.
	 *
	 * @return string
	 *
	 * @psalm-return 'recreate-emails'
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
		return __( 'Recreate Emails', 'sensei-lms' );
	}

	/**
	 * Get the description of the tool.
	 *
	 * @return string
	 */
	public function get_description() {
		return __(
			'Recreate all emails. Existing customizations will be lost.',
			'sensei-lms'
		);
	}

	/**
	 * Run the tool.
	 *
	 * @return void
	 */
	public function process() {
		$this->seeder->init();
		$result = $this->seeder->create_all( true );

		$message = $result
			? __( 'Emails were recreated successfully.', 'sensei-lms' )
			: __( 'There were errors while recreating emails.', 'sensei-lms' );
		$this->tools->add_user_message( $message, ! $result );
	}

	/**
	 * Is the tool currently available?
	 *
	 * @return true True if tool is available.
	 */
	public function is_available() {
		return true;
	}
}

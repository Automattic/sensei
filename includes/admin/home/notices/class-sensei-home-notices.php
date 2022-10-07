<?php
/**
 * File containing Sensei_Home_Notices class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class that gathers and produces the local notices for Sensei Home.
 */
class Sensei_Home_Notices {
	/**
	 * The remote data helper.
	 *
	 * @var Sensei_Home_Remote_Data_API $remote_data_api
	 */
	private $remote_data_api;

	/**
	 * Sensei_Home_Notices constructor.
	 *
	 * @param Sensei_Home_Remote_Data_API $remote_data_api The remote data helper.
	 */
	public function __construct( Sensei_Home_Remote_Data_API $remote_data_api ) {
		$this->remote_data_api = $remote_data_api;
	}

	/**
	 * Add the hooks related to this class.
	 */
	public function init() {
		add_action( 'sensei_admin_notices', [ $this, 'add_update_notices' ] );
	}

	/**
	 * Add the update notices.
	 *
	 * @param array $notices The notices to add the update notices to.
	 *
	 * @return array
	 */
	private function add_update_notices( $notices ) {
		$notices = [];

		$data = $this->remote_data_api->fetch();

		if ( $data instanceof \WP_Error || empty( $data['versions'] ) ) {
			return $notices;
		}

		return $notices;
	}

}

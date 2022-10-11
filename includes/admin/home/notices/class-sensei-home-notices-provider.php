<?php
/**
 * File containing Sensei_Home_Notices_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class that gathers and produces the local notices for Sensei Home.
 */
class Sensei_Home_Notices_Provider {
	/**
	 * The admin notices helper.
	 *
	 * @var Sensei_Admin_Notices
	 */
	private $admin_notices;

	/**
	 * Sensei_Home_News_Provider constructor.
	 *
	 * @param Sensei_Admin_Notices $admin_notices   The remote admin notices helper.
	 */
	public function __construct( Sensei_Admin_Notices $admin_notices = null ) {
		$this->admin_notices = $admin_notices;
	}

	/**
	 * Fallback for when we're in an environment that doesn't have `Sensei_Admin_Notices`.
	 *
	 * @return array
	 */
	private function local_only() : array {
		/**
		 * This filter is documented in `class-sensei-admin-notices.php`.
		 */
		$notices = apply_filters( 'sensei_admin_notices', [] );

		return array_filter(
			$notices,
			function( $notice_key ) {
				// We only care about home notices for now.
				return strpos( $notice_key, Sensei_Home_Notices::HOME_NOTICE_KEY_PREFIX ) === 0;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Returns all the information for the notices section.
	 *
	 * @param int $max_age The maximum age of the remote data.
	 *
	 * @return array
	 */
	public function get( $max_age = null ): array {
		return isset( $this->admin_notices ) ? $this->admin_notices->get_notices_to_display( Sensei_Home::SCREEN_ID ) : $this->local_only();
	}

}

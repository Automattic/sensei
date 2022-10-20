<?php
/**
 * File containing Sensei_Home_Notices_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class that formats the notices for the Sensei Home endpoint.
 */
class Sensei_Home_Notices_Provider {
	/**
	 * The admin notices helper.
	 *
	 * @var Sensei_Admin_Notices
	 */
	private $admin_notices;
	/**
	 * Screen ID to show notices on.
	 *
	 * @var string
	 */
	private $screen_id;

	/**
	 * Sensei_Home_News_Provider constructor.
	 *
	 * @param Sensei_Admin_Notices $admin_notices   The remote admin notices helper.
	 * @param string               $screen_id       The screen ID to show notices on.
	 */
	public function __construct( $admin_notices = null, $screen_id = null ) {
		$this->admin_notices = $admin_notices;
		$this->screen_id     = $screen_id;
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
	 * @return array
	 */
	public function get(): array {
		$notices = isset( $this->admin_notices ) ? $this->admin_notices->get_notices_to_display( $this->screen_id ) : $this->local_only();

		return array_map( [ $this, 'format_item' ], $notices );
	}

	/**
	 * Format a notice item.
	 *
	 * @param array $notice The unformatted notice.
	 * @return array
	 */
	private function format_item( $notice ) {
		return [
			'level'       => $notice['level'] ?? 'info',
			'heading'     => $notice['heading'] ?? null,
			'message'     => $notice['message'],
			'info_link'   => $notice['info_link'] ?? null,
			'actions'     => $notice['actions'] ?? [],
			'dismissible' => $notice['dismissible'] ?? false,
		];
	}
}

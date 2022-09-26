<?php
/**
 * File containing the Sensei_Home_Quick_Links_Item class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Quick_Links_Item class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Quick_Links_Item {

	/**
	 * The quick link title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The quick link url.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Constructor for Sensei_Home_Quick_Links_Item.
	 *
	 * @param string $title The localised title.
	 * @param string $url The destination url.
	 */
	public function __construct( string $title, string $url ) {
		$this->title = $title;
		$this->url   = $url;
	}

	/**
	 * Get quick link title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get quick link url. Special actions are returned as a url under the `sensei://` protocol.
	 * Check `Sensei_Home_Quick_Links_Provider::ACTION_*` constants for available special actions.
	 *
	 * @return string
	 */
	public function get_url(): string {
		return $this->url;
	}

}

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
	public $title;

	/**
	 * The quick link url.
	 *
	 * @var string
	 */
	public $url;

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

}

<?php
/**
 * File containing the Sensei_Home_Help_Item class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Help_Item class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Help_Item {

	/**
	 * The help item title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The help item url. Can be null.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * An optional link with extra information.
	 *
	 * @var Sensei_Home_Help_Extra_Link|null
	 */
	private $extra_link;

	/**
	 * An optional icon.
	 *
	 * @var string|null
	 */
	private $icon;

	/**
	 * Constructor for Sensei_Home_Help_Item.
	 *
	 * @param string                           $title      The localised title.
	 * @param string|null                      $url        The destination url. Can be set as null if the item is disabled.
	 * @param string|null                      $icon       The icon. But default null and will use the default icon (if any).
	 * @param Sensei_Home_Help_Extra_Link|null $extra_link An optional link with extra information.
	 */
	public function __construct( string $title, $url, string $icon = null, Sensei_Home_Help_Extra_Link $extra_link = null ) {
		$this->title      = $title;
		$this->url        = $url;
		$this->icon       = $icon;
		$this->extra_link = $extra_link;
	}

	/**
	 * Get help item title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get help item url.
	 *
	 * @return string|null
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Get the optional link with extra information.
	 *
	 * @return Sensei_Home_Help_Extra_Link|null
	 */
	public function get_extra_link(): ?Sensei_Home_Help_Extra_Link {
		return $this->extra_link;
	}

	/**
	 * Get the icon.
	 *
	 * @return string|null
	 */
	public function get_icon(): ?string {
		return $this->icon;
	}

}

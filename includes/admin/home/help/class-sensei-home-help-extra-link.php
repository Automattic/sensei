<?php
/**
 * File containing the Sensei_Home_Help_Extra_Link class.
 *
 * @package sensei-lms
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Help_Extra_Link class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Help_Extra_Link {

	/**
	 * The extra link label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * The extra link url.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Constructor for Sensei_Home_Help_Extra_Link.
	 *
	 * @param string $label The localised label.
	 * @param string $url The destination url.
	 */
	public function __construct( string $label, string $url ) {
		$this->label = $label;
		$this->url   = $url;
	}

	/**
	 * Get extra link label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get extra link url.
	 *
	 * @return string
	 */
	public function get_url(): string {
		return $this->url;
	}

}

<?php
/**
 * File containing the Sensei_Home_Quick_Links_Category class.
 *
 * @package sensei
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Quick_Links_Category class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Quick_Links_Category {

	/**
	 * The title for the category.
	 *
	 * @var string $title
	 */
	private $title;

	/**
	 * The category items.
	 *
	 * @var Sensei_Home_Quick_Links_Item[] $items
	 */
	private $items;

	/**
	 * The constructor.
	 *
	 * @param string                         $title The localised title for the category.
	 * @param Sensei_Home_Quick_Links_Item[] $items The items in the category.
	 */
	public function __construct( string $title, array $items = [] ) {
		$this->title = $title;
		$this->items = $items;
	}

	/**
	 * Get category title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get quick links included in the category.
	 *
	 * @return Sensei_Home_Quick_Links_Item[]
	 */
	public function get_items(): array {
		return $this->items;
	}
}

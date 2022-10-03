<?php
/**
 * File containing the Sensei_Home_Help_Category class.
 *
 * @package sensei
 * @since $$next-version$$
 */

/**
 * Sensei_Home_Help_Category class.
 *
 * @since $$next-version$$
 */
class Sensei_Home_Help_Category {

	/**
	 * The title for the category.
	 *
	 * @var string $title
	 */
	private $title;

	/**
	 * The category items.
	 *
	 * @var Sensei_Home_Help_Item[] $items
	 */
	private $items;

	/**
	 * The constructor.
	 *
	 * @param string                  $title The localised title for the category.
	 * @param Sensei_Home_Help_Item[] $items The items in the category.
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
	 * Get help items included in the category.
	 *
	 * @return Sensei_Home_Help_Item[]
	 */
	public function get_items(): array {
		return $this->items;
	}
}

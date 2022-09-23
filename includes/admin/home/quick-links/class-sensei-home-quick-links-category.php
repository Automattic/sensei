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
	public $title;

	/**
	 * The category items.
	 *
	 * @var Sensei_Home_Quick_Links_Item[] $items
	 */
	public $items;

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
}

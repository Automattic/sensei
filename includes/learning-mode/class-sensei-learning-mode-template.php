<?php
/**
 * The class file for Sensei_Learning_Mode_Template.
 *
 * @author      Automattic
 * @package     Sensei
 * @version     $$next-version$$
 */

/**
 * Class representing a Learning Mode block template.
 *
 * @since $$next-version$$
 */
class Sensei_Learning_Mode_Template {

	/**
	 * The unique name of the block template.
	 *
	 * @var string
	 * @since $$next-version$$
	 */
	public $name;

	/**
	 * The title of the block template.
	 *
	 * @var string
	 * @since $$next-version$$
	 */
	public $title;

	/**
	 * The version number of the block template. For example "1.0.0".
	 *
	 * @var string
	 * @since $$next-version$$
	 */
	public $version;

	/**
	 * An array of urls of styles that needs to be enqueued with this block template.
	 *
	 * @var string[]
	 * @since $$next-version$$
	 */
	public $styles;
	/**
	 * An array of urls of scripts that needs to be enqueued with this block template.
	 *
	 * @var string[]
	 * @since $$next-version$$
	 */
	public $scripts;

	/**
	 * The screenshots of the block templates that are displayed in the settings for user to see.
	 *
	 * @var array {
	 *     @type string $full      The url to the full size screenshot of the block template.
	 *     @type string $thumbnail The url to the thumbnail size screenshot of the block template.
	 * }
	 * @since $$next-version$$
	 */
	public $screenshots;

	/**
	 * The paths to actual html content of the templates.
	 *
	 * @var array {
	 *     @type string $lesson The path to html content of the block template for lessons.
	 *     @type string $quiz   The path to html content of the block template for quizzes.
	 * }
	 */
	public $content;

	/**
	 * In case template is a placeholder for an upsell, then supply upsell data.
	 *
	 * @var array {
	 *     @type string $title The CTA title of the upsell.
	 *     @type string $tag   The tag for the template to indicate that it belongs to a group of templates. E.g.: "PREMIUM"
	 *     @type string $url   The url to the upsell web page.
	 * }
	 */
	public $upsell;

	/**
	 * Constructor.
	 *
	 * @param array $properties An array of properties for this class.
	 */
	public function __construct( array $properties = [] ) {
		foreach ( $properties as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}
}

/**
 * Class Sensei_Course_Theme_Template
 *
 * @ignore only for backward compatibility.
 * @since $$next-version$$
 * @deprecated $$next-version$$ Use \Sensei_Learning_Mode_Template.
 */
class Sensei_Course_Theme_Template extends Sensei_Learning_Mode_Template {
}

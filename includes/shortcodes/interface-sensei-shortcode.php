<?php
/**
 * Sensei Shortcodes Interface
 *
 * Should be implemented by classes wishing to add shortcode functionality to Sensei.
 *
 * @interface Sensei_Shortcode
 * @since 1.9.0
 * @package Sensei
 * @category Classes
 * @author 		WooThemes
 */
interface Sensei_Shortcode_Interface {

    /**
     * All constructors must implement and accept $attributes and $content as arguments
     *
     * @param array $attributes
     * @param string $content
     * @param string $shortcode
     * @return mixed
     */
    public function __construct($attributes, $content, $shortcode);

    /**
     * @return string generated output
     */
    public function render();

}// end interface
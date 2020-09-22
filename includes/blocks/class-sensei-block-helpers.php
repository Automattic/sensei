<?php
/**
 * File containing the Sensei_Course_Block_Helpers class.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sensei_Course_Block_Helpers
 */
class Sensei_Block_Helpers {


	/**
	 * Build CSS classes and inline styles from block attributes.
	 *
	 * @param array $block_attributes  The block attributes array with the format$attribute_key => $attribute_value.
	 *
	 * @return array {
	 *     An array of classes and styles.
	 *
	 *     @type string $css_classes   An array of classes.
	 *     @type string $inline_styles An array of styles.
	 * }
	 */
	public static function build_block_styles( $block_attributes ) {
		$inline_styles = [];
		$css_classes   = [];

		foreach ( $block_attributes as $attribute => $value ) {
			switch ( $attribute ) {
				case 'backgroundColor':
					$css_classes[] = 'has-background-color';
					$css_classes[] = sprintf( 'has-%s-background-color', $value );
					break;
				case 'customBackgroundColor':
					$css_classes[]   = 'has-background-color';
					$inline_styles[] = sprintf( 'background-color: %s', $value );
					break;
				case 'textColor':
					$css_classes[] = 'has-color';
					$css_classes[] = sprintf( 'has-%s-color', $value );
					break;
				case 'customTextColor':
					$css_classes[]   = 'has-color';
					$inline_styles[] = sprintf( 'color: %s', $value );
					break;
				case 'fontSize':
					$inline_styles[] = sprintf( 'font-size: %spx', $value );
					break;
				default:
					break;
			}
		}

		return [
			'css_classes'   => $css_classes,
			'inline_styles' => $inline_styles,
		];
	}

	/**
	 * Render class and style HTML attributes.
	 *
	 * @param string|string[] $class_names
	 * @param array           $css
	 *
	 * @return string
	 */
	public static function render_style_attributes( $class_names, $css ) {

		$class_names = array_merge( is_array( $class_names ) ? $class_names : [ $class_names ], $css['css_classes'] );
		return sprintf(
			'class="%s" style="%s"',
			esc_attr( implode( ' ', array_map( 'sanitize_html_class', $class_names ) ) ),
			esc_attr( implode( '; ', $css['inline_styles'] ) )
		);
	}


}

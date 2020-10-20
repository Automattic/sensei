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
	 * Build CSS classes (for named colors) and inline styles from block attributes.
	 *
	 * @param array $block_attributes  The block attributes.
	 * @param array $colors Color      attributes and their style property.
	 *
	 * @return array Colors CSS classes and inline styles.
	 */
	public static function build_styles( array $block_attributes, array $colors = [] ) : array {
		$attributes = [
			'css_classes'   => [],
			'inline_styles' => [],
		];

		$colors = array_merge(
			[
				'textColor'       => 'color',
				'borderColor'     => 'border-color',
				'backgroundColor' => 'background-color',
			],
			$colors
		);

		foreach ( $colors as $color => $style ) {

			if ( ! $style ) {
				continue;
			}
			$named_color  = $block_attributes[ $color ] ?? null;
			$custom_color = $block_attributes[ 'custom' . ucfirst( $color ) ] ?? null;

			if ( $custom_color || $named_color ) {
				$attributes['css_classes'][] = sprintf( 'has-%s', $style );
			}
			if ( $named_color ) {
				$named_class                 = 'border-color' === $style ? 'border-color-%s' : 'has-%s-%s';
				$attributes['css_classes'][] = sprintf( $named_class, $named_color, $style );
			} elseif ( $custom_color ) {
				$attributes['inline_styles'][] = sprintf( '%s: %s;', $style, $custom_color );
			}
		}

		if ( ! empty( $block_attributes['fontSize'] ) ) {
			$attributes['inline_styles'][] = sprintf( 'font-size: %spx', $block_attributes['fontSize'] );
		}

		return $attributes;
	}

	/**
	 * Render class and style HTML attributes.
	 *
	 * @param string|string[] $class_names An array of classes or a single class.
	 * @param array           $css         {
	 *     An array of classes and inline styles.
	 *
	 *     @type string[] $css_classes   An array of classes.
	 *     @type string[] $inline_styles An array of inline css.
	 * }
	 *
	 * @return string
	 */
	public static function render_style_attributes( $class_names, $css ) {

		$class_names = array_merge( is_array( $class_names ) ? $class_names : [ $class_names ], $css['css_classes'] );
		return sprintf(
			'class="%s" style="%s"',
			esc_attr( implode( ' ', $class_names ) ),
			esc_attr( implode( '; ', $css['inline_styles'] ) )
		);
	}

	/**
	 * Add default style to list of classes if no style is selected.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public static function block_class_with_default_style( $attributes ) {
		$class_name = $attributes['className'] ?? '';
		if ( empty( $class_name ) && false === strpos( $class_name, 'is-style-' ) ) {
			$class_name .= ' is-style-default';
		}

		return $class_name;
	}


}

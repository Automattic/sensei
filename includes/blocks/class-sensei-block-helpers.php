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
	 * @param array $block_attributes The block attributes.
	 * @param array $colors           An array with the color attribute as keys and the style property as values.
	 * @param array $size_styles      An array with the sizing attribute as keys and the style property as values.
	 *
	 * @return array Colors CSS classes and inline styles.
	 */
	public static function build_styles( array $block_attributes, array $colors = [], array $size_styles = [] ): array {
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

		$has_style_classes = [
			'color'            => 'has-text-color',
			'background-color' => 'has-background',
		];

		foreach ( $colors as $color => $style ) {

			if ( ! $style ) {
				continue;
			}
			$named_color   = $block_attributes[ $color ] ?? null;
			$custom_color  = $block_attributes[ 'custom' . ucfirst( $color ) ] ?? null;
			$default_color = $block_attributes[ 'default' . ucfirst( $color ) ] ?? null;

			if ( isset( $has_style_classes[ $style ] ) && ( $custom_color || $named_color || $default_color ) ) {
				$attributes['css_classes'][] = $has_style_classes[ $style ];
			}

			$named_class = 'border-color' === $style ? 'border-color-%s' : 'has-%s-%s';
			if ( $named_color ) {
				$attributes['css_classes'][] = sprintf( $named_class, $named_color, $style );
			} elseif ( $custom_color ) {
				$attributes['inline_styles'][] = sprintf( '%s: %s;', $style, $custom_color );
			} elseif ( $default_color ) {
				$attributes['css_classes'][] = sprintf( $named_class, $default_color, $style );
			}
		}

		foreach ( $size_styles as $attribute_name => $css_class ) {
			if ( isset( $block_attributes[ $attribute_name ] ) && is_int( $block_attributes[ $attribute_name ] ) ) {
				$attributes['inline_styles'][] = sprintf( '%s: %spx', $css_class, $block_attributes[ $attribute_name ] );
			}
		}

		return $attributes;
	}

	/**
	 * Render class and style HTML attributes.
	 *
	 * @param string|string[] $class_names   An array of classes or a single class.
	 * @param array           $css           {
	 *                                       An array of classes and inline styles.
	 *
	 * @type string[]         $css_classes   An array of classes.
	 * @type string[]         $inline_styles An array of inline css.
	 * }
	 *
	 * @return string
	 */
	public static function render_style_attributes( $class_names, $css ) {
		$css_classes   = isset( $css['css_classes'] ) && is_array( $css['css_classes'] ) ? $css['css_classes'] : [];
		$inline_styles = isset( $css['inline_styles'] ) && is_array( $css['inline_styles'] ) ? $css['inline_styles'] : [];

		$class_names = array_merge( is_array( $class_names ) ? $class_names : [ $class_names ], $css_classes );
		return sprintf(
			'class="%s" style="%s"',
			esc_attr( implode( ' ', $class_names ) ),
			esc_attr( implode( '; ', $inline_styles ) )
		);
	}

	/**
	 * Add default style to list of classes if no style is selected. If a parent classname is supplied, it will override
	 * the default style.
	 *
	 * @param array  $attributes         Block attributes.
	 * @param array  $parent_attributes  Parent block attributes.
	 * @param string $default_style_name Default style name.
	 *
	 * @return string
	 */
	public static function block_class_with_default_style( $attributes, $parent_attributes = [], $default_style_name = 'default' ) {
		$class_name = $attributes['className'] ?? '';
		if ( false === strpos( $class_name, 'is-style-' ) ) {
			$parent_class_name = $parent_attributes['className'] ?? '';

			if ( false === strpos( $parent_class_name, 'is-style-' ) ) {
				$class_name .= ' is-style-' . $default_style_name;
			} else {
				preg_match( '/is-style-\w+/', $parent_class_name, $matches );
				$class_name .= ' ' . $matches[0];
			}
		}

		return $class_name;
	}

	/**
	 * Generate CSS for the given variables. Skips variables with an empty value.
	 *
	 * @param array  $variables CSS variable key - value pairs.
	 * @param string $separator Separator string between variables.
	 *
	 * @return array CSS styles and classlist.
	 */
	public static function css_variables( $variables, $separator = "\n" ) {
		$style   = '';
		$classes = '';
		foreach ( $variables as $variable => $value ) {
			if ( ! isset( $value ) || '' === $value ) {
				continue;
			}
			$style   .= '--sensei-' . $variable . ': ' . $value . ';' . $separator;
			$classes .= ' has-sensei-' . $variable . ' ';
		}

		return [ $style, $classes ];
	}

	/**
	 * Add unit postfix to a value. Returns null if value is empty.
	 *
	 * @param string $value Value.
	 * @param string $unit  Unit.
	 *
	 * @return string|null Postfixed value or null if empty.
	 */
	public static function css_unit( $value, $unit = 'px' ) {
		if ( ! isset( $value ) || '' === $value ) {
			return null;
		}
		return '' . $value . $unit;
	}

}

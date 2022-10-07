<?php
/**
 * File containing Sensei_Course_Theme_Styles class.
 *
 * @package sensei-lms
 * @since   4.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add support for global styles colors.
 *
 * @since 4.7.0
 */
class Sensei_Course_Theme_Styles {

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'wp_head', [ self::class, 'output_global_styles_colors' ] );
		add_action( 'render_block', [ self::class, 'apply_block_support' ], 10, 2 );
	}

	/**
	 * Add block colors as inline style with CSS variables.
	 *
	 * @access private
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $block         Block object.
	 *
	 * @return string Filtered block content.
	 */
	public static function apply_block_support( $block_content, $block ) {
		if ( ! $block_content ) {
			return $block_content;
		}

		$colors = self::get_block_colors( $block['attrs'] );

		if ( empty( $colors ) ) {
			return $block_content;
		}

		$style = esc_attr( implode( ' ', $colors ) );
		return self::set_style_attribute( $block_content, $style );

	}

	/**
	 * Get global styles colors and output them as CSS variables.
	 *
	 * @access private
	 */
	public static function output_global_styles_colors() {
		if ( ! function_exists( 'wp_get_global_styles' ) ) {
			return;
		}

		$styles = wp_get_global_styles();
		$colors = self::get_colors( $styles );
		$vars   = self::format_css_variables( $colors, '-global' );

		if ( ! empty( $vars ) ) {
			self::output_style( implode( "\n", $vars ) );
		}
	}

	/**
	 * Get colors from a styles configuration and generate CSS variables.
	 *
	 * @param array $styles Styles object.
	 *
	 * @return array Name-value pairs of CSS variables
	 */
	private static function get_colors_as_css_variables( $styles ) {

		if ( empty( $styles ) ) {
			return [];
		}

	}

	/**
	 * Get relevant colors from global styles or a block's attributes.
	 *
	 * @param array $styles Styles object.
	 *
	 * @return array
	 */
	private static function get_colors( $styles ) {

		if ( empty( $styles ) ) {
			return [];
		}

		$vars = [];

		$element_colors = $styles['color'] ?? $styles['elements']['color'] ?? null;

		$vars['--sensei-text-color']             = $element_colors['text'] ?? $styles['textColor'] ?? null;
		$vars['--sensei-background-color']       = $element_colors['background'] ?? $styles['backgroundColor'] ?? null;
		$vars['--sensei-primary-contrast-color'] = $vars['--sensei-background-color'];

		$link = $styles['elements']['link']['color'] ?? null;

		if ( ! empty( $link ) ) {
			$vars['--sensei-primary-color'] = $link['text'];
		}

		return $vars;
	}

	/**
	 * Get CSS variables for the block from its style attributes.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return array
	 */
	private static function get_block_colors( $attributes ) {

		$styles = $attributes['style'] ?? [];

		foreach ( [ 'textColor', 'backgroundColor' ] as $color ) {
			if ( ! empty( $attributes[ $color ] ) ) {
				$styles[ $color ] = $attributes[ $color ];
			}
		}

		if ( empty( $styles ) ) {
			return [];
		}

		$colors = self::get_colors( $styles );

		return self::format_css_variables( $colors );

	}

	/**
	 * Generate CSS variables.
	 *
	 * @param array $variables Key-value pair of variable names and values.
	 * @param array $postfix   Optional variable name postfix.
	 */
	private static function format_css_variables( $variables, $postfix = '' ) {
		$css = [];

		foreach ( $variables as $variable => $value ) {
			if ( $postfix ) {
				$variable = $variable . $postfix;
			}
			if ( $value ) {
				$css[] = sprintf( '%s: %s;', $variable, self::get_property_value( $value ) );
			}
		}

		return $css;
	}

	/**
	 * Generate a style tag with the given CSS properties.
	 *
	 * @param string $css CSS properties.
	 */
	private static function output_style( $css ) {
		?>
		<style>
			body {
			<?php echo esc_html( $css ); ?>
			}
		</style>
		<?php
	}

	/**
	 * Converts CSS Custom Property stored as
	 * "var:preset|color|secondary", or single named values stored as 'secondary' to the form
	 * "--wp--preset--color--secondary".
	 *
	 * Based on \WP_Theme_JSON::get_property_value
	 *
	 * @param string $value CSS value.
	 *
	 * @return string Style property value.
	 */
	private static function get_property_value( $value ) {

		$prefix     = 'var:';
		$prefix_len = strlen( $prefix );
		$token_in   = '|';
		$token_out  = '--';
		if ( 0 === strncmp( $value, $prefix, $prefix_len ) ) {
			$unwrapped_name = str_replace(
				$token_in,
				$token_out,
				substr( $value, $prefix_len )
			);
			$value          = "var(--wp--$unwrapped_name)";
		} elseif ( preg_match( '/^[a-z0-9-]+$/i', $value ) ) {
			$value = "var(--wp--preset--color--${value})";
		}

		return $value;
	}

	/**
	 * Update the style attribute on the block wrapper element.
	 *
	 * Based on wp_render_elements_support
	 *
	 * @param string $block_content Block HTML.
	 * @param string $style         Inline styles to add.
	 *
	 * @return string
	 */
	private static function set_style_attribute( string $block_content, string $style ) {
		// Like core hooks, this assumes the hook only applies to blocks with a single wrapper.
		// Retrieve the opening tag of the first HTML element.
		$html_element_matches = array();
		$match                = preg_match( '/<[^>]+>/', $block_content, $html_element_matches, PREG_OFFSET_CAPTURE );

		if ( ! $match ) {
			return $block_content;
		}

		$first_element = $html_element_matches[0][0];

		// Add new styles if there is already a style attribute.
		if ( strpos( $first_element, 'style="' ) !== false ) {
			$content = preg_replace(
				'/' . preg_quote( 'style="', '/' ) . '/',
				'style="' . $style . ' ',
				$block_content,
				1
			);
		} else {
			// Add as new style attribute to the element.
			$first_element_offset = $html_element_matches[0][1];
			$content              = substr_replace( $block_content, ' style="' . $style . '"', $first_element_offset + strlen( $first_element ) - 1, 0 );
		}

		return $content;
	}
}

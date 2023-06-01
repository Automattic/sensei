<?php
/**
 * Adds additional compatibility with Yoast.
 *
 * @package 3rd-Party
 * @since 4.15.0
 */

/**
 * Overrides the shortcodes that Yoast loads.
 *
 * @since 4.15.0
 *
 * @param bool   $is_excluded Whether the post type is excluded.
 * @param string $post_type The post type.
 *
 * @return bool Whether the post type is excluded.
 */
function sensei_wordpress_seo_exclude_some_sensei_cpts( $is_excluded, $post_type ) {
	if ( in_array( $post_type, Sensei_PostTypes::SITEMAPS_EXCLUDED_PUBLIC_POST_TYPES, true ) ) {
		return true;
	}

	return $is_excluded;
}

add_filter( 'wpseo_sitemap_exclude_post_type', 'sensei_wordpress_seo_exclude_some_sensei_cpts', 20, 2 );

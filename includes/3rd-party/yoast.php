<?php
/**
 * Adds additional compatibility with Yoast.
 *
 * @package 3rd-Party
 * @deprecated $$next-version$$
 */

function sensei_wordpress_seo_exclude_some_sensei_cpts( $is_excluded, $post_type ) {
	if ( in_array( $post_type, Sensei_PostTypes::SITEMAPS_EXCLUDED_PUBLIC_POST_TYPES, true ) ) {
		return true;
	}

	return $is_excluded;
}

add_filter( 'wpseo_sitemap_exclude_post_type', 'sensei_wordpress_seo_exclude_some_sensei_cpts', 10, 2 );

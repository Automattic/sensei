<?php
/**
 * File containing polyfills of WordPress core functions for older versions.
 *
 * @package sensei
 */
//phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Providing WordPress core functions.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * These functions can be removed when plugin support requires WordPress 5.5.0+.
 *
 * @see https://core.trac.wordpress.org/ticket/50263
 * @see https://core.trac.wordpress.org/changeset/48141
 */
if ( ! function_exists( 'register_block_type_from_metadata' ) ) {

	/**
	 * Registers a block type from metadata stored in the `block.json` file.
	 *
	 * @param string $file_or_folder Path to the JSON file with metadata definition for
	 *     the block or path to the folder where the `block.json` file is located.
	 * @param array  $args {
	 *     Optional. Array of block type arguments. Any arguments may be defined, however the
	 *     ones described below are supported by default. Default empty array.
	 *
	 *     @type callable $render_callback Callback used to render blocks of this block type.
	 * }
	 * @return WP_Block_Type|false The registered block type on success, or false on failure.
	 * @since 7.9.0
	 */
	function register_block_type_from_metadata( $file_or_folder, $args = array() ) {

		$filename      = 'block.json';
		$metadata_file = ( substr( $file_or_folder, -strlen( $filename ) ) !== $filename )
			?
			trailingslashit( $file_or_folder ) . $filename
			:
			$file_or_folder;
		if ( ! file_exists( $metadata_file ) ) {
			return false;
		}

		$metadata = json_decode( file_get_contents( $metadata_file ), true );
		if ( ! is_array( $metadata ) || empty( $metadata['name'] ) ) {
			return false;
		}
		$metadata['file'] = $metadata_file;

		$settings          = array();
		$property_mappings = array(
			'title'           => 'title',
			'category'        => 'category',
			'parent'          => 'parent',
			'icon'            => 'icon',
			'description'     => 'description',
			'keywords'        => 'keywords',
			'attributes'      => 'attributes',
			'providesContext' => 'provides_context',
			'usesContext'     => 'uses_context',
			// Deprecated: remove with Gutenberg 8.6 release.
			'context'         => 'context',
			'supports'        => 'supports',
			'styles'          => 'styles',
			'example'         => 'example',
		);

		foreach ( $property_mappings as $key => $mapped_key ) {
			if ( isset( $metadata[ $key ] ) ) {
				$settings[ $mapped_key ] = $metadata[ $key ];
			}
		}

		return register_block_type(
			$metadata['name'],
			array_merge(
				$settings,
				$args
			)
		);
	}
}

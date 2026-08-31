<?php
/**
 * File with trait Sensei_File_System_Helper.
 *
 * @package sensei-tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Sensei_File_System_Helper {
	/**
	 * Create the 'index.html' file to mimic a theme with FSE support.
	 */
	public function create_index_file( $index_file ) {
		$theme_directory = dirname( $index_file );

		// Initialize the WP_Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		global $wp_filesystem;

		// Check if WP_Filesystem is initialized properly.
		if ( ! $wp_filesystem ) {
			return; // Or handle the error accordingly.
		}

		if ( ! $wp_filesystem->is_dir( $theme_directory ) ) {
			$wp_filesystem->mkdir( $theme_directory );
		}

		$file_contents = "Silence is golden\n";

		// Use WP_Filesystem's method to create and write to the file.
		$wp_filesystem->put_contents( $index_file, $file_contents, FS_CHMOD_FILE );
	}

	/**
	 * Remove a theme's 'index.html' file and its parent directory.
	 */
	public function remove_index_file( $index_file ) {
		$theme_directory = dirname( $index_file );

		// Initialize the WP_Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		global $wp_filesystem;

		// Check if WP_Filesystem is initialized properly.
		if ( ! $wp_filesystem ) {
			return; // Or handle the error accordingly.
		}

		$wp_filesystem->delete( $index_file );
		$wp_filesystem->rmdir( $theme_directory );
	}
}

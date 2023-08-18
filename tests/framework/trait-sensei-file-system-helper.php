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

		$file_contents = "Silence is golden\n";

		// Use WP_Filesystem's method to create and write to the file.
		$wp_filesystem->put_contents( $index_file, $file_contents, FS_CHMOD_FILE );
	}
}

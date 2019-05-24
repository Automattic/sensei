<?php
/**
 * Adds additional compatibility with WordPress Importer.
 *
 * @package 3rd-Party
 */

/**
 * Track when dummy data is imported but don't track any events that could fire during import.
 *
 * @return bool
 */
function sensei_wordpress_importer_usage_tracking_dummy_data() {
	// Log the import event if we're importing the dummy data.
	$global_importer = isset( $GLOBALS['wp_import'] ) ? $GLOBALS['wp_import'] : false;
	if (
		$global_importer
		&& 'WP_Import' === get_class( $global_importer )
		&& 'http://demo.sensei.com/' === $global_importer->base_url
	) {
		sensei_log_event( 'data_import', [ 'dummy_data' => 1 ] );

		// Disable tracking of other events.
		add_filter( 'sensei_log_event', 'sensei_wordpress_importer_disable_event_tracking' );
	}
}

/**
 * Attaches modules to lessons for dummy data.
 */
function sensei_wordpress_importer_add_modules_to_imported_lessons() {
	$modules_with_lessons = array(
		'chords-101-the-building-blocks-of-music' => array( 7706, 7709 ),
		'rhythm-101-the-heartbeat-of-music'       => array( 7711, 7713 ),
		'your-first-songs'                        => array( 7715, 7717, 7719, 7721 ),
	);

	foreach ( $modules_with_lessons as $module_slug => $lesson_ids ) {

		$order = 1;
		$term  = get_term_by( 'slug', $module_slug, 'module' );

		if ( ! $term ) {
			return;
		}

		$module_id = $term->term_id;

		// Attach the module to each lesson.
		foreach ( $lesson_ids as $lesson_id ) {
			update_post_meta( $lesson_id, '_order_module_' . $module_id, $order );
			$order++;
		}
	}

	remove_filter( 'sensei_log_event', 'sensei_wordpress_importer_disable_event_tracking' );
}

/**
 * Helper function to disable event tracking while importing dummy data.
 *
 * @return bool
 */
function sensei_wordpress_importer_disable_event_tracking() {
	return false;
}

add_action( 'import_start', 'sensei_wordpress_importer_usage_tracking_dummy_data' );
add_action( 'import_end', 'sensei_wordpress_importer_add_modules_to_imported_lessons' );

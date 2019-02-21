<?php
/**
 * Adds additional compatibility with WordPress Importer.
 *
 * @package 3rd-Party
 */

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
}

add_action( 'import_end', 'sensei_wordpress_importer_add_modules_to_imported_lessons' );

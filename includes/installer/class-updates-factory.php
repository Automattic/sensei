<?php
/**
 * File containing the class \Sensei\Installer\Updates_Factory.
 *
 * @package sensei
 */

namespace Sensei\Installer;

/**
 * Updates factory class.
 *
 * @since $$next-version$$
 */
class Updates_Factory {
	/**
	 * Create Updates instance.
	 *
	 * @param mixed       $current_version Current Sensei version based on stored settings.
	 * @param string|null $plugin_version Sensei version based on plugin code.
	 *
	 * @return \Sensei_Updates
	 */
	public function create( $current_version, ?string $plugin_version ): \Sensei_Updates {
		$is_upgrade     = $current_version && version_compare( $plugin_version, $current_version, '>' );
		$is_new_install = ! $current_version && ! $this->course_exists();

		return new \Sensei_Updates( $current_version, $is_new_install, $is_upgrade );
	}

	/**
	 * Helper function to check to see if any courses exist in the database.
	 *
	 * @return bool
	 */
	private function course_exists(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Lightweight query run only once before post type is registered.
		$course_sample_id = (int) $wpdb->get_var( "SELECT `ID` FROM {$wpdb->posts} WHERE `post_type`='course' LIMIT 1" );

		return ! empty( $course_sample_id );
	}
}

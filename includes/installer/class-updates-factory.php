<?php

namespace Sensei\Installer;

class Updates_Factory {
	/**
	 * Create Updates instance.
	 *
	 * @param mixed $sensei_version Current Sensei version based on stored settings.
	 * @param bool  $is_upgrade     Whether this is an upgrade or not.
	 *
	 * @return \Sensei_Updates
	 */
	public static function create( $sensei_version, bool $is_upgrade ): \Sensei_Updates {
		$is_new_install = ! $sensei_version && ! self::course_exists();

		return new \Sensei_Updates( $sensei_version, $is_new_install, $is_upgrade );
	}

	/**
	 * Helper function to check to see if any courses exist in the database.
	 *
	 * @return bool
	 */
	private static function course_exists() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Lightweight query run only once before post type is registered.
		$course_sample_id = (int) $wpdb->get_var( "SELECT `ID` FROM {$wpdb->posts} WHERE `post_type`='course' LIMIT 1" );

		return ! empty( $course_sample_id );
	}
}

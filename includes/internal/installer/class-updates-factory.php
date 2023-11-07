<?php
/**
 * File containing the class \Sensei\Internal\Installer\Updates_Factory.
 *
 * @package sensei
 */

namespace Sensei\Internal\Installer;

/**
 * Updates factory class.
 *
 * @internal
 *
 * @since 4.16.1
 */
class Updates_Factory {
	/**
	 * Create Updates instance.
	 *
	 * @internal
	 *
	 * @param mixed       $current_version Current Sensei version based on stored settings.
	 * @param string|null $plugin_version Sensei version based on plugin code.
	 * @param bool        $is_new_install Whether this is a new install.
	 *
	 * @return \Sensei_Updates
	 */
	public function create( $current_version, ?string $plugin_version, bool $is_new_install ): \Sensei_Updates {
		$is_upgrade = $current_version && version_compare( $plugin_version, $current_version, '>' );

		return new \Sensei_Updates( $current_version, $is_new_install, $is_upgrade );
	}
}

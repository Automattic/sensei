<?php
/**
 * Setup Wizard mocks.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Mocks for Setup Wizard E2E tests.
 */
class Sensei_E2E_Setup_Wizard_Mocks {

	/**
	 * Register filters.
	 */
	public function register() {
		add_filter( 'plugins_api', [ $this, 'plugins_api' ], 99, 3 );
	}

	/**
	 * Filter plugins_api hook.
	 *
	 * Provides a path to a mock plugin file for installation for sensei-certificates.
	 *
	 * @access private
	 *
	 * @param false|object|array $result Plugin API result.
	 * @param string             $action Plugin API action.
	 * @param object             $args Plugin API arguments.
	 *
	 * @return object|false Overridden response or the default $result value.
	 */
	public function plugins_api( $result, $action, $args ) {
		$dir = __DIR__;
		error_log( 'plugins_api ' . $action );
		if ( 'plugin_information' === $action && 'sensei-certificates' === $args->slug ) {

			$plugin_file = tempnam( sys_get_temp_dir(), 'sensei' );

			if ( file_exists( $plugin_file ) ) {
				unlink( $plugin_file );
			}

			link( path_join( $dir, 'sensei-certificates.zip' ), $plugin_file );

			return (object) [
				'download_link' => $plugin_file
			];
		}
		return $result;
	}
}
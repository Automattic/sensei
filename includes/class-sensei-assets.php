<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // End if().

/**
 * Loading CSS and Javascript files
 *
 * @package Core
 * @author  Automattic
 * @since   3.1.0
 */
class Sensei_Assets {

	protected $plugin_url;
	protected $plugin_path;
	protected $version;

	public function __construct( $plugin_url, $plugin_path, $version ) {
		$this->plugin_url  = $plugin_url;
		$this->plugin_path = $plugin_path;
		$this->version     = $version;
	}

	/**
	 * Enqueue a script or stylesheet with wp_enqueue_script/wp_enqueue_style
	 *
	 * @see enqueue_asset
	 *
	 * @param string      $filename     The filename
	 * @param array       $dependencies Style dependencies
	 * @param bool|string $args         In footer flag (script) or media type (style)
	 */
	public function enqueue( $filename, $dependencies = [], $args = null ) {
		$config = $this->asset_config( $filename, $dependencies, $args );
		$this->call_wp( 'wp_enqueue', $config );
	}

	public function register( $name, $filename, $dependencies = [], $args = null ) {
		$config = $this->asset_config( $filename, $dependencies, $args );
		if ( $name ) {
			$config['name'] = $name;
		}
		$this->call_wp( 'wp_register', $config );
	}

	private function call_wp( $action, $config ) {
		call_user_func( $action . '_' . $config['type'], $config['name'], $config['url'], $config['dependencies'], $config['version'], $config["args"] );

	}

	/**
	 * Loads asset metadata (dependencies tracked by the build process, version hash)
	 * Generates a name with a sensei- prefix:
	 * 'js/admin/lesson.js' > 'sensei-js-admin-lesson'
	 *
	 * @param string      $type         Asset type (script or style)
	 * @param string      $filename     The filename
	 * @param array       $dependencies Dependencies
	 * @param bool|string $extra_arg    Argument passed to wp_enqueue_script or wp_enqueue_style
	 */

	/**
	 * @param string $filename     The filename
	 * @param array  $dependencies Dependencies
	 *
	 * @return array Asset information
	 */
	public function asset_config( $filename, $dependencies = [], $args = null ) {

		$is_js             = preg_match( '/\.js$/', $filename );
		$basename          = preg_replace( '/\.\w+$/', '', $filename );
		$name              = 'sensei-' . preg_replace( '/[]\/\.]/', '-', $basename );
		$url               = $this->asset_url( $filename );
		$version           = $this->version;
		$asset_config_path = path_join( $this->plugin_path, 'assets/dist/' . $filename . '.asset.php' );

		if ( file_exists( $asset_config_path ) ) {
			$asset_config = require $asset_config_path;
			if ( $is_js ) {
				$dependencies = array_merge( $dependencies, $asset_config['dependencies'] );
			}
			$version = $asset_config['version'];
		}

		return [
			"name"         => $name,
			"url"          => $url,
			"dependencies" => $dependencies,
			"version"      => $version,
			"type"         => $is_js ? "script" : "style",
			"args"         => $args || $is_js ? false : "all" // defaults for wp_enqueue_script or wp_enqueue_style
		];
	}

	private function asset_url( $filename ) {
		return rtrim( $this->plugin_url, '/' ) . '/assets/dist/' . $filename;
	}
}
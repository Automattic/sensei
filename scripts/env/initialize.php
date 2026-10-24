<?php
/**
 * Initialize a private development copy using settings supplied by the host tool.
 *
 * @package sensei
 */

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read the private local settings file.
$sensei_env_settings = json_decode( file_get_contents( '/sensei-private/initialize.json' ), true );
if ( ! is_array( $sensei_env_settings ) || is_multisite() ) {
	WP_CLI::error( 'Initialization requires a single-site development copy and valid settings.' );
}

update_option( 'active_plugins', array() );
require_once ABSPATH . 'wp-admin/includes/plugin.php';
foreach ( $sensei_env_settings['plugins'] as $sensei_env_slug ) {
	$sensei_env_matches = array_filter(
		array_keys( get_plugins() ),
		static function ( $file ) use ( $sensei_env_slug ) {
			return dirname( $file ) === $sensei_env_slug || $file === $sensei_env_slug . '.php';
		}
	);
	if ( 1 !== count( $sensei_env_matches ) ) {
		WP_CLI::error( 'Cannot identify installed plugin: ' . $sensei_env_slug );
	}
	$sensei_env_result = activate_plugin( reset( $sensei_env_matches ) );
	if ( is_wp_error( $sensei_env_result ) ) {
		WP_CLI::error( $sensei_env_result->get_error_message() );
	}
}

$sensei_env_admin = get_user_by( 'login', 'admin' );
$sensei_env_data  = array(
	'user_login' => 'admin',
	'user_pass'  => $sensei_env_settings['adminPassword'],
	'user_email' => 'admin@example.test',
	'role'       => 'administrator',
);
if ( $sensei_env_admin ) {
	$sensei_env_data['ID'] = $sensei_env_admin->ID;
}
$sensei_env_result = wp_insert_user( $sensei_env_data );
if ( is_wp_error( $sensei_env_result ) ) {
	WP_CLI::error( $sensei_env_result->get_error_message() );
}

if ( ! wp_get_theme( 'course' )->exists() ) {
	WP_CLI::error( 'The course theme is missing.' );
}
switch_theme( 'course' );
flush_rewrite_rules();
WP_CLI::success( 'Development copy initialized.' );

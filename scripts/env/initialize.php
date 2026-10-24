<?php
/**
 * Initialize a private development copy using settings supplied by the host tool.
 */
$settings = json_decode( file_get_contents( '/sensei-private/initialize.json' ), true );
if ( ! is_array( $settings ) || is_multisite() ) {
	WP_CLI::error( 'Initialization requires a single-site development copy and valid settings.' );
}

update_option( 'active_plugins', array() );
require_once ABSPATH . 'wp-admin/includes/plugin.php';
foreach ( $settings['plugins'] as $slug ) {
	$matches = array_filter( array_keys( get_plugins() ), static function ( $file ) use ( $slug ) {
		return dirname( $file ) === $slug || $file === $slug . '.php';
	} );
	if ( 1 !== count( $matches ) ) {
		WP_CLI::error( 'Cannot identify installed plugin: ' . $slug );
	}
	$result = activate_plugin( reset( $matches ) );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
}

$admin = get_user_by( 'login', 'admin' );
$data  = array(
	'user_login' => 'admin',
	'user_pass'  => $settings['adminPassword'],
	'user_email' => 'admin@example.test',
	'role'       => 'administrator',
);
if ( $admin ) {
	$data['ID'] = $admin->ID;
}
$result = wp_insert_user( $data );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

if ( ! wp_get_theme( 'course' )->exists() ) {
	WP_CLI::error( 'The course theme is missing.' );
}
switch_theme( 'course' );
flush_rewrite_rules();
WP_CLI::success( 'Development copy initialized.' );

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const crypto = require( 'node:crypto' );
const registry = require( './registry.cjs' );

const literalPrefix = /^\s*\$table_prefix\s*=\s*(['"])([a-zA-Z0-9_]+)\1\s*;/m;
const dockerPrefix =
	/^\s*\$table_prefix\s*=\s*getenv_docker\(\s*['"]WORDPRESS_TABLE_PREFIX['"],\s*(['"])([a-zA-Z0-9_]+)\1\s*\)\s*;/m;

function configFile( state ) {
	const file = path.join( registry.directory( state.id ), 'wp-config.php' );
	let contents = fs.existsSync( file )
		? fs.readFileSync( file, 'utf8' )
		: null;
	const match =
		contents?.match( literalPrefix ) || contents?.match( dockerPrefix );
	const prefix = state.tablePrefix || match?.[ 2 ] || 'wp_';
	if ( ! /^[a-zA-Z0-9_]+$/.test( prefix ) || ( contents && ! match ) ) {
		throw new Error(
			'Cannot preserve the development database table prefix.'
		);
	}
	state.tablePrefix = prefix;
	if ( contents ) {
		contents = contents.replace(
			match[ 0 ],
			`$table_prefix = '${ prefix }';`
		);
	} else {
		const salts = [
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		]
			.map(
				( key ) =>
					`define( '${ key }', '${ crypto
						.randomBytes( 48 )
						.toString( 'hex' ) }' );`
			)
			.join( '\n' );
		contents = `<?php
// Database credentials follow the wp-env container environment.
define( 'DB_NAME', getenv( 'WORDPRESS_DB_NAME' ) ?: 'wordpress' );
define( 'DB_USER', getenv( 'WORDPRESS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WORDPRESS_DB_PASSWORD' ) ?: 'password' );
define( 'DB_HOST', getenv( 'WORDPRESS_DB_HOST' ) ?: 'mysql' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
${ salts }
$table_prefix = '${ prefix }';
define( 'WP_DEBUG', false );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
`;
	}
	// Preserve the inode when this file is already bind-mounted into a container.
	fs.writeFileSync( file, contents, { mode: 0o600 } );
	return file;
}

async function prepare( state, commands ) {
	if ( state.storagePrepared ) return;
	const dir = registry.directory( state.id );
	if ( state.adoptedFrom || fs.existsSync( path.join( dir, 'runtime' ) ) ) {
		const ids = (
			await commands.run(
				'docker',
				[
					'ps',
					'-aq',
					'--filter',
					`label=com.docker.compose.project=${ state.project }`,
				],
				{ capture: true }
			)
		)
			.split( '\n' )
			.filter( Boolean );
		if ( ids.length ) {
			const containers = JSON.parse(
				await commands.run( 'docker', [ 'inspect', ...ids ], {
					capture: true,
				} )
			);
			for ( const container of containers ) {
				const labels = container.Config?.Labels;
				if (
					labels?.[ 'com.docker.compose.project' ] !== state.project
				) {
					throw new Error(
						'Storage migration container ownership mismatch.'
					);
				}
				const service = labels[ 'com.docker.compose.service' ];
				if ( ! [ 'cli', 'tests-cli' ].includes( service ) ) continue;
				const plugins = path.join(
					dir,
					service === 'cli' ? 'plugins' : 'tests-plugins'
				);
				fs.mkdirSync( plugins, { recursive: true, mode: 0o700 } );
				// Read old mounts through a disposable helper, even when the CLI is stopped.
				await commands.run(
					'docker',
					[
						'run',
						'--rm',
						'--network=none',
						'--volumes-from',
						`${ container.Id }:ro`,
						'--mount',
						`type=bind,src=${ plugins },dst=/sensei-persist`,
						'--user',
						container.Config.User || '0:0',
						'--entrypoint',
						'sh',
						container.Config.Image,
						'-c',
						'for entry in /var/www/html/wp-content/plugins/* /var/www/html/wp-content/plugins/.[!.]* /var/www/html/wp-content/plugins/..?*; do [ -e "$entry" ] || continue; [ "${entry##*/}" = sensei ] && continue; cp -an "$entry" /sensei-persist/ || exit; done',
					],
					{ capture: true }
				);
				const config = path.join( dir, 'wp-config.php' );
				if ( service === 'cli' && ! fs.existsSync( config ) ) {
					await commands.run(
						'docker',
						[
							'cp',
							`${ container.Id }:/var/www/html/wp-config.php`,
							config,
						],
						{ capture: true }
					);
					fs.chmodSync( config, 0o600 );
				}
				if ( service === 'cli' && ! state.tablePrefix ) {
					const contents = fs.readFileSync( config, 'utf8' );
					const literal = contents.match( literalPrefix );
					const fromEnvironment = container.Config.Env?.find(
						( entry ) =>
							entry.startsWith( 'WORDPRESS_TABLE_PREFIX=' )
					);
					if ( literal ) {
						state.tablePrefix = literal[ 2 ];
					} else if (
						contents.match( dockerPrefix ) &&
						fromEnvironment
					) {
						state.tablePrefix = fromEnvironment.slice(
							'WORDPRESS_TABLE_PREFIX='.length
						);
					}
				}
			}
		}
	}
	state.storagePrepared = true;
}
module.exports = { configFile, prepare };

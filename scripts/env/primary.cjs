const fs = require( 'node:fs' );
const path = require( 'node:path' );
const registry = require( './registry.cjs' );

function hostPath( value ) {
	return registry.canonicalPath(
		value.startsWith( '/host_mnt/' ) ? value.slice( 9 ) : value
	);
}
function mount( container, destination ) {
	return container.Mounts?.find(
		( entry ) => entry.Destination === destination
	);
}
async function existing( state, commands ) {
	const ids = (
		await commands.run(
			'docker',
			[
				'ps',
				'-aq',
				'--filter',
				'label=com.docker.compose.project=sensei-lms',
			],
			{ capture: true }
		)
	)
		.split( '\n' )
		.filter( Boolean );
	if ( ! ids.length ) {
		const volumes = (
			await commands.run(
				'docker',
				[ 'volume', 'ls', '--format', '{{.Name}}' ],
				{ capture: true }
			)
		).split( '\n' );
		if (
			volumes.some( ( name ) =>
				[ 'sensei-lms_mysql', 'sensei-lms_mysql-test' ].includes( name )
			)
		)
			throw new Error(
				'Existing sensei-lms database volumes have no containers. Restore its original Compose environment before adopting it.'
			);
		return null;
	}
	const containers = JSON.parse(
		await commands.run( 'docker', [ 'inspect', ...ids ], { capture: true } )
	);
	const service = ( name ) => {
		const matches = containers.filter(
			( item ) =>
				item.Config?.Labels?.[ 'com.docker.compose.service' ] === name
		);
		if (
			matches.length !== 1 ||
			matches[ 0 ].Config.Labels[ 'com.docker.compose.project' ] !==
				'sensei-lms'
		) {
			throw new Error(
				`Cannot safely adopt the sensei-lms ${ name } service.`
			);
		}
		return matches[ 0 ];
	};
	const cli = service( 'cli' );
	const wordpress = service( 'wordpress' );
	const tests = service( 'tests-wordpress' );
	const plugin = mount( cli, '/var/www/html/wp-content/plugins/sensei' );
	if (
		! plugin ||
		plugin.Type !== 'bind' ||
		hostPath( plugin.Source ) !== state.worktree
	) {
		throw new Error(
			'sensei-lms belongs to a different checkout. Its files and data have not been changed.'
		);
	}
	for ( const [ name, volume ] of [
		[ 'mysql', 'sensei-lms_mysql' ],
		[ 'tests-mysql', 'sensei-lms_mysql-test' ],
	] ) {
		const database = mount( service( name ), '/var/lib/mysql' );
		if ( database?.Type !== 'volume' || database.Name !== volume ) {
			throw new Error(
				`Cannot safely reuse the existing ${ name } database storage.`
			);
		}
	}
	const core = mount( cli, '/var/www/html' );
	const testCore = mount( tests, '/var/www/html' );
	if ( core?.Type !== 'bind' || testCore?.Type !== 'bind' ) {
		throw new Error(
			'Adoption requires the existing wp-env WordPress directories.'
		);
	}
	const compose =
		cli.Config.Labels[ 'com.docker.compose.project.config_files' ];
	if (
		! compose ||
		! path.isAbsolute( compose ) ||
		! fs.existsSync( compose )
	) {
		throw new Error(
			'Cannot locate the existing sensei-lms Compose configuration.'
		);
	}
	if (
		/[\/]environments[\/][a-f0-9]{16}[\/]runtime[\/]/.test( compose ) &&
		! compose.startsWith(
			path.join( registry.directory( state.id ), 'runtime' ) + path.sep
		)
	) {
		throw new Error(
			'sensei-lms is already managed by another private registry.'
		);
	}
	const port = ( container ) => {
		const value = Number(
			container.HostConfig.PortBindings?.[ '80/tcp' ]?.[ 0 ]?.HostPort
		);
		if ( ! Number.isInteger( value ) || value < 1 || value > 65535 )
			throw new Error( 'Cannot preserve the existing WordPress port.' );
		return value;
	};
	const theme = mount( cli, '/var/www/html/wp-content/themes/course' );
	const php = cli.Config.Env?.find( ( entry ) =>
		entry.startsWith( 'PHP_VERSION=' )
	)
		?.slice( 12 )
		.match( /^\d+\.\d+/ )?.[ 0 ];
	return {
		port: port( wordpress ),
		testsPort: port( tests ),
		php,
		initialized: true,
		initialization: 'complete',
		cloneSource: null,
		adoptedFrom: {
			compose,
			core: hostPath( core.Source ),
			testCore: hostPath( testCore.Source ),
			theme: theme?.Type === 'bind' ? hostPath( theme.Source ) : null,
		},
	};
}
function preserveUploads( state ) {
	if ( ! state.adoptedFrom || state.adoptionPrepared ) return;
	for ( const [ source, name ] of [
		[ state.adoptedFrom.core, 'uploads' ],
		[ state.adoptedFrom.testCore, 'tests-uploads' ],
	] ) {
		const uploads = path.join( source, 'wp-content/uploads' );
		const destination = path.join( registry.directory( state.id ), name );
		fs.mkdirSync( destination, { recursive: true, mode: 0o700 } );
		if ( fs.existsSync( uploads ) )
			fs.cpSync( uploads, destination, {
				recursive: true,
				dereference: true,
				mode: fs.constants.COPYFILE_FICLONE,
			} );
	}
	state.adoptionPrepared = true;
}
module.exports = { existing, preserveUploads };

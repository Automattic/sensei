const fs = require( 'node:fs' );
const path = require( 'node:path' );
const os = require( 'node:os' );
const crypto = require( 'node:crypto' );
const net = require( 'node:net' );
const { execFileSync } = require( 'node:child_process' );

function canonicalPath( input ) {
	let ancestor = path.resolve( input );
	const tail = [];
	while ( ! fs.existsSync( ancestor ) ) {
		tail.unshift( path.basename( ancestor ) );
		ancestor = path.dirname( ancestor );
	}
	return path.join( fs.realpathSync( ancestor ), ...tail );
}
function home() {
	const selected =
		process.env.SENSEI_ENV_HOME ||
		path.join( os.homedir(), '.local/share/sensei-dev' );
	if ( ! path.isAbsolute( selected ) ) {
		throw new Error( 'SENSEI_ENV_HOME must be absolute.' );
	}
	const resolved = canonicalPath( selected );
	const checkout = fs.realpathSync( path.resolve( __dirname, '../..' ) );
	if ( resolved === checkout || resolved.startsWith( checkout + path.sep ) ) {
		throw new Error(
			'Private environment storage must be outside the checkout.'
		);
	}
	return resolved;
}
const readJSON = ( file, fallback ) =>
	fs.existsSync( file )
		? JSON.parse( fs.readFileSync( file, 'utf8' ) )
		: fallback;
function writeJSON( file, value ) {
	fs.mkdirSync( path.dirname( file ), { recursive: true, mode: 0o700 } );
	const temporary = `${ file }.${ process.pid }.tmp`;
	fs.writeFileSync( temporary, JSON.stringify( value, null, 2 ) + '\n', {
		mode: 0o600,
	} );
	fs.renameSync( temporary, file );
}
function gitMetadata( worktree ) {
	const output = execFileSync(
		'git',
		[
			'rev-parse',
			'--path-format=absolute',
			'--git-dir',
			'--git-common-dir',
		],
		{ cwd: worktree, encoding: 'utf8' }
	)
		.trim()
		.split( '\n' );
	return {
		gitDir: canonicalPath( output[ 0 ] ),
		commonDir: canonicalPath( output[ 1 ] ),
	};
}
function identity( worktree, metadata = gitMetadata( worktree ) ) {
	// Separate private registries must never share Docker volumes.
	return crypto
		.createHash( 'sha256' )
		.update( `${ home() }\0${ metadata.gitDir }` )
		.digest( 'hex' )
		.slice( 0, 16 );
}
function nameSlug( value ) {
	const slug = value
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-+|-+$/g, '' )
		.slice( 0, 32 )
		.replace( /-+$/, '' );
	if ( ! slug )
		throw new Error( 'Environment names must contain letters or digits.' );
	return slug;
}
function environmentName( worktree, metadata, requested ) {
	if ( requested ) return nameSlug( requested );
	if ( metadata.gitDir === metadata.commonDir ) return 'primary';
	const branch = execFileSync( 'git', [ 'branch', '--show-current' ], {
		cwd: worktree,
		encoding: 'utf8',
	} ).trim();
	let folder = path.basename( worktree );
	if ( /^(sensei|sensei-lms)$/i.test( folder ) )
		folder = path.basename( path.dirname( worktree ) );
	if ( /^[a-f0-9]{4,}$/i.test( folder ) ) folder = 'worktree';
	const label = branch || folder;
	return /[a-z0-9]/i.test( label ) ? nameSlug( label ) : 'worktree';
}
const directory = ( id ) => path.join( home(), 'environments', id );
const projectName = ( id, name ) => `sensei-dev-${ name }-${ id }`;
const configPath = ( state ) =>
	state.name
		? path.join(
				directory( state.id ),
				'config',
				state.name,
				'.wp-env.json'
		  )
		: path.join( directory( state.id ), '.wp-env.json' );
function records() {
	const root = path.join( home(), 'environments' );
	return fs.existsSync( root )
		? fs
				.readdirSync( root )
				.filter( ( id ) => /^[a-f0-9]{16}$/.test( id ) )
				.map( ( id ) => {
					const state = readJSON(
						path.join( root, id, 'state.json' ),
						null
					);
					if (
						! state ||
						state.id !== id ||
						identity( state.worktree, state ) !== id ||
						! path.isAbsolute( state.gitDir || '' ) ||
						( state.name !== undefined &&
							( typeof state.name !== 'string' ||
								! /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(
									state.name
								) ||
								state.name.length > 32 ) ) ||
						( state.primary !== undefined &&
							state.primary !== true ) ||
						( state.primary && state.gitDir !== state.commonDir ) ||
						state.project !==
							( state.primary
								? 'sensei-lms'
								: state.name
								? projectName( id, state.name )
								: `sensei-dev-${ id }` )
					) {
						throw new Error( `Invalid ownership record: ${ id }` );
					}
					if ( state.gitDir !== state.commonDir ) {
						const pointer = path.join( state.gitDir, 'gitdir' );
						if ( fs.existsSync( pointer ) ) {
							state.worktree = canonicalPath(
								path.dirname(
									fs.readFileSync( pointer, 'utf8' ).trim()
								)
							);
						}
					}
					return state;
				} )
		: [];
}
async function lock( name, callback ) {
	fs.mkdirSync( home(), { recursive: true, mode: 0o700 } );
	const file = path.join( home(), `${ name }.lock` );
	const deadline = Date.now() + 30000;
	while ( true ) {
		try {
			fs.writeFileSync( file, String( process.pid ), {
				flag: 'wx',
				mode: 0o600,
			} );
			break;
		} catch ( error ) {
			if ( error.code !== 'EEXIST' ) {
				throw error;
			}
			if ( Date.now() >= deadline ) {
				throw new Error(
					`Another operation holds ${ file }. If interrupted, verify its PID before removing the lock.`
				);
			}
			await new Promise( ( resolve ) => setTimeout( resolve, 100 ) );
		}
	}
	try {
		return await callback();
	} finally {
		fs.unlinkSync( file );
	}
}
function portAvailable( port ) {
	return new Promise( ( resolve ) => {
		const server = net.createServer();
		server.once( 'error', () => resolve( false ) );
		server.listen( { port, host: '::', ipv6Only: false }, () =>
			server.close( () => resolve( true ) )
		);
	} );
}
async function allocatePorts() {
	const used = new Set(
		records().flatMap( ( state ) => [ state.port, state.testsPort ] )
	);
	const ports = [];
	for (
		let port = process.env.CI ? 8888 : 9000;
		port < 65000 && ports.length < 2;
		port++
	) {
		if ( ! used.has( port ) && ( await portAvailable( port ) ) ) {
			ports.push( port );
		}
	}
	if ( ports.length !== 2 ) {
		throw new Error( 'Could not allocate two available ports.' );
	}
	return { port: ports[ 0 ], testsPort: ports[ 1 ] };
}
function orphaned( state ) {
	if ( state.gitDir && fs.existsSync( state.commonDir ) ) {
		return ! fs.existsSync( state.gitDir );
	}
	if ( fs.existsSync( state.worktree ) ) {
		return false;
	}
	// Missing directories can still be registered worktrees on unavailable disks.
	if ( ! fs.existsSync( state.commonDir ) ) {
		return false;
	}
	const listing = execFileSync(
		'git',
		[
			`--git-dir=${ state.commonDir }`,
			'worktree',
			'list',
			'--porcelain',
			'-z',
		],
		{ encoding: 'utf8' }
	);
	return ! listing.split( '\0' ).includes( `worktree ${ state.worktree }` );
}
module.exports = {
	canonicalPath,
	gitMetadata,
	home,
	readJSON,
	writeJSON,
	identity,
	nameSlug,
	environmentName,
	projectName,
	configPath,
	directory,
	records,
	lock,
	portAvailable,
	allocatePorts,
	orphaned,
};

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { spawn, execFileSync } = require( 'node:child_process' );
const registry = require( './registry.cjs' );
const cloning = require( './clone.cjs' );
const storage = require( './storage.cjs' );
const primary = require( './primary.cjs' );
const { stream } = require( './stream.cjs' );

const toolRoot = path.resolve( __dirname, '../..' );
function run( executable, args, options = {} ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( executable, args, {
			stdio: [
				options.capture ? 'ignore' : 'inherit',
				options.capture ? 'pipe' : 'inherit',
				options.capture ? 'pipe' : 'inherit',
			],
			...options,
		} );

		let stdout = '';
		let stderr = '';
		if ( options.capture ) {
			child.stdout.on( 'data', ( data ) => {
				stdout += data;
			} );
			child.stderr.on( 'data', ( data ) => {
				stderr += data;
			} );
		}
		child.on( 'error', reject );
		child.on( 'close', ( code ) =>
			code === 0
				? resolve( stdout.trim() )
				: reject(
						new Error(
							`${ executable } failed (${ code }). ${ stderr }`
						)
				  )
		);
	} );
}
function childEnvironment( state ) {
	const env = { ...process.env };
	for ( const key of Object.keys( env ) ) {
		if ( key.startsWith( 'WP_ENV_' ) || key.startsWith( 'COMPOSE_' ) ) {
			delete env[ key ];
		}
	}
	return {
		...env,
		COMPOSE_PROJECT_NAME: state.project,
		WP_ENV_HOME: path.join( registry.directory( state.id ), 'runtime' ),
	};
}
function wpEnv( state, args ) {
	return run(
		process.execPath,
		[
			path.join( toolRoot, 'node_modules/@wordpress/env/bin/wp-env' ),
			'--config',
			registry.configPath( state ),
			...args,
		],
		{ cwd: state.worktree, env: childEnvironment( state ) }
	);
}
function save( state ) {
	registry.writeJSON(
		path.join( registry.directory( state.id ), 'state.json' ),
		state
	);
}
function current() {
	const worktree = fs.realpathSync( toolRoot );
	const id = registry.identity( worktree );
	return registry.records().find( ( state ) => state.id === id );
}
function settingsFor() {
	const settings = process.env.CI
		? {}
		: registry.readJSON(
				path.join( registry.home(), 'settings.json' ),
				{}
		  );

	if (
		settings.theme &&
		( ! path.isAbsolute( settings.theme ) ||
			path.basename( settings.theme ) !== 'course' ||
			! fs.statSync( settings.theme ).isDirectory() )
	) {
		throw new Error(
			'Private theme must be an absolute course theme directory.'
		);
	}

	return {
		theme: settings.theme,
		adminPassword: settings.adminPassword || 'password',
	};
}

async function create( options = {} ) {
	return registry.lock( 'ports', async () => {
		const existing = current();
		if ( existing ) {
			return existing;
		}
		const worktree = fs.realpathSync( toolRoot );
		const metadata = registry.gitMetadata( worktree );
		const id = registry.identity( worktree, metadata );
		const name = registry.environmentName(
			worktree,
			metadata,
			options.name
		);
		const canonical =
			! process.env.CI && metadata.gitDir === metadata.commonDir;
		if ( canonical && registry.records().some( ( item ) => item.primary ) )
			throw new Error(
				'sensei-lms is already owned by another primary checkout in this registry.'
			);
		const state = {
			id,
			worktree,
			name,
			project: canonical
				? 'sensei-lms'
				: registry.projectName( id, name ),
			...( canonical ? { primary: true } : {} ),
			...metadata,
			...( await registry.allocatePorts() ),
			initialized: false,
			createdAt: new Date().toISOString(),
		};
		const adopted = canonical
			? await primary.existing( state, { run } )
			: null;
		if ( adopted ) Object.assign( state, adopted );
		else {
			state.cloneSource = await cloning.selectSource(
				{ run },
				{
					...options,
					targetProject: state.project,
					commonDir: state.commonDir,
				}
			);
		}
		save( state );
		return state;
	} );
}
function coreSource( version ) {
	if ( version === 'latest' ) {
		return null;
	}
	if ( version === 'nightly' ) {
		return 'https://wordpress.org/nightly-builds/wordpress-latest.zip';
	}
	if ( /^\d+\.\d+(?:\.\d+)?-(?:beta|RC)\d+$/i.test( version ) ) {
		return `https://wordpress.org/wordpress-${ version }.zip`;
	}
	if ( /^\d+\.\d+\.\d+$/.test( version ) ) {
		return `https://wordpress.org/wordpress-${ version }.zip`;
	}
	if ( /^\d+\.\d+$/.test( version ) ) {
		return `WordPress/WordPress#${ version }-branch`;
	}
	throw new Error( `Invalid WordPress version: ${ version }` );
}
function configuration( state, configured ) {
	const config = registry.readJSON(
		path.join( state.worktree, '.wp-env.json' )
	);
	const dir = registry.directory( state.id );
	for ( const name of [
		'uploads',
		'tests-uploads',
		'plugins',
		'tests-plugins',
		'exchange',
		'mu-plugins',
	] ) {
		fs.mkdirSync( path.join( dir, name ), {
			recursive: true,
			mode: 0o700,
		} );
	}
	fs.copyFileSync(
		path.join( __dirname, 'local-guard.php' ),
		path.join( dir, 'mu-plugins', 'sensei-local-guard.php' )
	);
	// Fix the plugin mount name even when the checkout directory has another name.
	config.plugins = [];
	if ( configured.theme || state.adoptedFrom?.theme ) {
		config.themes = [ configured.theme || state.adoptedFrom.theme ];
	}
	config.mappings = {
		...config.mappings,
		'wp-content/plugins/sensei': state.worktree,
	};
	config.port = state.port;
	config.testsPort = state.testsPort;
	config.autoPort = false;
	if ( state.wp ) {
		config.core = coreSource( state.wp );
	} else if ( state.adoptedFrom ) {
		config.core = state.adoptedFrom.core;
	}
	if ( state.php ) {
		if ( ! /^\d+\.\d+$/.test( state.php ) ) {
			throw new Error( 'PHP must have the form 8.3.' );
		}
		config.phpVersion = state.php;
	}
	config.env = {
		development: {
			config: { DISABLE_WP_CRON: true },
			mappings: {
				'wp-content/uploads': path.join( dir, 'uploads' ),
				'wp-content/plugins': path.join( dir, 'plugins' ),
				// The WordPress entrypoint uses this template before installing the site.
				'../../../usr/src/wordpress/wp-config-docker.php':
					storage.configFile( state ),
				'wp-content/mu-plugins': path.join( dir, 'mu-plugins' ),
				// Staging files must stay outside Apache's document root.
				'../../../sensei-private': path.join( dir, 'exchange' ),
			},
		},
		tests: {
			...( ! state.wp && state.adoptedFrom
				? { core: state.adoptedFrom.testCore }
				: {} ),
			mappings: {
				'wp-content/uploads': path.join( dir, 'tests-uploads' ),
				'wp-content/plugins': path.join( dir, 'tests-plugins' ),
			},
		},
	};
	return {
		config,
		pluginSlugs: [ 'sensei' ],
	};
}
function composeFile( state ) {
	const runtime = path.join( registry.directory( state.id ), 'runtime' );
	const files = fs.existsSync( runtime )
		? fs
				.readdirSync( runtime )
				.map( ( name ) =>
					path.join( runtime, name, 'docker-compose.yml' )
				)
				.filter( fs.existsSync )
		: [];
	if (
		! files.length &&
		state.adoptedFrom?.compose &&
		fs.existsSync( state.adoptedFrom.compose )
	)
		return state.adoptedFrom.compose;
	if ( files.length !== 1 ) {
		throw new Error(
			'Environment has not started successfully. Run make up first.'
		);
	}
	return files[ 0 ];
}
function streamDatabase( source, target ) {
	const command = ( state, args ) => ( {
		executable: 'docker',
		args: [
			'compose',
			'-p',
			state.project,
			'-f',
			composeFile( state ),
			'exec',
			'-T',
			'cli',
			'wp',
			'--skip-plugins',
			'--skip-themes',
			...args,
		],
		options: { env: childEnvironment( state ) },
	} );
	return stream(
		cloning.sourceCommand( source, [
			'wp',
			'--skip-plugins',
			'--skip-themes',
			'db',
			'export',
			'-',
			'--single-transaction',
			'--skip-lock-tables',
		] ),
		command( target, [ 'db', 'import', '-' ] )
	);
}
function compose( state, args, capture = false ) {
	return run(
		'docker',
		[ 'compose', '-p', state.project, '-f', composeFile( state ), ...args ],
		{ env: childEnvironment( state ), capture }
	);
}
function wp( state, args, capture = true ) {
	return compose(
		state,
		[
			'exec',
			'-T',
			'cli',
			'wp',
			'--skip-plugins',
			'--skip-themes',
			...args,
		],
		capture
	);
}
async function initialize( state, configured, pluginSlugs ) {
	const exchange = path.join( registry.directory( state.id ), 'exchange' );
	state.initialization = 'incomplete';
	save( state );
	try {
		if ( state.cloneSource ) {
			console.log(
				`Copying development data from ${ state.cloneSource.project }.`
			);
			const { metadata } = await cloning.captureSource( state, {
				wp,
				run,
				streamDatabase,
			} );
			await wp( state, [
				'search-replace',
				metadata.sourceUrl,
				`http://localhost:${ state.port }`,
				'--all-tables-with-prefix',
				'--precise',
				'--skip-columns=guid',
			] );
		} else {
			console.log(
				'No development source found (or cloning disabled); starting a fresh site.'
			);
		}
		fs.copyFileSync(
			path.join( __dirname, 'initialize.php' ),
			path.join( exchange, 'initialize.php' )
		);
		registry.writeJSON( path.join( exchange, 'initialize.json' ), {
			plugins: pluginSlugs,
			adminPassword: configured.adminPassword,
		} );
		await wp(
			state,
			[ 'eval-file', '/sensei-private/initialize.php' ],
			false
		);

		await wp( state, [ 'core', 'is-installed' ] );
		state.initialized = true;
		state.initialization = 'complete';
		save( state );
	} finally {
		for ( const file of fs.readdirSync( exchange ) ) {
			fs.rmSync( path.join( exchange, file ), {
				recursive: true,
				force: true,
			} );
		}
	}
}
async function start( args, retry = false ) {
	const options = cloning.parseStartOptions( args );
	const existing = current();
	const state = existing || ( await create( options ) );
	if ( options.name && state.name !== registry.nameSlug( options.name ) ) {
		throw new Error(
			`This environment already uses ${ state.project }. Its name is fixed; recreate it explicitly to choose another name.`
		);
	}
	if ( state.initialized && options.cloneFrom ) {
		console.log( 'Environment already initialized; skipping clone.' );
	}
	await registry.lock( state.id, async () => {
		Object.assign( state, current() );
		if (
			retry &&
			( state.initialized || state.initialization !== 'incomplete' )
		) {
			throw new Error(
				'Only interrupted initialization can be retried.'
			);
		}
		if ( state.initialization === 'incomplete' && ! retry ) {
			throw new Error(
				'Initialization was interrupted. Use make env-retry to retry copying development data, or make destroy for a fresh environment.'
			);
		}
		if (
			existing &&
			! state.initialized &&
			! retry &&
			( options.cloneFrom || options.noClone )
		) {
			state.cloneSource = await cloning.selectSource(
				{ run },
				{
					...options,
					targetProject: state.project,
					commonDir: state.commonDir,
				}
			);
		}
		await storage.prepare( state, { run } );
		primary.preserveUploads( state );
		const configured = settingsFor();

		if ( process.env.WP ) {
			state.wp = process.env.WP;
		}
		if ( process.env.PHP ) {
			state.php = process.env.PHP;
		}
		const { config, pluginSlugs } = configuration( state, configured );
		state.plugins = pluginSlugs;
		registry.writeJSON( registry.configPath( state ), config );
		save( state );
		const running = await run(
			'docker',
			[
				'ps',
				'--filter',
				`label=com.docker.compose.project=${ state.project }`,
				'--format',
				'{{.Label "com.docker.compose.service"}}',
			],
			{ capture: true }
		);
		for ( const [ port, service ] of [
			[ state.port, 'wordpress' ],
			[ state.testsPort, 'tests-wordpress' ],
		] ) {
			if (
				! running.split( '\n' ).includes( service ) &&
				! ( await registry.portAvailable( port ) )
			) {
				throw new Error(
					`Saved port ${ port } is busy. Stop its owner before restarting this environment.`
				);
			}
		}
		await wpEnv( state, [ 'start', ...options.args ] );
		if (
			! fs.existsSync(
				path.join( state.worktree, 'vendor/autoload.php' )
			)
		) {
			await compose( state, [
				'exec',
				'-T',
				'-w',
				'/var/www/html/wp-content/plugins/sensei',
				'cli',
				'composer',
				'install',
			] );
		}
		// Mapped plugins are deliberately activated here, after wp-env installs WordPress.
		if ( ! state.initialized ) {
			await initialize( state, configured, pluginSlugs );
		}
		await compose(
			state,
			[ 'exec', '-T', 'tests-cli', 'wp', 'plugin', 'activate', 'sensei' ],
			true
		);
		console.log(
			`${ state.project }: development http://localhost:${ state.port } | tests http://localhost:${ state.testsPort }`
		);
	} );
}
async function retryInitialization( state ) {
	if ( state.initialized || state.initialization !== 'incomplete' ) {
		throw new Error( 'Only interrupted initialization can be retried.' );
	}
	return start( [], true );
}
async function resources( state, kind ) {
	const listing = await run(
		'docker',
		[
			...( kind === 'container'
				? [ 'ps', '-aq' ]
				: [ kind, 'ls', '-q' ] ),
			'--filter',
			`label=com.docker.compose.project=${ state.project }`,
		],
		{ capture: true }
	);
	return listing ? listing.split( '\n' ) : [];
}
async function destroy( state, orphanOnly = false ) {
	await registry.lock( state.id, async () => {
		const recorded = registry
			.records()
			.find( ( item ) => item.id === state.id );
		if ( ! recorded || ( orphanOnly && ! registry.orphaned( recorded ) ) ) {
			throw new Error(
				'Environment ownership changed; run the cleanup preview again.'
			);
		}
		for ( const kind of [ 'container', 'volume', 'network' ] ) {
			for ( const resource of await resources( state, kind ) ) {
				const [ inspected ] = JSON.parse(
					await run( 'docker', [ kind, 'inspect', resource ], {
						capture: true,
					} )
				);
				const labels =
					kind === 'container'
						? inspected.Config.Labels
						: inspected.Labels;
				if (
					labels?.[ 'com.docker.compose.project' ] !== state.project
				) {
					throw new Error( `Ownership mismatch for ${ resource }` );
				}
				await run(
					'docker',
					[
						kind,
						'rm',
						...( kind === 'container' ? [ '-f' ] : [] ),
						resource,
					],
					{ capture: true }
				);
			}
		}
		fs.rmSync( registry.directory( state.id ), { recursive: true } );
	} );
}
async function list( states = registry.records() ) {
	for ( const state of states ) {
		const running = await resources( state, 'container' );
		const status = await run(
			'docker',
			[
				'ps',
				'-q',
				'--filter',
				`label=com.docker.compose.project=${ state.project }`,
			],
			{ capture: true }
		);
		const size = execFileSync(
			'du',
			[ '-sh', registry.directory( state.id ) ],
			{ encoding: 'utf8' }
		).split( '\t' )[ 0 ];
		console.log(
			`${ state.project } [${ state.id }] ${
				status ? 'running' : 'stopped'
			}${ registry.orphaned( state ) ? ' orphaned' : '' } ${
				state.initialization || 'pending'
			}\n  ${ state.worktree }\n  http://localhost:${
				state.port
			} | tests http://localhost:${
				state.testsPort
			} | host storage ${ size } | containers ${ running.length }`
		);
	}
}
module.exports = {
	run,
	current,
	settingsFor,
	create,
	configuration,
	childEnvironment,
	wpEnv,
	wp,
	compose,
	streamDatabase,
	start,
	retryInitialization,
	destroy,
	list,
	save,
	toolRoot,
};

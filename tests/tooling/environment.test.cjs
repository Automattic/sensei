const { test, beforeEach, afterEach } = require( 'node:test' );
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const net = require( 'node:net' );
const { execFileSync } = require( 'node:child_process' );
const registry = require( '../../scripts/env/registry.cjs' );
const environment = require( '../../scripts/env/environment.cjs' );
const cloning = require( '../../scripts/env/clone.cjs' );
let temporary;
let originalHome;
let originalCI;
beforeEach( () => {
	temporary = fs.realpathSync(
		fs.mkdtempSync( path.join( os.tmpdir(), 'sensei-env-test-' ) )
	);
	originalHome = process.env.SENSEI_ENV_HOME;
	originalCI = process.env.CI;
	delete process.env.CI;
	process.env.SENSEI_ENV_HOME = path.join( temporary, 'private' );
} );
afterEach( () => {
	if ( originalHome === undefined ) {
		delete process.env.SENSEI_ENV_HOME;
	} else {
		process.env.SENSEI_ENV_HOME = originalHome;
	}
	if ( originalCI === undefined ) {
		delete process.env.CI;
	} else {
		process.env.CI = originalCI;
	}
	fs.rmSync( temporary, { recursive: true, force: true } );
} );
function record( worktree, port = 9000 ) {
	if ( ! fs.existsSync( path.join( worktree, '.git' ) ) ) {
		gitRepository( worktree );
	}
	const id = registry.identity( worktree );
	const state = {
		id,
		worktree,
		project: `sensei-dev-${ id }`,
		...registry.gitMetadata( worktree ),
		port,
		testsPort: port + 1,
	};
	registry.writeJSON(
		path.join( registry.directory( id ), 'state.json' ),
		state
	);
	return state;
}

test( 'identity keeps worktrees separate', () => {
	const worktree = path.join( temporary, 'one' );
	gitRepository( worktree );
	gitRepository( path.join( temporary, 'two' ) );

	const ids = [
		registry.identity( worktree ),
		registry.identity( path.join( temporary, 'two' ) ),
	];

	assert.equal( new Set( ids ).size, 2 );
	assert.equal( ids[ 0 ], registry.identity( worktree ) );
} );

test( 'allocatePorts skips reserved and occupied ports', async () => {
	record( path.join( temporary, 'one' ) );
	const server = net.createServer();
	const blocked = ( await registry.allocatePorts() ).port;
	await new Promise( ( resolve ) => server.listen( blocked, '::', resolve ) );

	try {
		const result = await registry.allocatePorts();

		assert.ok( result.port > blocked );
		assert.ok( result.testsPort > result.port );
	} finally {
		await new Promise( ( resolve ) => server.close( resolve ) );
	}
} );

test( 'lock serializes concurrent port reservations and releases after failure', async () => {
	const reserve = ( worktree ) =>
		registry.lock( 'ports', async () => {
			const ports = await registry.allocatePorts();
			return record( worktree, ports.port );
		} );

	const states = await Promise.all( [
		reserve( path.join( temporary, 'one' ) ),
		reserve( path.join( temporary, 'two' ) ),
	] );

	assert.equal(
		new Set(
			states.flatMap( ( state ) => [ state.port, state.testsPort ] )
		).size,
		4
	);
	await assert.rejects(
		registry.lock( 'ports', () => {
			throw new Error( 'interrupted' );
		} )
	);
	await registry.lock( 'ports', async () => {} );
} );

test( 'records refuses altered ownership before cleanup can select resources', () => {
	const state = record( path.join( temporary, 'one' ) );
	registry.writeJSON(
		path.join( registry.directory( state.id ), 'state.json' ),
		{ ...state, project: 'sensei-lms' }
	);

	assert.throws( () => registry.records(), /Invalid ownership/ );
} );

test( 'orphaned preserves missing registered worktrees and detects retired worktrees', () => {
	const repo = path.join( temporary, 'repo' );
	fs.mkdirSync( repo );
	execFileSync( 'git', [ 'init', repo ], { stdio: 'ignore' } );
	execFileSync(
		'git',
		[
			'-C',
			repo,
			'-c',
			'user.name=Test',
			'-c',
			'user.email=test@example.test',
			'commit',
			'--allow-empty',
			'-m',
			'initial',
		],
		{ stdio: 'ignore' }
	);
	const worktree = path.join( temporary, 'worktree' );
	execFileSync(
		'git',
		[ '-C', repo, 'worktree', 'add', '--detach', worktree ],
		{ stdio: 'ignore' }
	);
	const state = { worktree, commonDir: path.join( repo, '.git' ) };
	fs.rmSync( worktree, { recursive: true } );

	assert.equal( registry.orphaned( state ), false );
	execFileSync( 'git', [ '-C', repo, 'worktree', 'prune', '--expire=now' ] );
	assert.equal( registry.orphaned( state ), true );
	assert.equal(
		registry.orphaned( { ...state, commonDir: '/missing/repository' } ),
		false
	);
} );

test( 'configuration isolates uploads and private imports from the test database', () => {
	const worktree = path.join( temporary, 'checkout' );
	fs.mkdirSync( worktree );
	registry.writeJSON( path.join( worktree, '.wp-env.json' ), {
		plugins: [ '.' ],
		themes: [ 'Automattic/themes/course#trunk' ],
	} );
	const normal = record( worktree );
	const other = record( path.join( temporary, 'other-checkout' ), 9002 );
	fs.copyFileSync(
		path.join( worktree, '.wp-env.json' ),
		path.join( other.worktree, '.wp-env.json' )
	);

	const first = environment.configuration( normal, {} ).config;
	const second = environment.configuration( other, {} ).config;

	assert.deepEqual( first.plugins, [] );
	assert.equal( first.mappings[ 'wp-content/plugins/sensei' ], worktree );
	assert.notEqual(
		first.env.development.mappings[ 'wp-content/uploads' ],
		second.env.development.mappings[ 'wp-content/uploads' ]
	);
	assert.notEqual(
		first.env.development.mappings[ 'wp-content/uploads' ],
		first.env.tests.mappings[ 'wp-content/uploads' ]
	);
	assert.equal(
		first.env.tests.mappings[ '../../../sensei-private' ],
		undefined
	);
} );

test( 'childEnvironment replaces inherited Docker and wp-env targets', () => {
	const previous = {
		COMPOSE_PROJECT_NAME: process.env.COMPOSE_PROJECT_NAME,
		WP_ENV_PORT: process.env.WP_ENV_PORT,
	};
	process.env.COMPOSE_PROJECT_NAME = 'sensei-lms';
	process.env.WP_ENV_PORT = '8888';
	try {
		const state = record( path.join( temporary, 'one' ) );

		const result = environment.childEnvironment( state );

		assert.equal( result.COMPOSE_PROJECT_NAME, state.project );
		assert.equal( result.WP_ENV_PORT, undefined );
		assert.equal(
			result.WP_ENV_HOME,
			path.join( registry.directory( state.id ), 'runtime' )
		);
	} finally {
		for ( const [ key, value ] of Object.entries( previous ) ) {
			if ( value === undefined ) {
				delete process.env[ key ];
			} else {
				process.env[ key ] = value;
			}
		}
	}
} );

for ( const mismatch of [ false, true ] ) {
	test( `destroy ${
		mismatch
			? 'refuses mismatched resource ownership'
			: 'removes only verified resources and preserves unrelated private files'
	}`, async () => {
		const worktree = path.join( temporary, 'checkout' );
		const state = record( worktree );
		const privateFiles = path.join( registry.home(), 'private-files' );
		fs.mkdirSync( privateFiles, { recursive: true } );
		fs.writeFileSync(
			path.join( privateFiles, 'keep.txt' ),
			'unrelated private file'
		);
		const bin = path.join( temporary, 'bin' );
		fs.mkdirSync( bin );
		const log = path.join( temporary, 'docker.jsonl' );
		const script = `#!${ process.execPath }
const fs = require('node:fs');
const args = process.argv.slice(2);
fs.appendFileSync(${ JSON.stringify( log ) }, JSON.stringify(args)+'\\n');
if (args.includes('inspect')) {
 const labels = {'com.docker.compose.project': ${ JSON.stringify(
		mismatch ? 'sensei-lms' : state.project
 ) }};
 console.log(JSON.stringify([{Config:{Labels:labels},Labels:labels}]));
} else if (args.includes('-q') || args.includes('-aq')) {
 console.log(args[0] === 'ps' ? 'owned-container' : 'owned-'+args[0]);
}
`;
		fs.writeFileSync( path.join( bin, 'docker' ), script, { mode: 0o700 } );
		const previousPath = process.env.PATH;
		process.env.PATH = `${ bin }${ path.delimiter }${ previousPath }`;
		try {
			if ( mismatch ) {
				await assert.rejects(
					environment.destroy( state ),
					/Ownership mismatch/
				);
				assert.equal( registry.records().length, 1 );
			} else {
				await environment.destroy( state );
				assert.equal( registry.records().length, 0 );
			}
			const calls = fs
				.readFileSync( log, 'utf8' )
				.trim()
				.split( '\n' )
				.map( JSON.parse );
			const removals = calls.filter( ( args ) => args.includes( 'rm' ) );
			assert.equal( removals.length, mismatch ? 0 : 3 );
			assert.equal(
				calls.some(
					( args ) =>
						args.includes( 'prune' ) || args.includes( 'image' )
				),
				false
			);
			assert.equal(
				fs.readFileSync(
					path.join( privateFiles, 'keep.txt' ),
					'utf8'
				),
				'unrelated private file'
			);
		} finally {
			process.env.PATH = previousPath;
		}
	} );
}

test( 'configuration selects requested WordPress and PHP versions without changing the repository', () => {
	const worktree = path.join( temporary, 'checkout' );
	fs.mkdirSync( worktree );
	registry.writeJSON( path.join( worktree, '.wp-env.json' ), {
		core: null,
		plugins: [ '.' ],
	} );
	const state = record( worktree );
	const cases = [
		[ 'latest', null ],
		[ '6.8', 'WordPress/WordPress#6.8-branch' ],
		[ '6.8.3', 'https://wordpress.org/wordpress-6.8.3.zip' ],
		[ '7.0-beta1', 'https://wordpress.org/wordpress-7.0-beta1.zip' ],
		[
			'nightly',
			'https://wordpress.org/nightly-builds/wordpress-latest.zip',
		],
	];

	for ( const [ version, expected ] of cases ) {
		const { config } = environment.configuration(
			{ ...state, wp: version, php: '8.3' },
			{ plugins: [] }
		);
		assert.equal( config.core, expected );
		assert.equal( config.phpVersion, '8.3' );
	}
	assert.equal(
		registry.readJSON( path.join( worktree, '.wp-env.json' ) ).core,
		null
	);
} );

test( 'identity keeps separate private registries from sharing Docker data', () => {
	const worktree = path.join( temporary, 'checkout' );
	gitRepository( worktree );
	const first = registry.identity( worktree );

	process.env.SENSEI_ENV_HOME = path.join( temporary, 'another-registry' );
	const second = registry.identity( worktree );

	assert.notEqual( first, second );
} );

function gitRepository( root ) {
	fs.mkdirSync( root, { recursive: true } );
	execFileSync( 'git', [ 'init', root ], { stdio: 'ignore' } );
	execFileSync(
		'git',
		[
			'-C',
			root,
			'-c',
			'user.name=Test',
			'-c',
			'user.email=test@example.test',
			'commit',
			'--allow-empty',
			'-m',
			'initial',
		],
		{ stdio: 'ignore' }
	);
	return path.join( root, '.git' );
}

for ( const readable of [ false, true ] ) {
	test( `identity and stored data survive a Git worktree move (${
		readable ? 'readable' : 'legacy'
	} name) and cleanup detects its later removal`, () => {
		const main = path.join( temporary, 'main' );
		gitRepository( main );
		const checkout = path.join( temporary, 'original' );
		const moved = path.join( temporary, 'renamed' );
		execFileSync(
			'git',
			[ '-C', main, 'worktree', 'add', '--detach', checkout ],
			{ stdio: 'ignore' }
		);
		const state = record( checkout );
		if ( readable ) {
			state.name = 'fix-module-order';
			state.project = registry.projectName( state.id, state.name );
		}
		state.initialized = true;
		environment.save( state );
		const data = path.join( registry.directory( state.id ), 'saved-data' );
		fs.writeFileSync( data, 'keep me' );
		fs.cpSync(
			path.join( environment.toolRoot, 'scripts/env' ),
			path.join( checkout, 'scripts/env' ),
			{ recursive: true }
		);
		registry.writeJSON( path.join( checkout, '.wp-env.json' ), {
			plugins: [ '.' ],
		} );
		if ( readable ) {
			execFileSync(
				'git',
				[ '-C', checkout, 'switch', '-c', 'another-branch' ],
				{ stdio: 'ignore' }
			);
		}
		execFileSync( 'git', [
			'-C',
			main,
			'worktree',
			'move',
			checkout,
			moved,
		] );

		assert.equal( registry.identity( moved ), state.id );
		const movedEnvironment = require(
			path.join( moved, 'scripts/env/environment.cjs' )
		);
		const restored = movedEnvironment.current();
		assert.equal( restored.id, state.id );
		assert.equal( restored.name, state.name );
		assert.equal( restored.project, state.project );
		assert.equal(
			registry.configPath( restored ),
			registry.configPath( state )
		);
		assert.equal(
			movedEnvironment.configuration( restored, { plugins: [] } ).config
				.mappings[ 'wp-content/plugins/sensei' ],
			moved
		);
		assert.equal( restored.worktree, moved );
		assert.equal( restored.port, state.port );
		assert.equal( restored.initialized, true );
		assert.equal( fs.readFileSync( data, 'utf8' ), 'keep me' );
		assert.equal( registry.orphaned( restored ), false );
		fs.rmSync( moved, { recursive: true } );
		assert.equal( registry.orphaned( restored ), false );
		execFileSync( 'git', [
			'-C',
			main,
			'worktree',
			'prune',
			'--expire=now',
		] );
		assert.equal( registry.orphaned( restored ), true );
	} );
}

const { stream } = require( '../../scripts/env/stream.cjs' );
const nodeCommand = ( code ) => ( {
	executable: process.execPath,
	args: [ '-e', code ],
} );
test( 'stream transfers a database larger than its buffers without creating a dump', async () => {
	const output = path.join( temporary, 'imported' );
	const source = nodeCommand( 'process.stdout.write("sql".repeat(1000000))' );
	const target = nodeCommand(
		`const fs = require('fs'); process.stdin.pipe(fs.createWriteStream(${ JSON.stringify(
			output
		) }));`
	);

	await stream( source, target );

	assert.equal( fs.readFileSync( output, 'utf8' ), 'sql'.repeat( 1000000 ) );
} );

for ( const [ label, source, target, expected ] of [
	[
		'export failure',
		'process.stdout.write("partial");process.exitCode=17',
		'process.stdin.resume()',
		/Database export failed \(17\)/,
	],
	[
		'import failure',
		'process.stdout.write("sql")',
		'process.stdin.resume();process.stdin.on("end",()=>process.exit(19))',
		/Database import failed \(19\)/,
	],
	[
		'early import closure',
		'setInterval(()=>process.stdout.write("sql".repeat(10000)),5)',
		'process.exit(0)',
		/closed before|EPIPE/,
	],
	[
		'failed import with a running exporter',
		'setInterval(()=>process.stdout.write("sql".repeat(10000)),5)',
		'process.exit(19)',
		/Database import failed|EPIPE/,
	],
] ) {
	test(
		`stream rejects ${ label } and stops both processes`,
		{ timeout: 5000 },
		async () => {
			await assert.rejects(
				stream( nodeCommand( source ), nodeCommand( target ) ),
				expected
			);
		}
	);
}

test(
	'stream rejects an unavailable exporter and stops its waiting importer',
	{ timeout: 5000 },
	async () => {
		await assert.rejects(
			stream(
				{ executable: path.join( temporary, 'missing' ), args: [] },
				nodeCommand( 'process.stdin.resume()' )
			),
			/Database export failed/
		);
	}
);

function sourceCommands( {
	running = true,
	present = true,
	labels = {},
	config = {},
	url = 'http://localhost:8888',
} = {} ) {
	const calls = [];
	return {
		calls,
		run: async ( executable, args ) => {
			calls.push( [ executable, ...args ] );
			if ( args[ 0 ] === 'ps' ) return present ? 'source-container' : '';
			if ( args[ 0 ] === 'inspect' )
				return JSON.stringify( [
					{
						Id: 'source-container',
						State: { Running: running },
						Config: {
							Labels: {
								'com.docker.compose.project': 'sensei-lms',
								'com.docker.compose.service': 'cli',
								...labels,
							},
						},
					},
				] );
			if ( args.includes( 'config' ) )
				return JSON.stringify(
					Object.entries( {
						table_prefix: 'custom_',
						...config,
					} ).map( ( [ name, value ] ) => ( { name, value } ) )
				);
			if ( args.includes( 'query' ) ) return url;
			if ( args.includes( 'sh' ) ) return 'yes';
			if ( args[ 0 ] === 'cp' ) return '';
			throw new Error( 'Unexpected source command.' );
		},
	};
}

test( 'selectSource uses sensei-lms labels and ignores checkout paths', async () => {
	const commands = sourceCommands();

	const source = await cloning.selectSource( commands );

	assert.deepEqual( source, {
		container: 'source-container',
		project: 'sensei-lms',
	} );
	assert.ok(
		commands.calls[ 0 ].includes(
			'label=com.docker.compose.project=sensei-lms'
		)
	);
} );

test( 'selectSource starts fresh only when absent or explicitly disabled', async () => {
	assert.equal(
		await cloning.selectSource( sourceCommands( { present: false } ) ),
		null
	);
	const previous = process.env.NO_CLONE;
	try {
		process.env.CI = 'true';
		assert.equal( await cloning.selectSource( {} ), null );
		delete process.env.CI;
		process.env.NO_CLONE = '1';
		assert.equal( await cloning.selectSource( {} ), null );
	} finally {
		if ( previous === undefined ) delete process.env.NO_CLONE;
		else process.env.NO_CLONE = previous;
	}
} );

for ( const [ scenario, options, error ] of [
	[ 'stopped source', { running: false }, /source is stopped/ ],
	[
		'missing CLI service',
		{ labels: { 'com.docker.compose.service': 'wordpress' } },
		/unique sensei-lms CLI/,
	],
	[
		'mismatched ownership',
		{ labels: { 'com.docker.compose.project': 'another-project' } },
		/ownership/,
	],
] ) {
	test( `selectSource rejects ${ scenario } without starting or changing it`, async () => {
		const commands = sourceCommands( options );

		await assert.rejects( cloning.selectSource( commands ), error );

		assert.deepEqual(
			commands.calls.map( ( call ) => call[ 1 ] ),
			[ 'ps', 'inspect' ]
		);
	} );
}

test( 'captureSource reads without bootstrapping WordPress and streams into the target only', async () => {
	const target = record( path.join( temporary, 'target' ) );
	const commands = sourceCommands();
	const targetCalls = [];
	commands.wp = async ( state, args ) => {
		assert.equal( state.id, target.id );
		targetCalls.push( args );
	};
	commands.streamDatabase = async ( source, state ) => {
		assert.equal( source.container, 'source-container' );
		assert.equal( state.id, target.id );
	};

	const result = await cloning.captureSource( target, commands );

	assert.equal( result.metadata.sourceUrl, 'http://localhost:8888' );
	assert.equal( result.metadata.tablePrefix, 'custom_' );
	assert.equal(
		registry.readJSON(
			path.join( registry.directory( target.id ), 'state.json' )
		).tablePrefix,
		'custom_'
	);
	assert.deepEqual( targetCalls, [
		[ 'db', 'reset', '--yes' ],
		[ 'config', 'set', 'table_prefix', 'custom_', '--type=variable' ],
	] );
	const sourceWP = commands.calls.filter( ( call ) => call.includes( 'wp' ) );
	assert.equal( sourceWP.length, 2 );
	assert.ok( sourceWP[ 0 ].includes( 'config' ) );
	assert.ok(
		sourceWP[ 1 ].some( ( arg ) => arg.startsWith( 'SELECT option_value' ) )
	);
	assert.ok(
		commands.calls.some(
			( call ) =>
				call[ 1 ] === 'cp' &&
				call.includes(
					'source-container:/var/www/html/wp-content/uploads/.'
				)
		)
	);
	assert.equal(
		fs.existsSync(
			path.join(
				registry.directory( target.id ),
				'exchange/clone/database.sql'
			)
		),
		false
	);
} );

for ( const [ options, error ] of [
	[ { config: { MULTISITE: true } }, /single-site/ ],
	[ { config: { table_prefix: 'unsafe-prefix;' } }, /valid table prefix/ ],
	[ { url: 'invalid-url' }, /valid site URL/ ],
] ) {
	test( `captureSource rejects invalid source metadata: ${ error.source }`, async () => {
		const commands = sourceCommands( options );
		commands.wp = async () =>
			assert.fail(
				'Target must stay unchanged when source metadata is invalid.'
			);

		await assert.rejects( cloning.captureSource( {}, commands ), error );
	} );
}

for ( const args of [
	[ '--clone-from', 'sensei-lms', '--update' ],
	[ '--clone-from=sensei-lms', '--update' ],
] ) {
	test( `parseStartOptions accepts ${ args[ 0 ] } and keeps wp-env arguments separate`, () => {
		const options = cloning.parseStartOptions( args );

		assert.equal( options.cloneFrom, 'sensei-lms' );
		assert.deepEqual( options.args, [ '--update' ] );
	} );
}

test( 'parseStartOptions rejects missing source arguments', () => {
	for ( const args of [
		[ '--clone-from' ],
		[ '--clone-from=' ],
		[ '--clone-from', '--update' ],
	] ) {
		assert.throws(
			() => cloning.parseStartOptions( args ),
			/requires an environment ID/
		);
	}
} );

test( 'selectSource explicitly clones a registered environment by ID or project', async () => {
	const state = record( path.join( temporary, 'wpml-baseline' ) );
	state.initialized = true;
	environment.save( state );

	for ( const name of [ state.id, state.project ] ) {
		const commands = sourceCommands( {
			labels: { 'com.docker.compose.project': state.project },
		} );
		const source = await cloning.selectSource( commands, {
			cloneFrom: name,
			noClone: true,
		} );

		assert.equal( source.project, state.project );
		assert.ok(
			commands.calls[ 0 ].includes(
				`label=com.docker.compose.project=${ state.project }`
			)
		);
	}
} );

for ( const [ scenario, settings, message ] of [
	[ 'missing', { present: false }, /not running/ ],
	[ 'stopped', { running: false }, /source is stopped/ ],
] ) {
	test( `selectSource rejects an explicitly selected ${ scenario } environment`, async () => {
		const state = record( path.join( temporary, 'baseline' ) );
		state.initialized = true;
		environment.save( state );
		const commands = sourceCommands( {
			...settings,
			labels: { 'com.docker.compose.project': state.project },
		} );

		await assert.rejects(
			cloning.selectSource( commands, { cloneFrom: state.id } ),
			message
		);
	} );
}

test( 'selectSource rejects unknown, incomplete, and self-cloning sources before Docker operations', async () => {
	const state = record( path.join( temporary, 'baseline' ) );

	await assert.rejects(
		cloning.selectSource( {}, { cloneFrom: 'unknown' } ),
		/Unknown cloning source/
	);
	await assert.rejects(
		cloning.selectSource( {}, { cloneFrom: state.id } ),
		/not initialized/
	);
	state.initialized = true;
	environment.save( state );
	await assert.rejects(
		cloning.selectSource(
			{},
			{ cloneFrom: state.id, targetProject: state.project }
		),
		/cannot clone itself/
	);
} );

test( 'captureSource retries the saved project and resolves a new container ID', async () => {
	const source = record( path.join( temporary, 'baseline' ) );
	source.initialized = true;
	environment.save( source );
	const target = record( path.join( temporary, 'issue' ), 9002 );
	target.cloneSource = {
		project: source.project,
		container: 'old-container-id',
	};
	const commands = sourceCommands( {
		labels: { 'com.docker.compose.project': source.project },
	} );
	commands.wp = async () => {};
	commands.streamDatabase = async ( selected ) => {
		assert.equal( selected.project, source.project );
		assert.equal( selected.container, 'source-container' );
	};

	await cloning.captureSource( target, commands );

	assert.ok(
		commands.calls[ 0 ].includes(
			`label=com.docker.compose.project=${ source.project }`
		)
	);
} );

test( 'start preserves initialized data when clone-from names an unavailable source', async () => {
	const checkout = path.join( temporary, 'checkout' );
	const state = record( checkout );
	state.initialized = true;
	state.storagePrepared = true;
	state.cloneSource = { project: 'sensei-lms', container: 'original-source' };
	environment.save( state );
	fs.cpSync(
		path.join( environment.toolRoot, 'scripts/env' ),
		path.join( checkout, 'scripts/env' ),
		{ recursive: true }
	);
	registry.writeJSON( path.join( checkout, '.wp-env.json' ), {
		plugins: [ '.' ],
	} );
	const wpEnv = path.join(
		checkout,
		'node_modules/@wordpress/env/bin/wp-env'
	);
	fs.mkdirSync( path.dirname( wpEnv ), { recursive: true } );
	fs.writeFileSync(
		wpEnv,
		`if (process.argv.some(arg=>arg.startsWith('--clone-from')||arg==='--no-clone')) process.exit(19);`
	);
	fs.mkdirSync( path.join( checkout, 'vendor' ) );
	fs.writeFileSync( path.join( checkout, 'vendor/autoload.php' ), '<?php' );
	registry.writeJSON(
		path.join(
			registry.directory( state.id ),
			'runtime/check/docker-compose.yml'
		),
		{}
	);
	const bin = path.join( temporary, 'bin' );
	fs.mkdirSync( bin );
	fs.writeFileSync(
		path.join( bin, 'docker' ),
		`#!${ process.execPath }
const args=process.argv.slice(2);
if (args.includes('db') || args[0]==='inspect' || args.includes('label=com.docker.compose.project=sensei-lms')) process.exit(20);
if (args[0]==='ps') console.log('wordpress\\ntests-wordpress');
`,
		{ mode: 0o700 }
	);
	const previous = process.env.PATH;
	process.env.PATH = `${ bin }${ path.delimiter }${ previous }`;
	try {
		const local = require(
			path.join( checkout, 'scripts/env/environment.cjs' )
		);

		await local.start( [ '--clone-from', 'unavailable', '--update' ] );

		assert.equal( local.current().initialized, true );
		assert.deepEqual( local.current().cloneSource, state.cloneSource );
	} finally {
		process.env.PATH = previous;
	}
} );

function primaryAndLinkedEnvironment() {
	const primary = record( path.join( temporary, 'primary' ) );
	primary.initialized = true;
	environment.save( primary );
	const checkout = path.join( temporary, 'linked' );
	execFileSync(
		'git',
		[ '-C', primary.worktree, 'worktree', 'add', '--detach', checkout ],
		{ stdio: 'ignore' }
	);
	return { primary, linked: record( checkout, 9002 ) };
}
function withoutLegacySource( commands ) {
	const run = commands.run;
	commands.run = ( executable, args, options ) =>
		args.includes( 'label=com.docker.compose.project=sensei-lms' )
			? Promise.resolve( '' )
			: run( executable, args, options );
	return commands;
}

test( 'selectSource automatically clones the primary checkout for a new contributor', async () => {
	const { primary, linked } = primaryAndLinkedEnvironment();
	const commands = withoutLegacySource(
		sourceCommands( {
			labels: { 'com.docker.compose.project': primary.project },
		} )
	);

	const source = await cloning.selectSource( commands, {
		commonDir: linked.commonDir,
		targetProject: linked.project,
	} );

	assert.equal( source?.project, primary.project );
} );

test( 'selectSource preserves the legacy source priority when a managed primary also exists', async () => {
	const { linked } = primaryAndLinkedEnvironment();

	const source = await cloning.selectSource( sourceCommands(), {
		commonDir: linked.commonDir,
		targetProject: linked.project,
	} );

	assert.equal( source.project, 'sensei-lms' );
} );

test( 'selectSource never automatically clones its own primary or a different repository', async () => {
	const { primary, linked } = primaryAndLinkedEnvironment();
	const unrelated = record(
		path.join( temporary, 'unrelated-repository' ),
		9004
	);
	const commands = sourceCommands( { present: false } );

	assert.equal(
		await cloning.selectSource( commands, {
			commonDir: primary.commonDir,
			targetProject: primary.project,
		} ),
		null
	);
	assert.equal(
		await cloning.selectSource( commands, {
			commonDir: unrelated.commonDir,
			targetProject: unrelated.project,
		} ),
		null
	);
	assert.notEqual( linked.commonDir, unrelated.commonDir );
} );

for ( const [ scenario, initialized, running, expected ] of [
	[ 'incomplete', false, true, /not initialized/ ],
	[ 'stopped', true, false, /source is stopped/ ],
] ) {
	test( `selectSource reports an ${ scenario } primary instead of starting a fresh linked environment`, async () => {
		const { primary, linked } = primaryAndLinkedEnvironment();
		primary.initialized = initialized;
		environment.save( primary );
		const commands = withoutLegacySource(
			sourceCommands( {
				running,
				labels: { 'com.docker.compose.project': primary.project },
			} )
		);

		await assert.rejects(
			cloning.selectSource( commands, {
				commonDir: linked.commonDir,
				targetProject: linked.project,
			} ),
			expected
		);
	} );
}

test( 'create saves the automatically selected primary source without configuration', async () => {
	const { primary, linked } = primaryAndLinkedEnvironment();
	fs.rmSync( registry.directory( linked.id ), { recursive: true } );
	fs.cpSync(
		path.join( environment.toolRoot, 'scripts/env' ),
		path.join( linked.worktree, 'scripts/env' ),
		{ recursive: true }
	);
	const sourceFile = path.join(
		registry.directory( primary.id ),
		'state.json'
	);
	const original = fs.readFileSync( sourceFile, 'utf8' );
	const bin = path.join( temporary, 'bin' );
	fs.mkdirSync( bin );
	fs.writeFileSync(
		path.join( bin, 'docker' ),
		`#!${ process.execPath }
const args=process.argv.slice(2);
if(args[0]==='ps') {
 if(args.includes('label=com.docker.compose.project=${ primary.project }')) console.log('primary-cli');
} else if(args[0]==='inspect') console.log(JSON.stringify([{Id:'primary-cli',State:{Running:true},Config:{Labels:{'com.docker.compose.project':'${ primary.project }','com.docker.compose.service':'cli'}}}]));
else process.exit(21);
`,
		{ mode: 0o700 }
	);
	const previous = process.env.PATH;
	process.env.PATH = `${ bin }${ path.delimiter }${ previous }`;
	try {
		const local = require(
			path.join( linked.worktree, 'scripts/env/environment.cjs' )
		);

		const created = await local.create();

		assert.equal( created.cloneSource.project, primary.project );
		assert.deepEqual( local.current().cloneSource, created.cloneSource );
		assert.equal( created.initialized, false );
		assert.equal( fs.readFileSync( sourceFile, 'utf8' ), original );
	} finally {
		process.env.PATH = previous;
	}
} );

test( 'configuration preserves the database prefix and installed plugins when core and PHP versions change', () => {
	const checkout = path.join( temporary, 'checkout' );
	const state = record( checkout );
	registry.writeJSON( path.join( checkout, '.wp-env.json' ), {
		plugins: [ '.' ],
	} );
	state.tablePrefix = 'custom_';
	state.wp = '6.8.3';
	const first = environment.configuration( state, {} ).config;
	const development = first.env.development.mappings;
	const tests = first.env.tests.mappings;
	assert.ok(
		development[ 'wp-content/plugins' ],
		'Installed plugins need storage outside the core directory.'
	);
	assert.ok(
		development[ '../../../usr/src/wordpress/wp-config-docker.php' ],
		'Database configuration needs persistent storage.'
	);
	fs.writeFileSync(
		path.join( development[ 'wp-content/plugins' ], 'example.php' ),
		'<?php /* Plugin Name: Example */'
	);
	fs.writeFileSync(
		path.join( tests[ 'wp-content/plugins' ], 'test-only.php' ),
		'<?php /* Plugin Name: Test only */'
	);

	state.wp = '6.8.4';
	state.php = '8.3';
	const updated = environment.configuration( state, {} ).config;

	assert.match(
		fs.readFileSync(
			updated.env.development.mappings[
				'../../../usr/src/wordpress/wp-config-docker.php'
			],
			'utf8'
		),
		/\$table_prefix = 'custom_';/
	);
	assert.equal(
		fs.readFileSync(
			path.join(
				updated.env.development.mappings[ 'wp-content/plugins' ],
				'example.php'
			),
			'utf8'
		),
		'<?php /* Plugin Name: Example */'
	);
	assert.equal(
		fs.existsSync(
			path.join(
				updated.env.development.mappings[ 'wp-content/plugins' ],
				'test-only.php'
			)
		),
		false
	);
	assert.notEqual(
		development[ 'wp-content/plugins' ],
		tests[ 'wp-content/plugins' ]
	);
	assert.equal( updated.mappings[ 'wp-content/plugins/sensei' ], checkout );
} );

test( 'storage migration refuses another project before copying files', async () => {
	const state = record( path.join( temporary, 'checkout' ) );
	fs.mkdirSync( path.join( registry.directory( state.id ), 'runtime' ) );
	const calls = [];
	const commands = {
		run: async ( executable, args ) => {
			calls.push( args );
			if ( args[ 0 ] === 'ps' ) return 'foreign-container';
			if ( args[ 0 ] === 'inspect' )
				return JSON.stringify( [
					{
						Config: {
							Labels: {
								'com.docker.compose.project': 'sensei-lms',
							},
						},
					},
				] );
			assert.fail( "Migration must not copy another project's files." );
		},
	};
	const storage = require( '../../scripts/env/storage.cjs' );

	await assert.rejects(
		storage.prepare( state, commands ),
		/ownership mismatch/
	);

	assert.equal( state.storagePrepared, undefined );
	assert.equal( calls.length, 2 );
} );

test( 'configuration preserves the default Docker prefix when migrating an existing config', () => {
	const state = record( path.join( temporary, 'checkout' ) );
	registry.writeJSON( path.join( state.worktree, '.wp-env.json' ), {
		plugins: [ '.' ],
	} );
	const file = path.join( registry.directory( state.id ), 'wp-config.php' );
	fs.writeFileSync(
		file,
		"<?php\n$table_prefix = getenv_docker('WORDPRESS_TABLE_PREFIX', 'wp_');\n"
	);

	environment.configuration( state, {} );

	assert.equal( state.tablePrefix, 'wp_' );
	assert.match( fs.readFileSync( file, 'utf8' ), /\$table_prefix = 'wp_';/ );
} );

for ( const [ definition, value, expected ] of [
	[ "$table_prefix = 'custom_';", 'wp_', 'custom_' ],
	[
		"$table_prefix = getenv_docker('WORDPRESS_TABLE_PREFIX', 'wp_');",
		'configured_',
		'configured_',
	],
] ) {
	test( `storage migration retains the effective ${ expected } prefix`, async () => {
		const state = record( path.join( temporary, 'checkout' ) );
		registry.writeJSON( path.join( state.worktree, '.wp-env.json' ), {
			plugins: [ '.' ],
		} );
		fs.mkdirSync( path.join( registry.directory( state.id ), 'runtime' ) );
		const commands = {
			run: async ( executable, args ) => {
				if ( args[ 0 ] === 'ps' ) return 'old-cli';
				if ( args[ 0 ] === 'inspect' )
					return JSON.stringify( [
						{
							Id: 'old-cli',
							Config: {
								User: '0:0',
								Image: 'fixture',
								Env: [ `WORDPRESS_TABLE_PREFIX=${ value }` ],
								Labels: {
									'com.docker.compose.project': state.project,
									'com.docker.compose.service': 'cli',
								},
							},
						},
					] );
				if ( args[ 0 ] === 'cp' )
					fs.writeFileSync( args[ 2 ], `<?php\n${ definition }\n` );
				return '';
			},
		};
		const storage = require( '../../scripts/env/storage.cjs' );

		await storage.prepare( state, commands );
		environment.configuration( state, {} );

		assert.equal( state.tablePrefix, expected );
		assert.ok(
			fs
				.readFileSync(
					path.join(
						registry.directory( state.id ),
						'wp-config.php'
					),
					'utf8'
				)
				.includes( `$table_prefix = '${ expected }';` )
		);
	} );
}

test( 'create gives Docker projects and wp-env builds readable names without changing identity', async () => {
	const { linked } = primaryAndLinkedEnvironment();
	fs.rmSync( registry.directory( linked.id ), { recursive: true } );
	fs.cpSync(
		path.join( environment.toolRoot, 'scripts/env' ),
		path.join( linked.worktree, 'scripts/env' ),
		{ recursive: true }
	);
	fs.copyFileSync(
		path.join( environment.toolRoot, '.wp-env.json' ),
		path.join( linked.worktree, '.wp-env.json' )
	);
	const local = require(
		path.join( linked.worktree, 'scripts/env/environment.cjs' )
	);
	const state = await local.create( {
		name: 'Fix / Module Order',
		noClone: true,
	} );
	assert.equal( state.id, registry.identity( linked.worktree ) );
	assert.equal( state.name, 'fix-module-order' );
	assert.equal( state.project, `sensei-dev-fix-module-order-${ state.id }` );
	assert.equal( local.current().project, state.project );
	const generated = local.configuration( state, {} ).config;
	const config = registry.configPath( state );
	registry.writeJSON( config, generated );
	const previous = process.env.WP_ENV_HOME;
	process.env.WP_ENV_HOME = environment.childEnvironment( state ).WP_ENV_HOME;
	try {
		const load = require(
			path.join(
				environment.toolRoot,
				'node_modules/@wordpress/env/lib/config/load-config'
			)
		);

		const resolved = await load( linked.worktree, config );

		assert.match(
			path.basename( resolved.workDirectoryPath ),
			/^wp-env-fix-module-order-[a-f0-9]{8}$/
		);
	} finally {
		if ( previous === undefined ) delete process.env.WP_ENV_HOME;
		else process.env.WP_ENV_HOME = previous;
	}
	await assert.rejects(
		local.start( [ '--name', 'another-name' ] ),
		/name is fixed/
	);
} );

test( 'environmentName derives readable defaults for primary, branch, and detached worktrees', () => {
	const primary = path.join( temporary, 'primary' );
	gitRepository( primary );
	const checkout = path.join( temporary, 'module-order' );
	execFileSync(
		'git',
		[
			'-C',
			primary,
			'worktree',
			'add',
			'-b',
			'fix/module-order',
			checkout,
		],
		{ stdio: 'ignore' }
	);

	assert.equal(
		registry.environmentName( primary, registry.gitMetadata( primary ) ),
		'primary'
	);
	assert.equal(
		registry.environmentName( checkout, registry.gitMetadata( checkout ) ),
		'fix-module-order'
	);
	execFileSync( 'git', [ '-C', checkout, 'checkout', '--detach' ], {
		stdio: 'ignore',
	} );
	assert.equal(
		registry.environmentName( checkout, registry.gitMetadata( checkout ) ),
		'module-order'
	);
	const automatic = path.join( temporary, '1740/sensei' );
	execFileSync(
		'git',
		[ '-C', primary, 'worktree', 'add', '--detach', automatic ],
		{ stdio: 'ignore' }
	);
	assert.equal(
		registry.environmentName(
			automatic,
			registry.gitMetadata( automatic )
		),
		'worktree'
	);
} );

test( 'readable project names remain unique across checkouts and reject forged ownership', () => {
	const states = [
		record( path.join( temporary, 'one' ) ),
		record( path.join( temporary, 'two' ), 9002 ),
	];
	for ( const state of states ) {
		state.name = 'same-label';
		state.project = registry.projectName( state.id, state.name );
		environment.save( state );
	}

	assert.equal(
		new Set( registry.records().map( ( state ) => state.project ) ).size,
		2
	);
	const state = states[ 0 ];
	state.project = registry.projectName( state.id, 'another-label' );
	environment.save( state );
	assert.throws( () => registry.records(), /Invalid ownership/ );
} );

for ( const flag of [ '--name', '--name=' ] ) {
	test( `parseStartOptions consumes ${ flag } and rejects a missing name`, () => {
		const args =
			flag === '--name'
				? [ flag, 'module-order', '--update' ]
				: [ '--name=module-order', '--update' ];

		const options = cloning.parseStartOptions( args );

		assert.equal( options.name, 'module-order' );
		assert.deepEqual( options.args, [ '--update' ] );
		assert.throws(
			() => cloning.parseStartOptions( [ flag ] ),
			/requires an environment name/
		);
		assert.throws( () => registry.nameSlug( '../' ), /letters or digits/ );
	} );
}

function existingPrimaryFixture( checkout ) {
	const core = path.join( temporary, 'existing/WordPress' );
	const testCore = path.join( temporary, 'existing/tests-WordPress' );
	const theme = path.join( temporary, 'existing/course' );
	const compose = path.join( temporary, 'existing/docker-compose.yml' );
	fs.mkdirSync( path.join( core, 'wp-content/uploads' ), {
		recursive: true,
	} );
	fs.mkdirSync( path.join( testCore, 'wp-content/uploads' ), {
		recursive: true,
	} );
	fs.mkdirSync( theme );
	fs.writeFileSync( compose, 'services: {}\n' );
	fs.writeFileSync(
		path.join( core, 'wp-content/uploads/image.txt' ),
		'original media'
	);
	fs.writeFileSync(
		path.join( testCore, 'wp-content/uploads/test.txt' ),
		'original test media'
	);
	const bind = ( Source, Destination ) => ( {
		Type: 'bind',
		Source,
		Destination,
	} );
	const containers = [
		'cli',
		'wordpress',
		'tests-wordpress',
		'mysql',
		'tests-mysql',
	].map( ( name ) => ( {
		Id: name,
		State: { Running: false },
		Config: {
			Labels: {
				'com.docker.compose.project': 'sensei-lms',
				'com.docker.compose.service': name,
				'com.docker.compose.project.config_files': compose,
			},
			Env: [ 'PHP_VERSION=8.3.36' ],
		},
		HostConfig: {
			PortBindings: {
				'80/tcp': [
					{ HostPort: name === 'tests-wordpress' ? '8889' : '8888' },
				],
			},
		},
		Mounts: name.includes( 'mysql' )
			? [
					{
						Type: 'volume',
						Name:
							name === 'mysql'
								? 'sensei-lms_mysql'
								: 'sensei-lms_mysql-test',
						Destination: '/var/lib/mysql',
					},
			  ]
			: [
					bind(
						name === 'tests-wordpress' ? testCore : core,
						'/var/www/html'
					),
					bind( checkout, '/var/www/html/wp-content/plugins/sensei' ),
					bind( theme, '/var/www/html/wp-content/themes/course' ),
			  ],
	} ) );
	return {
		containers,
		core,
		testCore,
		theme,
		commands: {
			run: async ( executable, args ) => {
				if ( args[ 0 ] === 'ps' )
					return containers.map( ( item ) => item.Id ).join( '\n' );
				if ( args[ 0 ] === 'inspect' )
					return JSON.stringify( containers );
				assert.fail(
					'Adoption inspection must not start, import, or reset a site.'
				);
			},
		},
	};
}

test( 'primary adoption reuses the existing database volumes and ports and independently preserves uploads', async () => {
	const state = record( path.join( temporary, 'primary' ) );
	state.primary = true;
	state.project = 'sensei-lms';
	const fixture = existingPrimaryFixture( state.worktree );
	const primary = require( '../../scripts/env/primary.cjs' );

	Object.assign( state, await primary.existing( state, fixture.commands ) );
	primary.preserveUploads( state );

	assert.equal( state.initialized, true );
	assert.equal( state.cloneSource, null );
	assert.equal( state.port, 8888 );
	assert.equal( state.testsPort, 8889 );
	assert.equal( state.php, '8.3' );
	assert.equal( state.adoptedFrom.core, fixture.core );
	environment.save( state );
	assert.equal( registry.records()[ 0 ].project, 'sensei-lms' );
	const copied = path.join(
		registry.directory( state.id ),
		'uploads/image.txt'
	);
	assert.equal( fs.readFileSync( copied, 'utf8' ), 'original media' );
	fs.writeFileSync( copied, 'independent media' );
	assert.equal(
		fs.readFileSync(
			path.join( fixture.core, 'wp-content/uploads/image.txt' ),
			'utf8'
		),
		'original media'
	);
	assert.equal(
		fs.readFileSync(
			path.join(
				registry.directory( state.id ),
				'tests-uploads/test.txt'
			),
			'utf8'
		),
		'original test media'
	);
	fs.writeFileSync( path.join( state.worktree, '.wp-env.json' ), '{}' );
	const { config } = environment.configuration( state, {} );
	assert.equal( config.core, fixture.core );
	assert.equal( config.env.tests.core, fixture.testCore );
	assert.deepEqual( config.themes, [ fixture.theme ] );
} );

for ( const scenario of [ 'foreign checkout', 'unexpected database volume' ] ) {
	test( `primary adoption refuses ${ scenario } before changing resources`, async () => {
		const state = record( path.join( temporary, 'primary' ) );
		const fixture = existingPrimaryFixture( state.worktree );
		if ( scenario === 'foreign checkout' )
			fixture.containers[ 0 ].Mounts[ 1 ].Source = path.join(
				temporary,
				'another-project'
			);
		else
			fixture.containers.find(
				( item ) => item.Id === 'mysql'
			).Mounts[ 0 ].Name = 'another-project_mysql';
		const primary = require( '../../scripts/env/primary.cjs' );

		await assert.rejects(
			primary.existing( state, fixture.commands ),
			/different checkout|database storage/
		);
	} );
}

test( 'a linked worktree cannot claim ownership of the canonical primary project', () => {
	const { linked } = primaryAndLinkedEnvironment();
	linked.primary = true;
	linked.project = 'sensei-lms';
	environment.save( linked );

	assert.throws( () => registry.records(), /Invalid ownership/ );
} );

test( 'the primary environment skips automatic self-cloning but rejects explicit self-cloning', async () => {
	assert.equal(
		await cloning.selectSource( {}, { targetProject: 'sensei-lms' } ),
		null
	);
	await assert.rejects(
		cloning.selectSource(
			{},
			{ cloneFrom: 'sensei-lms', targetProject: 'sensei-lms' }
		),
		/cannot clone itself/
	);
} );

for ( const adopt of [ false, true ] ) {
	test( `create uses the canonical primary project ${ adopt ? 'with existing data' : 'for a fresh site' }`, async () => {
		const checkout = path.join( temporary, 'primary' );
		gitRepository( checkout );
		const fixture = existingPrimaryFixture( checkout );
		fs.cpSync(
			path.join( environment.toolRoot, 'scripts/env' ),
			path.join( checkout, 'scripts/env' ),
			{ recursive: true }
		);
		const bin = path.join( temporary, 'bin' );
		fs.mkdirSync( bin );
		fs.writeFileSync(
			path.join( bin, 'docker' ),
			`#!${ process.execPath }
const args=process.argv.slice(2);
if(args[0]==='ps') console.log(${ JSON.stringify( adopt ? 'existing' : '' ) });
else if(args[0]==='inspect') console.log(${ JSON.stringify( JSON.stringify( fixture.containers ) ) });
else if(args[0]==='volume' && args[1]==='ls') console.log('');
else process.exit(21);
`,
			{ mode: 0o700 }
		);
		const previous = process.env.PATH;
		process.env.PATH = `${ bin }${ path.delimiter }${ previous }`;
		try {
			const local = require( path.join( checkout, 'scripts/env/environment.cjs' ) );

			const created = await local.create();

			assert.equal( created.project, 'sensei-lms' );
			assert.equal( created.primary, true );
			assert.equal( created.initialized, adopt );
			assert.equal( created.cloneSource, null );
			assert.equal( local.current().project, 'sensei-lms' );
			if ( adopt ) assert.equal( created.port, 8888 );
		} finally {
			process.env.PATH = previous;
		}
	} );
}

test( 'primary adoption refuses database volumes without their original containers', async () => {
	const state = record( path.join( temporary, 'primary' ) );
	const commands = {
		run: async ( executable, args ) => {
			if ( args[ 0 ] === 'ps' ) return '';
			if ( args[ 0 ] === 'volume' && args[ 1 ] === 'ls' )
				return 'sensei-lms_mysql';
			assert.fail( 'Orphaned database inspection must not change resources.' );
		},
	};
	const primary = require( '../../scripts/env/primary.cjs' );

	await assert.rejects( primary.existing( state, commands ), /original Compose/ );
} );

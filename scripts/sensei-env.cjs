#!/usr/bin/env node
const registry = require( './env/registry.cjs' );
const environment = require( './env/environment.cjs' );

async function main( args ) {
	const command = args.shift();
	if ( ! command || [ 'help', '--help' ].includes( command ) ) {
		console.log(
			'Usage: node scripts/sensei-env.cjs start|stop|status|list|destroy|prune|retry|exec|run|logs|reset tests\nstart options: --name NAME, --clone-from ID|sensei-lms, --no-clone\nSee docs/development/worktree-environments.md.'
		);
		return;
	}
	if ( [ 'list', 'prune' ].includes( command ) ) {
		const states = registry
			.records()
			.filter(
				( state ) => command !== 'prune' || registry.orphaned( state )
			);
		await environment.list( states );
		if ( command === 'prune' && process.env.APPLY === '1' ) {
			const selected = ( process.env.IDS || '' )
				.split( ',' )
				.filter( Boolean );
			if (
				! selected.length ||
				selected.some(
					( id ) => ! states.some( ( state ) => state.id === id )
				)
			) {
				throw new Error(
					'Set IDS to comma-separated orphan IDs from the preview.'
				);
			}
			for ( const state of states.filter( ( item ) =>
				selected.includes( item.id )
			) ) {
				await environment.destroy( state, true );
			}
		}
		return;
	}
	if (
		command !== 'exec' &&
		args.some(
			( arg ) =>
				arg === '--config' ||
				arg.startsWith( '--config=' ) ||
				arg.startsWith( '--runtime' ) ||
				arg.startsWith( '--auto-port' )
		)
	) {
		throw new Error(
			'Configuration, runtime, and ports are managed by this tool.'
		);
	}
	if ( command === 'start' ) {
		return environment.start( args );
	}
	const state = environment.current();
	if ( ! state ) {
		throw new Error(
			'No environment for this worktree. Run make up first.'
		);
	}
	if ( command === 'destroy' || command === 'cleanup' ) {
		return environment.destroy( state );
	}
	if ( command === 'status' ) {
		return environment.list( [ state ] );
	}
	if ( command === 'retry' ) {
		return environment.retryInitialization( state );
	}

	if ( command === 'exec' && ! state.initialized ) {
		throw new Error(
			'Initialization is incomplete. Run make up or make env-retry.'
		);
	}
	if ( command === 'exec' ) {
		const executable = args.shift();
		if ( ! executable ) {
			throw new Error( 'exec requires a command.' );
		}
		return environment.run( executable, args, {
			env: {
				...environment.childEnvironment( state ),
				SENSEI_TEST_URL: `http://localhost:${ state.testsPort }`,
			},
			cwd: state.worktree,
		} );
	}
	if ( command === 'stop' ) {
		return registry.lock( state.id, () =>
			environment.wpEnv( state, [ 'stop' ] )
		);
	}
	// Development resets must use destroy/recreate so initialization cannot be skipped.
	if ( command === 'clean' || command === 'reset' ) {
		if ( args.length !== 1 || args[ 0 ] !== 'tests' ) {
			throw new Error(
				'Only test database resets are allowed here. Use make destroy to recreate development data.'
			);
		}
	}
	if ( ! [ 'run', 'logs', 'clean', 'reset' ].includes( command ) ) {
		throw new Error(
			'Commands: start, stop, status, list, destroy, prune, retry, exec, run, logs, clean tests.'
		);
	}
	if ( ! state.initialized ) {
		throw new Error(
			'Initialization is incomplete. Run make up or make env-retry.'
		);
	}
	if (
		args.some(
			( arg ) => arg === '--config' || arg.startsWith( '--config=' )
		)
	) {
		throw new Error(
			'Custom wp-env config paths are managed by this tool.'
		);
	}
	await environment.wpEnv( state, [ command, ...args ] );
	if ( command === 'clean' || command === 'reset' ) {
		await environment.compose(
			state,
			[ 'exec', '-T', 'tests-cli', 'wp', 'plugin', 'activate', 'sensei' ],
			true
		);
	}
}
if ( require.main === module ) {
	main( process.argv.slice( 2 ) ).catch( ( error ) => {
		console.error( error.message );
		process.exitCode = 1;
	} );
}
module.exports = { main };

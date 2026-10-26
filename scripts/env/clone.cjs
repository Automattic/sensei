const fs = require( 'node:fs' );
const path = require( 'node:path' );
const registry = require( './registry.cjs' );

function parseStartOptions( args ) {
	const options = {
		args: [],
		name: process.env.SENSEI_ENV_NAME || undefined,
		cloneFrom: process.env.CLONE_FROM || undefined,
		noClone: process.env.NO_CLONE === '1',
	};
	for ( let index = 0; index < args.length; index++ ) {
		const arg = args[ index ];
		if ( arg === '--name' || arg.startsWith( '--name=' ) ) {
			const value = arg === '--name' ? args[ ++index ] : arg.slice( 7 );
			if ( ! value || value.startsWith( '-' ) )
				throw new Error( '--name requires an environment name.' );
			options.name = value;
		} else if (
			arg === '--clone-from' ||
			arg.startsWith( '--clone-from=' )
		) {
			const value =
				arg === '--clone-from' ? args[ ++index ] : arg.slice( 13 );
			if ( ! value || value.startsWith( '-' ) ) {
				throw new Error(
					'--clone-from requires an environment ID from make env-list, or sensei-lms.'
				);
			}
			options.cloneFrom = value;
		} else if ( arg === '--no-clone' ) {
			options.noClone = true;
		} else {
			options.args.push( arg );
		}
	}
	return options;
}
function sourceProjectFor( name ) {
	if ( ! name || name === 'sensei-lms' ) return 'sensei-lms';
	const source = registry
		.records()
		.find( ( state ) => state.id === name || state.project === name );
	if ( ! source )
		throw new Error(
			`Unknown cloning source: ${ name }. Use an environment ID from make env-list, or sensei-lms.`
		);
	if ( ! source.initialized )
		throw new Error( `Cloning source ${ name } is not initialized.` );
	return source.project;
}
async function selectSource( commands, options = {} ) {
	if (
		process.env.CI ||
		( ! options.cloneFrom &&
			( options.noClone || process.env.NO_CLONE === '1' ) )
	) {
		return null;
	}
	const sourceProject = sourceProjectFor( options.cloneFrom );
	if ( sourceProject === options.targetProject ) {
		if ( ! options.cloneFrom ) return null;
		throw new Error( 'An environment cannot clone itself.' );
	}
	const ids = (
		await commands.run(
			'docker',
			[
				'ps',
				'-aq',
				'--filter',
				`label=com.docker.compose.project=${ sourceProject }`,
			],
			{ capture: true }
		)
	)
		.split( '\n' )
		.filter( Boolean );
	if ( ! ids.length ) {
		if ( options.cloneFrom )
			throw new Error(
				`Cloning source ${ sourceProject } is not running. Start it first.`
			);
		if ( options.commonDir ) {
			const commonDir = registry.canonicalPath( options.commonDir );
			const primary = registry
				.records()
				.find(
					( state ) =>
						state.commonDir === commonDir &&
						state.gitDir === commonDir &&
						state.project !== options.targetProject
				);
			if ( primary ) {
				return selectSource( commands, {
					...options,
					cloneFrom: primary.id,
				} );
			}
		}
		return null;
	}
	const containers = JSON.parse(
		await commands.run( 'docker', [ 'inspect', ...ids ], { capture: true } )
	);
	const candidates = containers.filter(
		( item ) =>
			item.Config?.Labels?.[ 'com.docker.compose.service' ] === 'cli'
	);
	if ( candidates.length !== 1 ) {
		throw new Error(
			`Cannot identify a unique ${ sourceProject } CLI container. Restore the source environment or use --no-clone for a fresh site.`
		);
	}
	const container = candidates[ 0 ];
	if (
		container.Config?.Labels?.[ 'com.docker.compose.project' ] !==
			sourceProject ||
		container.Config?.Labels?.[ 'com.docker.compose.service' ] !== 'cli'
	) {
		throw new Error(
			`Cloning source ownership does not match ${ sourceProject }.`
		);
	}
	if ( ! container.State?.Running ) {
		throw new Error(
			`The ${ sourceProject } source is stopped. Start it manually, then retry make up; or use --no-clone for a fresh site.`
		);
	}
	return { container: container.Id, project: sourceProject };
}
function sourceCommand( source, args ) {
	return {
		executable: 'docker',
		args: [ 'exec', source.container, ...args ],
	};
}
async function captureSource( target, commands ) {
	// Resolve the container again on retry; its ID may have changed since creation.
	const source = await selectSource( commands, {
		cloneFrom: target.cloneSource?.project,
		targetProject: target.project,
	} );
	if ( ! source ) {
		throw new Error(
			'The cloning source is unavailable. Restore it and run make env-retry.'
		);
	}
	const read = ( args ) =>
		commands.run(
			'docker',
			sourceCommand( source, [
				'wp',
				'--skip-plugins',
				'--skip-themes',
				...args,
			] ).args,
			{ capture: true }
		);
	// Config and database commands avoid bootstrapping source plugins or WordPress hooks.
	const config = Object.fromEntries(
		JSON.parse(
			await read( [
				'config',
				'list',
				'table_prefix',
				'MULTISITE',
				'--fields=name,value',
				'--format=json',
			] )
		).map( ( item ) => [ item.name, item.value ] )
	);
	if ( [ true, 'true', 1, '1' ].includes( config.MULTISITE ) ) {
		throw new Error(
			'Only single-site development databases can be cloned.'
		);
	}
	if (
		typeof config.table_prefix !== 'string' ||
		! /^[a-zA-Z0-9_]+$/.test( config.table_prefix )
	) {
		throw new Error(
			`Cannot read a valid table prefix from ${ source.project }.`
		);
	}
	const metadata = {
		tablePrefix: config.table_prefix,
		sourceUrl: await read( [
			'db',
			'query',
			`SELECT option_value FROM \`${ config.table_prefix }options\` WHERE option_name='siteurl' LIMIT 1`,
			'--skip-column-names',
			'--batch',
		] ),
	};
	if ( ! /^https?:\/\//.test( metadata.sourceUrl ) ) {
		throw new Error(
			`Cannot read a valid site URL from ${ source.project }.`
		);
	}
	await commands.wp( target, [ 'db', 'reset', '--yes' ] );
	await commands.wp( target, [
		'config',
		'set',
		'table_prefix',
		metadata.tablePrefix,
		'--type=variable',
	] );
	target.tablePrefix = metadata.tablePrefix;
	registry.writeJSON(
		path.join( registry.directory( target.id ), 'state.json' ),
		target
	);
	await commands.streamDatabase( source, target );
	const uploads = '/var/www/html/wp-content/uploads';
	const exists = await commands.run(
		'docker',
		sourceCommand( source, [
			'sh',
			'-c',
			`if [ -d ${ uploads } ]; then printf yes; fi`,
		] ).args,
		{ capture: true }
	);
	if ( exists === 'yes' ) {
		const destination = path.join(
			registry.directory( target.id ),
			'uploads'
		);
		fs.mkdirSync( destination, { recursive: true, mode: 0o700 } );
		await commands.run(
			'docker',
			[ 'cp', '-L', `${ source.container }:${ uploads }/.`, destination ],
			{ capture: true }
		);
	}
	return { metadata };
}
module.exports = {
	parseStartOptions,
	selectSource,
	sourceCommand,
	captureSource,
};

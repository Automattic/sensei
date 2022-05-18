/* eslint-disable no-console */
/**
 * External dependencies
 */
import { readFileSync } from 'fs';
import Git from 'simple-git';
import path from 'path';
import { readFile, rm } from 'fs/promises';

const git = new Git();
const GUTENBERG_TARGET_VERSION = 'v10.9.1';
const TEMP_FOLDER = '../.tmp/gutenberg';

const getExpectedVersionFromGutenberg = ( packageName ) => {
	const packagePath = path.resolve(
		TEMP_FOLDER,
		'packages',
		packageName,
		'package.json'
	);

	const { version } = JSON.parse( readFileSync( packagePath ) );
	return version;
};

const byNameSpace = ( targetNamespace ) => ( [ namespace ] ) =>
	namespace.startsWith( targetNamespace );

const getInstalledDependencies = async ( packageJSONPath ) => {
	const pkg = JSON.parse( await readFile( packageJSONPath ) );

	const devDependencies = Object.entries( pkg.devDependencies )
		.filter( byNameSpace( '@wordpress' ) )
		.map( ( [ module, version ] ) => ( { module, version, isDev: true } ) );

	const dependencies = Object.entries( pkg.dependencies )
		.filter( byNameSpace( '@wordpress' ) )
		.map( ( [ module, version ] ) => ( {
			module,
			version,
			isDev: false,
		} ) );

	return [ ...dependencies, ...devDependencies ];
};

const loadGutenberg = async ( targetVersion ) => {
	await rm( TEMP_FOLDER, { recursive: true, force: true } );
	await git.clone(
		'https://github.com/WordPress/gutenberg.git',
		TEMP_FOLDER,
		{ '--depth': 1, '--branch': targetVersion }
	);
	return TEMP_FOLDER;
};

const getDependencyList = async ( localDependencies ) => {
	return Promise.all( localDependencies.map( toDependencyList ) );
};

const toDependencyList = async ( { module, version, ...rest } ) => {
	const moduleWithoutNameSpace = module.split( '/' )[ 1 ];
	const expectedVersion = await getExpectedVersionFromGutenberg(
		moduleWithoutNameSpace
	);

	return {
		name: module,
		version,
		expectedVersion,
		isDivergent: version !== expectedVersion,
		...rest,
	};
};

const groupByEnv = ( acc, dep ) =>
	acc.set(
		dep.isDev ? 'devDependencies' : 'dependencies',
		(
			acc.get( dep.isDev ? 'devDependencies' : 'dependencies' ) || []
		).concat( `${ dep.name }@${ dep.expectedVersion }` )
	);

const print = ( dependencies, format ) => {
	switch ( format ) {
		case 'npm':
			const groupedByDev = dependencies.reduce( groupByEnv, new Map() );

			console.log(
				'npm install',
				...groupedByDev.get( 'devDependencies' ),
				'--save-dev'
			);

			console.log( ' ' );
			console.log(
				'npm install',
				...groupedByDev.get( 'dependencies' ),
				'--save'
			);
			break;
		default:
			// eslint-disable-next-line no-console
			console.table( dependencies );
			break;
	}
};

const parseArgs = ( argv ) => {
	const [ , , command = 'list', flags ] = argv;
	const [ , format ] = flags?.includes( 'format' )
		? flags.split( '=' )
		: [ null, null ];

	return { command, format };
};

const run = async ( argv ) => {
	const { command, format } = parseArgs( argv );

	await loadGutenberg( GUTENBERG_TARGET_VERSION );
	const localDependencies = await getInstalledDependencies(
		`${ process.cwd() }/package.json`
	);

	console.log(
		'Comparing Local JS dependencies with Gutenberg release dependencies...'
	);

	switch ( command ) {
		case 'list':
			const result = await getDependencyList( localDependencies );
			print( result, format );

			break;

		case 'divergent':
			const all = await getDependencyList( localDependencies );
			const divergent = all.filter( ( dep ) => dep.isDivergent );
			print( divergent, format );
			break;
	}
};

run( process.argv );

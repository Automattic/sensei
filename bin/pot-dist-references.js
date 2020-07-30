/**
 * The purpose of this script is to add the dist references to the
 * pot file based on the chunk map (generated through Webpack).
 *
 * If a message in the pot file contains `src/file.js` as references
 * and this file was bundled as part of the `dist/dist.js`, the
 * `dist/dist.js` will be added as a new reference to the message.
 */

const fs = require( 'fs' );
const { po } = require( 'gettext-parser' );
const chunkMap = require( '../webpack/chunks-map.json' );

const POT_PATH = './lang/sensei-lms.pot';

/**
 * Run script to add new references.
 */
const run = () => {
	const data = fs.readFileSync( POT_PATH, 'utf8' );
	fs.writeFileSync( POT_PATH, addDistReferences( data ) );
};

/**
 * Add dist references to the pot string.
 *
 * @param {string} potString Current pot string to be replaced.
 *
 * @return {string} Pot string after adding new references.
 */
const addDistReferences = ( potString ) => {
	const chunkMapBySource = getChunkMapBySource( chunkMap );

	const potObject = po.parse( potString );
	const { translations } = potObject;

	// Loop through contexts.
	Object.keys( translations ).forEach( ( contextKey ) => {
		const context = translations[ contextKey ];

		// Loop through message objects.
		Object.keys( context ).forEach( ( messageKey ) => {
			const message = context[ messageKey ];
			const { reference } = message.comments;

			if ( reference ) {
				message.comments.reference = getReferenceWithDist(
					reference,
					chunkMapBySource
				);
			}
		} );
	} );

	return po.compile( potObject );
};

/**
 * Add the dist to the reference.
 *
 * @param {string} referenceString  Current reference string to add the dist references.
 * @param {Object} chunkMapBySource Chunk map by source.
 *
 * @return {string} References with the respective dist files.
 */
const getReferenceWithDist = ( referenceString, chunkMapBySource ) => {
	const references = referenceString.split( '\n' );
	let distReferences = [];

	references.forEach( ( reference ) => {
		const referencePath = reference.replace( /:[0-9]+$/, '' );

		if ( chunkMapBySource[ referencePath ] ) {
			distReferences = [
				...distReferences,
				...chunkMapBySource[ referencePath ],
			];
		}
	} );

	distReferences = [ ...new Set( distReferences ) ]; // Remove duplicated.
	distReferences = addLineToReferences( distReferences, 1 );

	return [ ...references, ...distReferences ].join( '\n' );
};

/**
 * Add respective line to the references.
 *
 * @param {string[]} references References to add the line.
 * @param {number}   line       Line to be added to the references.
 *
 * @return {string[]} References with the respective lines.
 */
const addLineToReferences = ( references, line ) =>
	references.map( ( reference ) => reference + ':' + line );

/**
 * Invert the chunk map, getting by source.
 *
 * @param {Object} chunkMapByDist Chunk map by dist.
 *
 * @return {Object} Chunk map by source.
 */
const getChunkMapBySource = ( chunkMapByDist ) =>
	Object.entries( chunkMapByDist ).reduce( ( acc, [ dist, sources ] ) => {
		sources.forEach( ( source ) => {
			if ( ! acc[ source ] ) {
				acc[ source ] = [ dist ];
				return;
			}

			acc[ source ] = [ ...acc[ source ], dist ];
		} );

		return acc;
	}, {} );

run();

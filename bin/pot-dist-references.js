const fs = require( 'fs' );

const PATH = './lang/sensei-lms.pot';

/**
 * Additional references by RegExp.
 */
const additionalReferences = [
	{
		re: /^#: assets\/setup-wizard\//gm,
		reference: '#: assets/dist/setup-wizard/index.js:1',
	},
	{
		re: /^#: assets\/data-port\//gm,
		reference: '#: assets/dist/data-port/import.js:1',
	},
];

/**
 * Run script to add new references.
 */
const run = () => {
	fs.readFile( PATH, 'utf8', ( err, data ) => {
		if ( err ) throw err;

		saveChanges( addNewReferences( data ) );
	} );
};

/**
 * Save changes.
 *
 * @param {string} newPotString New content to be saved.
 */
const saveChanges = ( newPotString ) => {
	fs.writeFile( PATH, newPotString, 'utf8', ( err ) => {
		if ( err ) throw err;
	} );
};

/**
 * Add new references to the pot string.
 *
 * @param {string} potString Current pot string to be replaced.
 *
 * @return {string} Pot string after adding new references.
 */
const addNewReferences = ( potString ) => {
	const potSplitter = '\n\n';
	const splittedPot = potString.split( potSplitter );
	const header = splittedPot[ 0 ];

	const messages = splittedPot
		.slice( 1 ) // Skip header.
		.map( ( message ) => {
			let newMessage = message;

			additionalReferences.forEach( ( r ) => {
				if ( ! message.match( r.re ) ) {
					return;
				}

				newMessage = newMessage.replace(
					/\n(?!#)/,
					'\n' + r.reference + '\n'
				);
			} );

			return newMessage;
		} );

	return [ header, ...messages ].join( potSplitter );
};

run();

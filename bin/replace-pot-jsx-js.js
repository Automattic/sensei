/**
 * Script that replaces `jsx` to `js` occurrences in the pot file.
 * It's because the wp i18n make-json doesn't get jsx files.
 *
 * https://github.com/wp-cli/i18n-command/issues/200
 */

const fs = require( 'fs' );

const PATH = './lang/sensei-lms.pot';

const startReplace = () => {
	fs.readFile( PATH, 'utf8', ( err, data ) => {
		if ( err ) throw err;

		const newContent = data.replace( /.jsx\:/g, '.js:' );
		saveChanges( newContent );
	} );
};

const saveChanges = ( newContent ) => {
	fs.writeFile( PATH, newContent, 'utf8', ( err ) => {
		if ( err ) throw err;
	} );
};

startReplace();

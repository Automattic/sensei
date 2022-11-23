const prettier = require( 'prettier' );
const parserBabel = require( 'prettier/parser-babel' );
const parserFlow = require( 'prettier/parser-flow' );
const { parseComment } = require( '@es-joy/jsdoccomment' );

// In the future this function should ideally be migrated to `comment-parser` dependency.
const alignTransform = require( 'eslint-plugin-jsdoc/dist/alignTransform' );
const {
	stringify,
	transforms: { flow },
} = require( 'comment-parser' );

const prettierPluginJsdoc = {
	languages: prettier
		.getSupportInfo()
		.languages.filter( ( { name } ) =>
			[ 'JavaScript', 'JSX' ].includes( name )
		),
	parsers: {
		get babel() {
			return getParserWithJSDoc( parserBabel.parsers.babel );
		},
		get flow() {
			return getParserWithJSDoc( parserFlow.parsers.flow );
		},
	},
};

const getParserWithJSDoc = ( parser ) => ( {
	...parser,
	parse: ( text, parsers, options ) => {
		const ast = parser.parse( text, parsers, options );

		ast.comments.forEach( ( comment ) => {
			if ( 'CommentBlock' !== comment.type && 'Block' !== comment.type ) {
				return;
			}

			if (
				! comment.value.match( /\n/ ) ||
				! comment.value.match( /^\*/ ) ||
				comment.value.match( /^\*\*/ )
			) {
				return;
			}

			const indent = ''.padStart( comment.start, '\t' ); // Indentation fixed as tabs for now.
			const parsed = parseComment( comment, indent );

			const transform = flow(
				// If it's converted to a dynamic plugin sometime, this configuration should be an option.
				alignTransform( {
					indent,
					tags: [ 'param', 'arg', 'argument', 'property', 'prop' ],
					preserveMainDescriptionPostDelimiter: true,
				} )
			);

			const transformedJsdoc = transform( parsed );
			const formatted = stringify( transformedJsdoc )
				.trimStart()
				.replace( /^\/\*/, '' )
				.replace( /\*\/$/, '' );

			comment.value = formatted;
		} );

		return ast;
	},
} );

module.exports = prettierPluginJsdoc;

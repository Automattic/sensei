const path                 = require( 'path' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );

function getWebpackConfig( env, argv ) {
	const webpackConfig = getBaseWebpackConfig( env, argv );

	return {
		...webpackConfig,
		entry: {
			// TODO: Remove this! It is only here to fix the empty build.
			index: './assets/block-editor/index.js',
		},
		output: {
			path: path.resolve( __dirname, 'assets/block-editor/build' ),
		},
		node: {
			crypto: 'empty',
		},
	};
}

module.exports = getWebpackConfig;

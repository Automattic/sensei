const webpack = require( 'webpack' );
const glob = require( 'glob' );
const miniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const entryArray = glob.sync( './assets/blocks/**/index.jsx' );
const entryObject = entryArray.reduce( ( acc, item ) => {
	let name = item.replace( './assets/blocks/', '' ).replace( '/index.jsx', '' );
	acc[name] = item;

	return acc;
}, {} );

const webpackConfig = ( env, argv ) => {
	return {
		entry: entryObject,
		output: {
			filename: 'build/blocks/[name]/index.js',
			path: __dirname
		},
		module: {
			rules: [
				{
					test: /.jsx$/,
					loader: 'babel-loader',
					exclude: /node_modules/
				},
				{
					test: /style\.s?css$/,
					include: [
						/assets\/blocks/
					],
					use: [
						argv.mode !== 'production' ? 'style-loader' : miniCssExtractPlugin.loader,
						'css-loader',
						'sass-loader'
					]
				}
			],
		},
		plugins: [
			new miniCssExtractPlugin( {
				filename: 'build/blocks/[name]/style.css'
			} )
		]
	};
};

module.exports = webpackConfig;

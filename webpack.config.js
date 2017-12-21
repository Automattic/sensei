/* global require, process, __dirname, module */
const webpack = require( 'webpack' );
const ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
const NODE_ENV = process.env.NODE_ENV || 'development';

// CSS loader for styles specific to blocks.
const blocksCSSPlugin = new ExtractTextPlugin( {
	filename: 'includes/blocks/[name]/build/style.css',
} );

// Configuration for the ExtractTextPlugin.
const extractConfig = {
	use: [
		{ loader: 'raw-loader' },
		{
			loader: 'postcss-loader',
			options: {
				plugins: [
					require( 'autoprefixer' ),
				],
			},
		},
		{
			loader: 'sass-loader',
			query: {
				outputStyle: 'production' === process.env.NODE_ENV ? 'compressed' : 'nested',
			},
		},
	],
};

const webpackConfig = {
	entry: {
		module: './includes/blocks/module/index.jsx',
	},
	output: {
		filename: 'includes/blocks/[name]/build/index.js',
		path: __dirname,
	},
	module: {
		rules: [
			{
				test: /.jsx$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
			{
				test: /style\.s?css$/,
				include: [
					/includes\/blocks/,
				],
				use: blocksCSSPlugin.extract( extractConfig )
			},
		],
	},
	plugins: [
		new webpack.DefinePlugin( {
			'process.env.NODE_ENV': JSON.stringify( NODE_ENV )
		} ),
		blocksCSSPlugin,
	]
};

if ( 'production' === NODE_ENV ) {
	webpackConfig.plugins.push( new webpack.optimize.UglifyJsPlugin() );
}

module.exports = webpackConfig;

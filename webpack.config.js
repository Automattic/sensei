const webpack = require( 'webpack' );
const glob = require( 'glob' );
const miniCssExtractPlugin = require( 'mini-css-extract-plugin' );
// Used for blocks.
// const entryArray = glob.sync( './assets/blocks/**/index.jsx' );
// const entryObject = entryArray.reduce( ( acc, item ) => {
// 	let name = item.replace( './assets/blocks/', '' ).replace( '/index.jsx', '' );
// 	acc[name] = item;

// 	return acc;
// }, {} );

const webpackConfig = ( env, argv ) => {
	return {
		// entry: entryObject, // Used for blocks.
		entry: {
			'admin/lesson-bulk-edit': './assets/js/admin/lesson-bulk-edit.js',
			'admin/lesson-quick-edit': './assets/js/admin/lesson-quick-edit.js',
			'admin/message-menu-fix': './assets/js/admin/message-menu-fix.js',
			'admin/testharness': './assets/js/admin/testharness.js',
			'frontend/course-archive': './assets/js/frontend/course-archive.js',
			'grading-general': './assets/js/grading-general.js',
			'image-selectors': './assets/js/image-selectors.js',
			'learners-bulk-actions': './assets/js/learners-bulk-actions.js',
			'learners-general': './assets/js/learners-general.js',
			'lesson-metadata': './assets/js/lesson-metadata.js',
			'modules-admin': './assets/js/modules-admin.js',
			'ranges': './assets/js/ranges.js',
			'settings': './assets/js/settings.js',
			'user-dashboard': './assets/js/user-dashboard.js',
		},
		output: {
			// filename: 'build/blocks/[name]/index.js', // Used for blocks.
			filename: 'build/woothemes-sensei/assets/js/[name].min.js',
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

const path = require( 'path' );
const process = require( 'process' );
const { fromPairs } = require( 'lodash' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );
const isDevelopment = process.env.NODE_ENV !== 'production';

const files = [
	'js/admin/course-edit.js',
	'js/admin/event-logging.js',
	'js/admin/lesson-bulk-edit.js',
	'js/admin/lesson-quick-edit.js',
	'js/admin/message-menu-fix.js',
	'js/admin/ordering.js',
	'js/admin/testharness.js',
	'js/frontend/course-archive.js',
	'js/grading-general.js',
	'js/image-selectors.js',
	'js/learners-bulk-actions.js',
	'js/learners-general.js',
	'js/lesson-metadata.js',
	'js/modules-admin.js',
	'js/ranges.js',
	'js/settings.js',
	'js/user-dashboard.js',
	'setup-wizard/index.jsx',
	'setup-wizard/style.scss',
	'shared/styles/wp-components.scss',
	'shared/styles/wc-components.scss',
	'data-port/import.jsx',
	'data-port/style.scss',

	'css/frontend.scss',
	'css/admin-custom.css',
	'css/extensions.scss',
	'css/global.css',
	'css/jquery-ui.css',
	'css/modules-admin.css',
	'css/modules-frontend.scss',
	'css/ranges.css',
	'css/settings.scss',
];

function getName( filename ) {
	return filename.replace( /\.\w*$/, '' );
}

const FileLoader = {
	test: /\.(?:gif|jpg|jpeg|png|svg|woff|woff2|eot|ttf|otf)$/i,
	loader: 'file-loader',
	options: {
		name: '[path]/[name]-[contenthash].[ext]',
		context: 'assets',
		publicPath: '..',
	},
};

function mapFilesToEntries( filenames ) {
	return fromPairs(
		filenames.map( ( filename ) => [
			getName( filename ),
			`./assets/${ filename }`,
		] )
	);
}

function getWebpackConfig( env, argv ) {
	const webpackConfig = getBaseWebpackConfig( { ...env, WP: true }, argv );
	return {
		...webpackConfig,
		entry: mapFilesToEntries( files ),
		output: {
			path: path.resolve( './assets/dist' ),
		},
		devtool:
			process.env.SOURCEMAP ||
			( isDevelopment ? 'eval-source-map' : false ),
		module: {
			rules: [ FileLoader, ...webpackConfig.module.rules ],
		},
		node: {
			crypto: 'empty',
		},
	};
}

module.exports = getWebpackConfig;

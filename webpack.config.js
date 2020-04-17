const path = require( 'path' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );

const files = [
	'js/admin/course-edit.js',
	'js/admin/email-signup.js',
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
	'js/admin/onboarding.jsx',

	'css/admin/email-signup.scss',
	'css/frontend/sensei.scss',
	'css/activation.scss',
	'css/admin-custom.css',
	'css/extensions.scss',
	'css/global.css',
	'css/jquery-ui.css',
	'css/modules-admin.css',
	'css/modules-frontend.scss',
	'css/ranges.css',
	'css/settings.scss',
];

function getName ( filename ) {
	return filename
		.replace( /\.\w*$/, '' )
		;
}

const FileLoader = {
	test: /\.(?:gif|jpg|jpeg|png|svg|woff|woff2|eot|ttf|otf)$/i,
	loader: 'file-loader',
	options: {
		name: '[path]/[name]-[contenthash].[ext]',
		context: 'assets',
		publicPath: '..'
	}
};

function mapFilesToEntries ( files ) {
	return Object.fromEntries( files.map( filename =>
		[ getName( filename ), `./assets/${filename}` ]
	) );
}

function getWebpackConfig ( env, argv ) {
	const webpackConfig = getBaseWebpackConfig( {...env, WP: true}, argv );
	return {
		...webpackConfig,
		entry: mapFilesToEntries( files ),
		output: {
			path: path.resolve( './assets/dist' )
		},
		module: {
			rules: [
				FileLoader,
				...webpackConfig.module.rules,
			]
		},
		node: {
			crypto: 'empty',
		}
	};
}

module.exports = getWebpackConfig;

/**
 * External dependencies
 */
const path = require( 'path' );
const process = require( 'process' );
const { fromPairs } = require( 'lodash' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );

/**
 * Internal dependencies
 */
const GenerateChunksMapPlugin = require( './scripts/webpack/generate-chunks-map-plugin' );

const isDevelopment = process.env.NODE_ENV !== 'production';

const files = [
	'js/admin/course-edit.js',
	'js/admin/event-logging.js',
	'js/admin/lesson-bulk-edit.js',
	'js/admin/lesson-quick-edit.js',
	'js/admin/message-menu-fix.js',
	'js/admin/meta-box-quiz-editor.js',
	'js/admin/lesson-edit.js',
	'js/admin/ordering.js',
	'js/admin/sensei-notice-dismiss.js',
	'js/frontend/course-archive.js',
	'js/grading-general.js',
	'js/image-selectors.js',
	'js/learners-bulk-actions.js',
	'js/learners-general.js',
	'js/modules-admin.js',
	'js/ranges.js',
	'js/settings.js',
	'js/user-dashboard.js',
	'js/stop-double-submission.js',
	'setup-wizard/index.js',
	'setup-wizard/style.scss',
	'extensions/index.js',
	'extensions/extensions.scss',
	'shared/styles/wp-components.scss',
	'data-port/import.js',
	'data-port/export.js',
	'data-port/style.scss',
	'blocks/editor-components/editor-components-style.scss',
	'blocks/single-page.js',
	'blocks/single-page-style.scss',
	'blocks/single-page-style-editor.scss',
	'blocks/single-course.js',
	'blocks/single-course-style.scss',
	'blocks/single-course-style-editor.scss',
	'blocks/single-lesson.js',
	'blocks/single-lesson-style-editor.scss',
	'blocks/quiz/index.js',
	'blocks/quiz/quiz.editor.scss',
	'blocks/shared.js',
	'blocks/shared-style.scss',
	'blocks/shared-style-editor.scss',
	'blocks/frontend.js',
	'admin/exit-survey/index.js',
	'admin/exit-survey/exit-survey.scss',
	'css/tools.scss',
	'css/enrolment-debug.scss',
	'css/frontend.scss',
	'css/admin-custom.css',
	'css/extensions.scss',
	'css/global.scss',
	'css/jquery-ui.css',
	'css/modules-admin.css',
	'css/modules-frontend.scss',
	'css/pages-frontend.scss',
	'css/ranges.css',
	'css/settings.scss',
	'css/meta-box-quiz-editor.scss',
];

function getName( filename ) {
	return filename.replace( /\.\w*$/, '' );
}

const FileLoader = {
	test: /\.(?:gif|jpg|jpeg|png|woff|woff2|eot|ttf|otf)$/i,
	loader: 'file-loader',
	options: {
		name: '[path][name]-[contenthash].[ext]',
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

const baseDist = 'assets/dist/';

function getWebpackConfig( env, argv ) {
	const webpackConfig = getBaseWebpackConfig( { ...env, WP: true }, argv );
	webpackConfig.module.rules[ 3 ].use[ 0 ].options.publicPath = '../images';

	return {
		...webpackConfig,
		entry: mapFilesToEntries( files ),
		output: {
			path: path.resolve( '.', baseDist ),
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
		plugins: [
			...webpackConfig.plugins,
			new GenerateChunksMapPlugin( {
				output: path.resolve(
					'./node_modules/.cache/sensei-lms/chunks-map.json'
				),
				ignoreSrcPattern: /^node_modules/,
				baseDist,
			} ),
		],
	};
}

module.exports = getWebpackConfig;

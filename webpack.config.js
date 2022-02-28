/**
 * External dependencies
 */
const path = require( 'path' );
const process = require( 'process' );
const { fromPairs } = require( 'lodash' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const SVGSpritemapPlugin = require( 'svg-spritemap-webpack-plugin' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );

/**
 * Internal dependencies
 */
const GenerateChunksMapPlugin = require( './scripts/webpack/generate-chunks-map-plugin' );

const isDevelopment = process.env.NODE_ENV !== 'production';

const files = [
	'js/admin/course-edit.js',
	'js/admin/course-index.js',
	'js/admin/event-logging.js',
	'js/admin/lesson-bulk-edit.js',
	'js/admin/lesson-quick-edit.js',
	'js/admin/message-menu-fix.js',
	'js/admin/meta-box-quiz-editor.js',
	'js/admin/lesson-edit.js',
	'js/admin/ordering.js',
	'js/admin/sensei-notice-dismiss.js',
	'js/admin/custom-navigation.js',
	'js/admin/reports.js',
	'js/frontend/course-archive.js',
	'js/frontend/course-video/video-blocks-extension.js',
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
	'blocks/quiz/ordering-promo/index.js',
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
	'css/admin-custom.scss',
	'css/extensions.scss',
	'css/global.scss',
	'css/jquery-ui.css',
	'css/modules-admin.css',
	'css/modules-frontend.scss',
	'css/pages-frontend.scss',
	'css/course-editor.scss',
	'css/lesson-editor.scss',
	'css/ranges.css',
	'css/settings.scss',
	'css/meta-box-quiz-editor.scss',
	'css/learning-mode.scss',
	'css/learning-mode.editor.scss',
	'css/learning-mode.theme.scss',
	'css/sensei-theme-blocks.scss',
	'course-theme/learning-mode.js',
	'course-theme/course-theme.editor.js',
	'course-theme/blocks/blocks.js',
];

function getName( filename ) {
	return filename.replace( /\.\w*$/, '' );
}

function mapFilesToEntries( filenames ) {
	return fromPairs(
		filenames.map( ( filename ) => [
			getName( filename ),
			`./${ filename }`,
		] )
	);
}

const baseDist = 'assets/dist/';

function getWebpackConfig( env, argv ) {
	const webpackConfig = getBaseWebpackConfig( { ...env, WP: true }, argv );
	webpackConfig.module.rules[ 3 ].generator.publicPath = '../';

	// Handle SVG images only in CSS files.
	webpackConfig.module.rules[ 3 ].test = /\.(?:gif|jpg|jpeg|png)$/i;
	webpackConfig.module.rules = [
		...webpackConfig.module.rules,
		{
			...webpackConfig.module.rules[ 3 ],
			issuer: /\.(sc|sa|c)ss$/,
			test: /\.svg$/,
		},
	];

	return {
		...webpackConfig,
		context: path.resolve( __dirname, 'assets' ),
		entry: mapFilesToEntries( files ),
		output: {
			path: path.resolve( '.', baseDist ),
		},
		devtool:
			process.env.SOURCEMAP ||
			( isDevelopment ? 'eval-source-map' : false ),
		module: {
			rules: [
				...webpackConfig.module.rules,
				{
					test: /\.(?:gif|jpg|jpeg|png|woff|woff2|eot|ttf|otf)$/i,
					type: 'asset/resource',
					generator: {
						filename: '[path][name]-[contenthash][ext]',
						publicPath: '../',
					},
				},
				{
					test: /\.svg$/,
					issuer: /\.[jt]sx?$/,
					use: [ '@svgr/webpack' ],
				},
			],
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
			new CopyPlugin( {
				patterns: [
					{
						from: path.resolve( '.', 'assets/images' ),
						to: path.resolve( '.', baseDist, 'images' ),
					},
				],
			} ),
			new SVGSpritemapPlugin( 'assets/icons/**/*.svg', {
				output: {
					filename: 'icons/sensei-sprite.svg',
				},
				sprite: {
					generate: {
						title: false,
					},
					prefix: 'sensei-sprite-',
				},
			} ),
		],
	};
}

module.exports = getWebpackConfig;

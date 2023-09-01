/**
 * External dependencies
 */
const path = require( 'path' );
const process = require( 'process' );
const { fromPairs } = require( 'lodash' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const SVGSpritemapPlugin = require( 'svg-spritemap-webpack-plugin' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );
const TerserPlugin = require( 'terser-webpack-plugin' );

/**
 * WordPress dependencies
 */
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

/**
 * I18n methods that should not be mangled by the compiler process
 */
const I18N_METHODS = [ '__', '_n', '_nx', '_x' ];

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
	'js/admin/lesson-ai.js',
	'js/admin/ordering.js',
	'js/admin/sensei-notice-dismiss.js',
	'js/admin/custom-navigation.js',
	'js/admin/reports.js',
	'js/frontend/course-archive.js',
	'js/frontend/course-video/video-blocks-extension.js',
	'js/file-upload-question-type.js',
	'js/grading-general.js',
	'js/image-selectors.js',
	'js/learners-bulk-actions.js',
	'js/learners-general.js',
	'js/modules-admin.js',
	'js/ranges.js',
	'js/settings.js',
	'js/user-dashboard.js',
	'js/stop-double-submission.js',
	'js/question-answer-tinymce-editor.js',
	'setup-wizard/index.js',
	'setup-wizard/style.scss',
	'home/index.js',
	'home/home.scss',
	'shared/styles/wp-components.scss',
	'shared/components/modal/style.scss',
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
	'blocks/global-blocks.js',
	'blocks/global-blocks-style.scss',
	'blocks/global-blocks-style-editor.scss',
	'blocks/single-lesson-style-editor.scss',
	'blocks/course-list-filter-block/course-list-filter.js',
	'blocks/quiz/index.js',
	'blocks/quiz/ordering-promo/index.js',
	'blocks/quiz/quiz.editor.scss',
	'blocks/shared.js',
	'blocks/shared-style.scss',
	'blocks/shared-style-editor.scss',
	'blocks/frontend.js',
	'blocks/core-pattern-polyfill/core-pattern-polyfill.js',
	'blocks/email-editor.js',
	'css/email-notifications/email-editor-style.scss',
	'css/email-notifications/email-style.scss',
	'admin/editor-wizard/index.js',
	'admin/editor-wizard/style.scss',
	'admin/exit-survey/index.js',
	'admin/exit-survey/exit-survey.scss',
	'admin/students/student-action-menu/index.js',
	'admin/students/student-bulk-action-button/index.js',
	'admin/students/student-bulk-action-button/student-bulk-action-button.scss',
	'admin/students/student-modal/student-modal.scss',
	'admin/emails/email-preview-button/index.js',
	'admin/emails/email-preview-button/email-preview-button.scss',
	'css/block-patterns.scss',
	'css/page-block-patterns.scss',
	'css/tools.scss',
	'css/enrolment-debug.scss',
	'css/frontend.scss',
	'css/admin-custom.scss',
	'css/home.scss',
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
	'css/learning-mode.4-0-2.scss',
	'css/learning-mode.scss',
	'css/learning-mode-compat.scss',
	'css/learning-mode.editor.scss',
	'css/learning-mode.theme.scss',
	'css/sensei-theme-blocks.scss',
	'css/sensei-course-theme/sidebar-mobile-menu.scss',
	'css/showcase-upsell.scss',
	'css/senseilms-licensing.scss',
	'course-theme/learning-mode.js',
	'course-theme/course-theme.editor.js',
	'course-theme/blocks/index.js',
	'course-theme/themes/default-theme.scss',
	'course-theme/learning-mode-templates/index.js',
	'course-theme/learning-mode-templates/styles.scss',
	'css/3rd-party/themes/astra/learning-mode.scss',
	'css/3rd-party/themes/course/learning-mode.scss',
	'css/3rd-party/themes/divi/learning-mode.scss',
	'css/3rd-party/themes/divi/learning-mode.editor.scss',
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
	const styleSheetFiles = /\.(sc|sa|c)ss$/i;
	const scriptFiles = /\.[jt]sx?$/i;

	const isProduction = process.env.NODE_ENV === 'production';
	const COMBINE_ASSETS = 'true' === process.env.COMBINE_ASSETS;

	webpackConfig.module.rules = webpackConfig.module.rules.map( ( rule ) => {
		if ( rule.test.test( 'test.scss' ) ) {
			const use = rule.use.slice();
			// Find where the sass-loader is installed.
			const sassRuleIndex = use.findIndex(
				( useRule ) =>
					require.resolve( 'sass-loader' ) === useRule.loader
			);
			const computeSourceMap =
				use[ sassRuleIndex ].options.sourceMap ?? ! isProduction;

			use[ sassRuleIndex ] = {
				...use[ sassRuleIndex ],
				options: {
					...use[ sassRuleIndex ].options,
					// Always enable Source Maps, because resolve-url-loader will
					// need these source maps to work correctly.
					sourceMap: true,
				},
			};

			// Insert resolve-url-loader just before the sass-loader.
			use.splice( sassRuleIndex, 0, {
				loader: require.resolve( 'resolve-url-loader' ),
				options: {
					sourceMap: computeSourceMap,
				},
			} );
			return {
				...rule,
				use,
			};
		}
		if ( rule.test.test( 'image.svg' ) ) {
			// Handle SVG images only in CSS files.
			return {
				...rule,
				test: /\.(?:gif|jpg|jpeg|png|woff|woff2|eot|ttf|otf|svg)$/i,
				issuer: styleSheetFiles,
				generator: {
					...rule.generator,
					publicPath: '../',
				},
			};
		}
		return rule;
	} );

	// Handle only images in JS files
	webpackConfig.module.rules.push(
		{
			test: /\.(?:gif|jpg|jpeg|png|webp)$/i,
			issuer: scriptFiles,
			type: 'asset/resource',
			generator: {
				filename: '[path][name]-[contenthash][ext]',
				publicPath: 'assets/dist/',
			},
		},
		{
			test: /\.svg$/,
			issuer: scriptFiles,
			use: [ '@svgr/webpack' ],
		}
	);

	// We remove the DependencyExtractionWebpackPlugin from the list of plugins because we will
	// add some custom parameters later.
	const plugins = webpackConfig.plugins.filter(
		( plugin ) =>
			plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
	);

	return {
		...webpackConfig,
		context: path.resolve( __dirname, 'assets' ),
		entry: mapFilesToEntries( files ),
		output: {
			path: path.resolve( '.', baseDist ),
		},
		optimization: {
			concatenateModules: false,
			minimizer: [
				new TerserPlugin( {
					extractComments: false,
					terserOptions: {
						mangle: { reserved: I18N_METHODS },
						format: { comments: true },
					},
				} ),
			],
		},
		devtool:
			process.env.SOURCEMAP ||
			( isDevelopment ? 'eval-source-map' : false ),
		plugins: [
			...plugins,
			new DependencyExtractionWebpackPlugin( {
				injectPolyfill: true,
				combineAssets: COMBINE_ASSETS,
				outputFormat: COMBINE_ASSETS ? 'json' : 'php',
			} ),
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

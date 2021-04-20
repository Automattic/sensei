/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

/**
 * External dependencies
 */
const path = require( 'path' );

/**
 * Internal dependencies
 */
const GenerateChunksMapPlugin = require( './scripts/webpack/generate-chunks-map-plugin' );

const files = {
	'js/admin/course-edit': 'js/admin/course-edit.js',
	'js/admin/event-logging': 'js/admin/event-logging.js',
	'js/admin/lesson-bulk-edit': 'js/admin/lesson-bulk-edit.js',
	'js/admin/lesson-quick-edit': 'js/admin/lesson-quick-edit.js',
	'js/admin/message-menu-fix': 'js/admin/message-menu-fix.js',
	'js/admin/meta-box-quiz-editor': 'js/admin/meta-box-quiz-editor.js',
	'js/admin/lesson-edit': 'js/admin/lesson-edit.js',
	'js/admin/ordering': 'js/admin/ordering.js',
	'js/frontend/course-archive': 'js/frontend/course-archive.js',
	'js/grading-general': 'js/grading-general.js',
	'js/image-selectors': 'js/image-selectors.js',
	'js/learners-bulk-actions': 'js/learners-bulk-actions.js',
	'js/learners-general': 'js/learners-general.js',
	'js/modules-admin': 'js/modules-admin.js',
	'js/ranges': 'js/ranges.js',
	'js/settings': 'js/settings.js',
	'js/user-dashboard': 'js/user-dashboard.js',
	'js/stop-double-submission': 'js/stop-double-submission.js',
	'setup-wizard/index': 'setup-wizard/index.js',
	'setup-wizard/setup-wizard': 'setup-wizard/setup-wizard.scss',
	'extensions/index': 'extensions/index.js',
	'extensions/extensions': 'extensions/extensions.scss',
	'shared/styles/wp-components': 'shared/styles/wp-components.scss',
	'data-port/import': 'data-port/import.js',
	'data-port/export': 'data-port/export.js',
	'data-port/data-port': 'data-port/data-port.scss',
	'blocks/editor-components/editor-components-style':
		'blocks/editor-components/editor-components-style.scss',
	'blocks/single-page': 'blocks/single-page.js',
	'blocks/single-page-style': 'blocks/single-page-style.scss',
	'blocks/single-page-style-editor': 'blocks/single-page-style-editor.scss',
	'blocks/single-course': 'blocks/single-course.js',
	'blocks/single-course-style': 'blocks/single-course-style.scss',
	'blocks/single-course-style-editor':
		'blocks/single-course-style-editor.scss',
	'blocks/single-lesson': 'blocks/single-lesson.js',
	'blocks/single-lesson-style-editor':
		'blocks/single-lesson-style-editor.scss',
	'blocks/quiz/index': 'blocks/quiz/index.js',
	'blocks/quiz/quiz.editor': 'blocks/quiz/quiz.editor.scss',
	'blocks/shared': 'blocks/shared.js',
	'blocks/shared-style': 'blocks/shared-style.scss',
	'blocks/shared-style-editor': 'blocks/shared-style-editor.scss',
	'blocks/frontend': 'blocks/frontend.js',
	'admin/exit-survey/index': 'admin/exit-survey/index.js',
	'admin/exit-survey/exit-survey': 'admin/exit-survey/exit-survey.scss',
	'css/tools': 'css/tools.scss',
	'css/enrolment-debug': 'css/enrolment-debug.scss',
	'css/frontend': 'css/frontend.scss',
	'css/admin-custom': 'css/admin-custom.css',
	'css/extensions': 'css/extensions.scss',
	'css/global': 'css/global.css',
	'css/jquery-ui': 'css/jquery-ui.css',
	'css/modules-admin': 'css/modules-admin.css',
	'css/modules-frontend': 'css/modules-frontend.scss',
	'css/ranges': 'css/ranges.css',
	'css/settings': 'css/settings.scss',
	'css/meta-box-quiz-editor': 'css/meta-box-quiz-editor.scss',
};

const baseDist = 'assets/dist/';

Object.keys( files ).forEach( function ( key ) {
	files[ key ] = path.resolve( './assets', files[ key ] );
} );

module.exports = {
	...defaultConfig,
	entry: files,
	output: {
		path: path.resolve( '.', baseDist ),
	},
	plugins: [
		...defaultConfig.plugins,
		new GenerateChunksMapPlugin( {
			output: path.resolve(
				'./node_modules/.cache/sensei-lms/chunks-map.json'
			),
			ignoreSrcPattern: /^node_modules/,
			baseDist,
		} ),
	],
};

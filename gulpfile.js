/* eslint-disable */
/**
 * Gulp File
 *
 * 1) Make sure you have node and npm installed locally
 *
 * 2) Install all the modules from package.json:
 * $ npm install
 *
 * 3) Run gulp to minify javascript and css using the 'gulp' command.
 *
 */

var del = require( 'del' );
var exec = require( 'child_process' ).exec;
var gulp = require( 'gulp' );
var zip = require( 'gulp-zip' );
var process = require( 'process' );

function npm_run( command ) {
	var npmProcess = exec( `npm run ${ command }` );
	npmProcess.stdout.pipe( process.stdout );
	npmProcess.stderr.pipe( process.stderr );
	return npmProcess;
}

var paths = {
	select2: [
		'node_modules/select2/dist/css/select2.min.css',
		'node_modules/select2/dist/js/select2.full.js',
		'node_modules/select2/dist/js/select2.full.min.js',
	],
	packageContents: [
		'assets/**/*',
		'changelog.txt',
		'CONTRIBUTING.md',
		'LICENSE',
		'dummy_data.xml',
		'includes/**/*',
		'lang/**/*',
		'readme.txt',
		'templates/**/*',
		'uninstall.php',
		'widgets/**/*',
		'sensei-lms.php',
		'wpml-config.xml',
	],
	packageDir: 'build/sensei-lms',
	packageZip: 'build/sensei-lms.zip',
};

gulp.task(
	'clean',
	gulp.series( function( cb ) {
		return del(
			[ 'assets/dist/**', 'assets/vendor/select2/**', 'build' ],
			cb
		);
	} )
);

function buildWebpack() {
	return npm_run( 'build' );
}

gulp.task( 'webpack', gulp.series( buildWebpack ) );

gulp.task(
	'block-editor-assets',
	gulp.series( function( cb ) {
		exec( 'npm run block-editor-assets', cb );
	} )
);

gulp.task( 'vendor', function() {
	return gulp
		.src( paths.select2 )
		.pipe( gulp.dest( 'assets/vendor/select2' ) );
} );

gulp.task(
	'test',
	gulp.series( function npm_test() {
		npm_run( 'test' );
	} )
);

gulp.task( 'build', gulp.series( 'test', 'clean', 'webpack', 'vendor' ) );
gulp.task( 'build-unsafe', gulp.series( 'clean', 'webpack', 'vendor' ) );

gulp.task( 'copy-package', function() {
	return gulp
		.src( paths.packageContents, { base: '.' } )
		.pipe( gulp.dest( paths.packageDir ) );
} );

gulp.task( 'zip-package', function() {
	return gulp
		.src( paths.packageDir + '/**/*', { base: paths.packageDir + '/..' } )
		.pipe( zip( paths.packageZip ) )
		.pipe( gulp.dest( '.' ) );
} );

gulp.task( 'package', gulp.series( 'build', 'copy-package', 'zip-package' ) );
gulp.task(
	'package-unsafe',
	gulp.series( 'build-unsafe', 'copy-package', 'zip-package' )
);

gulp.task( 'default', gulp.series( 'build' ) );

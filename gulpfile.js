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


var babel           = require( 'gulp-babel' );
var checktextdomain = require( 'gulp-checktextdomain' );
var chmod           = require( 'gulp-chmod' );
var del             = require( 'del' );
var exec            = require( 'child_process' ).exec;
var gulp            = require( 'gulp' );
var cleanCSS        = require( 'gulp-clean-css' );
var rename          = require( 'gulp-rename' );
var sass            = require( 'gulp-sass' );
var sort            = require( 'gulp-sort' );
var uglify          = require( 'gulp-uglify' );
var wpPot           = require( 'gulp-wp-pot' );
var zip             = require( 'gulp-zip' );
var process         = require( 'process' );

function npm_run ( command ) {
	var npmProcess = exec( `npm run ${command}` );
	npmProcess.stdout.pipe( process.stdout );
	npmProcess.stderr.pipe( process.stderr );
	return npmProcess;
}

var paths = {
	scripts: [ 'assets/js/**/*.js', '!assets/js/**/*.min.js' ],
	css: [ 'assets/css/**/*.scss' ],
	select2: [
		'node_modules/select2/dist/css/select2.min.css',
		'node_modules/select2/dist/js/select2.full.js',
		'node_modules/select2/dist/js/select2.full.min.js'
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
	packageZip: 'build/sensei-lms.zip'
};

gulp.task( 'clean', gulp.series( function( cb ) {
	return del( [
		'assets/js/**/*.min.js',
		'assets/js/**/*.min.js',
		'assets/css/**/*.min.css',
		'assets/vendor/select2/**',
		'build'
	], cb );
} ) );

function buildCSS() {
	return gulp.src( paths.css )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( gulp.dest( 'assets/css' ) );
}

function buildJS() {
	return gulp.src( paths.scripts )
		.pipe( babel( {
			'configFile': './.babelrc-legacy',
		} ) )
		// This will minify and rename to *.min.js
		.pipe( uglify() )
		.pipe( rename( { extname: '.min.js' } ) )
		.pipe( chmod( 0o644 ) )
		.pipe( gulp.dest( 'assets/js' ) );
}

gulp.task( 'CSS', gulp.series( buildCSS ) );

gulp.task( 'JS', gulp.series( buildJS ) );

gulp.task( 'block-editor-assets', gulp.series( function( cb ) {
	exec( 'npm run block-editor-assets', cb );
} ) );

gulp.task( 'pot', gulp.series( function() {
	return gulp.src( [ '**/**.php', '!node_modules/**', '!vendor/**', '!build/**', '!tests/**' ] )
		.pipe( sort() )
		.pipe( wpPot( {
			domain: 'sensei-lms',
			bugReport: 'https://translate.wordpress.org/projects/wp-plugins/sensei-lms/'
		} ) )
		.pipe( gulp.dest( 'lang/sensei-lms.pot' ) );
} ) );

gulp.task( 'textdomain', gulp.series( function() {
	return gulp.src( [ '**/*.php', '!node_modules/**', '!build/**' , '!vendor/**' ] )
		.pipe( checktextdomain( {
			text_domain: 'sensei-lms',
			keywords: [
				'__:1,2d',
				'_e:1,2d',
				'_x:1,2c,3d',
				'esc_html__:1,2d',
				'esc_html_e:1,2d',
				'esc_html_x:1,2c,3d',
				'esc_attr__:1,2d',
				'esc_attr_e:1,2d',
				'esc_attr_x:1,2c,3d',
				'_ex:1,2c,3d',
				'_n:1,2,4d',
				'_nx:1,2,4c,5d',
				'_n_noop:1,2,3d',
				'_nx_noop:1,2,3c,4d'
			]
		} ) );
} ) );

gulp.task( 'vendor', function() {
	return gulp.src( paths.select2 )
		.pipe( gulp.dest( 'assets/vendor/select2' ) );
} );

gulp.task( 'test', gulp.series( function npm_test ( ) {
	npm_run( 'test' )
} ) );


gulp.task( 'build', gulp.series( 'test', 'clean', 'CSS', 'JS', 'block-editor-assets', 'vendor' ) );
gulp.task( 'build-unsafe', gulp.series( 'clean', 'CSS', 'JS', 'block-editor-assets', 'vendor' ) );

gulp.task( 'copy-package', function() {
	return gulp.src( paths.packageContents, { base: '.' } )
		.pipe( gulp.dest( paths.packageDir ) );
} );

gulp.task( 'zip-package', function() {
	return gulp.src( paths.packageDir + '/**/*', { base: paths.packageDir + '/..' } )
		.pipe( zip( paths.packageZip ) )
		.pipe( gulp.dest( '.' ) );
} );

gulp.task( 'package', gulp.series( 'build', 'copy-package', 'zip-package' ) );
gulp.task( 'package-unsafe', gulp.series( 'build-unsafe', 'copy-package', 'zip-package' ) );

gulp.task( 'default', gulp.series( 'build' ) );

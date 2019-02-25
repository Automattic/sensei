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
 */

var babel           = require( 'gulp-babel' );
var checktextdomain = require( 'gulp-checktextdomain' );
var chmod           = require( 'gulp-chmod' );
var del             = require( 'del' );
var gulp            = require( 'gulp' );
var minifyCSS       = require( 'gulp-minify-css' );
var phpunit         = require( 'gulp-phpunit' );
var rename          = require( 'gulp-rename' );
var sass            = require( 'gulp-sass' );
var sort            = require( 'gulp-sort' );
var uglify          = require( 'gulp-uglify' );
var wpPot           = require( 'gulp-wp-pot' );
var zip             = require( 'gulp-zip' );

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
		'README.md',
		'templates/**/*',
		'uninstall.php',
		'widgets/**/*',
		'sensei.php',
		'wpml-config.xml',
	],
	packageDir: 'build/sensei',
	packageZip: 'build/sensei.zip'
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

gulp.task( 'CSS', gulp.series( function() {
	return gulp.src( paths.css )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( minifyCSS( { keepBreaks: false } ) )
		.pipe( gulp.dest( 'assets/css' ) );
} ) );

gulp.task( 'JS', gulp.series( function() {
	return gulp.src( paths.scripts )
		.pipe( babel() )
		// This will minify and rename to *.min.js
		.pipe( uglify() )
		.pipe( rename( { extname: '.min.js' } ) )
		.pipe( chmod( 0o644 ) )
		.pipe( gulp.dest( 'assets/js' ) );
} ) );

gulp.task( 'pot', gulp.series( function() {
	return gulp.src( [ '**/**.php', '!node_modules/**', '!build/**' ] )
		.pipe( sort() )
		.pipe( wpPot( {
			domain: 'woothemes-sensei',
			bugReport: 'https://www.transifex.com/woothemes/sensei-by-woothemes/'
		} ) )
		.pipe( gulp.dest( 'lang/sensei.pot' ) );
} ) );

gulp.task( 'textdomain', gulp.series( function() {
	return gulp.src( [ '**/*.php', '!node_modules/**', '!build/**' ] )
		.pipe( checktextdomain( {
			text_domain: 'woothemes-sensei',
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

gulp.task( 'test', function() {
	return gulp.src( 'phpunit.xml' )
		.pipe( phpunit() );
} );

gulp.task( 'build', gulp.series( 'test', 'clean', 'CSS', 'JS', 'vendor' ) );
gulp.task( 'build-unsafe', gulp.series( 'clean', 'CSS', 'JS', 'vendor' ) );

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

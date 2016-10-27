/**
 * Gulp File
 *
 * 1) Make sure you have node and npm installed locally
 *
 * 2) Install all the modules from package.json:
 * $ npm install
 *
 * 3) Run gulp to mifiy javascript and css using the 'gulp' command.
 */

var gulp            = require( 'gulp' );
var rename          = require( 'gulp-rename' );
var uglify          = require( 'gulp-uglify' );
var minifyCSS       = require( 'gulp-minify-css' );
var chmod           = require( 'gulp-chmod' );
var del             = require( 'del' );
var sass            = require( 'gulp-sass' );
var wpPot           = require( 'gulp-wp-pot' );
var sort            = require( 'gulp-sort' );
var checktextdomain = require( 'gulp-checktextdomain' );

var paths = {
	scripts: ['assets/js/*.js' ],
	adminScripts: ['assets/js/admin/*.js'],
	css: ['assets/css/*.scss'],
    frontedCss: ['assets/css/frontend/*.scss']
};

gulp.task( 'clean', function( cb ) {
	return del( ['assets/js/*.min.js','assets/js/admin/*.min.js', 'assets/css/*.min.css'], cb );
});

gulp.task( 'default', [ 'CSS','FrontendCSS','JS','adminJS' ] );

gulp.task( 'CSS', ['clean'], function() {
	return gulp.src( paths.css )
        .pipe( sass().on('error', sass.logError))
	.pipe( minifyCSS({ keepBreaks: false }) )
		.pipe( gulp.dest( 'assets/css' ) );
});

gulp.task( 'FrontendCSS', function() {
    return gulp.src( paths.frontedCss )
        .pipe( sass().on('error', sass.logError))
        .pipe( gulp.dest( './assets/css/frontend' ) );
});

gulp.task( 'JS', ['clean'], function() {
	return gulp.src( paths.scripts )
		// This will minify and rename to *.min.js
		.pipe( uglify() )
		.pipe( rename({ extname: '.min.js' }) )
		.pipe( chmod( 644 ) )
		.pipe( gulp.dest( 'assets/js' ));
});

gulp.task( 'adminJS', ['clean'], function() {
	return gulp.src( paths.adminScripts )
		// This will minify and rename to *.min.js
		.pipe( uglify() )
		.pipe( rename({ extname: '.min.js' }) )
		.pipe( chmod( 644 ) )
		.pipe( gulp.dest( 'assets/js/admin' ) );
});

gulp.task( 'pot', function() {
	return gulp.src( [ '**/**.php', '!node_modules/**'] )
		.pipe( sort() )
		.pipe( wpPot({
			domain: 'woothemes-sensei',
			bugReport: 'https://www.transifex.com/woothemes/sensei-by-woothemes/'
		}) )
		.pipe( gulp.dest( 'lang' ) );
});

gulp.task ( 'textdomain' , function() {
	return gulp.src( [ '**/*.php', '!node_modules/**'] )
		.pipe( checktextdomain({
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
		}));
});

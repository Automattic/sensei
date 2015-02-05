var gulp = require('gulp');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify');
var minifyCSS = require('gulp-minify-css');
del = require('del');

var paths = {
    scripts: ['assets/js/*.js'],
    css: ['assets/css/*.css']
};

gulp.task('clean', function(cb) {
    del( ['assets/js/*.min.js', 'assets/css/*.min.css'], cb );

});

gulp.task('default', [ 'clean' ] , function() {
    gulp.run('css') ;
    gulp.run('javascript');
});

gulp.task('css', function(){
    return gulp.src( paths.css )
        .pipe(minifyCSS({keepBreaks:false}))
        .pipe(rename({ extname: '.min.css' }))
        .pipe( gulp.dest('assets/css') );
});

gulp.task('javascript', function(){
     return gulp.src( paths.scripts )
        // This will minify and rename to *.min.js
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe( gulp.dest( 'assets/js' ));
});

gulp.task('watch', function() {
    // NOTE: this watch recusrively loops when .min changes, find a way to avoid this then
    // activate the watch again.
    // Watch .js files
    //gulp.watch( ['assets/js/*.js' , '!assets/js/*.js' ], ['javascript']);
   // gulp.watch('assets/css/*.css', ['css']);

});
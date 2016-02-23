'use strict';

var gulp = require('gulp');

// load plugins
var $ = require('gulp-load-plugins')();

gulp.task('styles', function () {
  return gulp.src('public/styles/sass/*.scss')
    .pipe($.sourcemaps.init())
    .pipe($.sass({
      outputStyle: 'nested', // libsass doesn't support expanded yet
      precision: 10,
      // includePaths: ['lib'],
      onError: console.error.bind(console, 'Sass error:')
    }))
    .pipe($.postcss([
      require('autoprefixer')({browsers: ['last 2 version']})
    ]))
    .pipe($.sourcemaps.write())
    .pipe(gulp.dest('public/styles/css/'))
    .pipe($.livereload())
    .pipe($.size());
});


gulp.task('build', ['styles']);

gulp.task('default', function () {
  gulp.start('build');
});

gulp.task('watch', function () {
  $.livereload.listen();

  // watch for changes
  gulp.watch([
    'public/styles/css/*.css',
    'public/js/**/*.js',
    '**/*.php'
  ]).on('change', $.livereload.changed);

  gulp.watch('public/styles/sass/**/*.scss', ['styles']);

});

gulp.task('serve', ['styles', 'watch'], function () {});

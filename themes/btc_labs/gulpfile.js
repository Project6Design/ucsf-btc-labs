// Initialize modules
var gulp = require('gulp');
var cssnano = require('gulp-cssnano');
var sass = require('gulp-sass')(require('sass'));
var uglify = require('gulp-uglify');
// var svgstore = require('gulp-svgstore');
// var svgmin = require('gulp-svgmin');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('svgmin', function () {
  return gulp.src('images/source/*.svg')
    .pipe(svgmin())
    .pipe(gulp.dest('images/optimized/')); // put final CSS in dist folder
});

gulp.task('svgstore', function () {
  return gulp
    .src(['images/optimized/*.svg', '!images/optimized/store.svg'])
    .pipe(svgmin((file) => {
      return {
        plugins: [{
          cleanupIDs: {
            prefix: '',
            minify: true
          }
        }]
      }
    }))
    .pipe(svgstore())
    .pipe(gulp.dest('images/optimized/store.svg'));
});

// Sass task: compiles the style.scss file into style.css
gulp.task('sass', function () {
    return gulp.src('sass/**/*.sass')
        .pipe(sass({
            loadPaths: ['node_modules/bourbon/app/assets/stylesheets']
        }).on('error', sass.logError))
        .pipe(cssnano())
        .pipe(gulp.dest('./css'));
});

// JS task: sourcemap, uglify
gulp.task('js', function () {
  return gulp.src('js/src/scripts.js')
    .pipe(sourcemaps.init())
    .pipe(uglify())
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('js'));
});

// Watch task: watch SCSS and JS files for changes
gulp.task('watch', function () {
  gulp.watch('sass/**/*.sass', gulp.series('sass'));
  gulp.watch('js/**/*.js', gulp.series('js'));
});

// Default task
// gulp.task('default', gulp.series('sass', 'js', 'svgmin', 'svgstore', 'watch'));
gulp.task('default', gulp.series('sass', 'js'));

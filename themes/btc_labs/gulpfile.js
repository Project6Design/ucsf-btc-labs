// Initialize modules
var gulp = require('gulp');
var cssnano = require('gulp-cssnano');
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
// var imagemin = require('gulp-imagemin');
var svgstore = require('gulp-svgstore');
var svgmin = require('gulp-svgmin');

// Sass task: compiles the style.scss file into style.css
// gulp.task('imagemin', function(){
//   return gulp.src('images/source/*.+(png,jpg,gif)')
//     .pipe(imagemin())
//     .pipe(gulp.dest('images/optimized/')); // put final CSS in dist folder
// });
gulp.task('svgmin', function(){
  return gulp.src('images/source/*.svg')
    .pipe(svgmin())
    .pipe(gulp.dest('images/optimized/')); // put final CSS in dist folder
});

gulp.task('svgstore', function(){
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

gulp.task('sass', function(){
  return gulp.src('sass/**/*.sass')
    .pipe(sass().on('error', sass.logError)) // compile SCSS to CSS
    .pipe(cssnano()) // minify CSS
    .pipe(gulp.dest('./css')); // put final CSS in dist folder
});

// JS task: concatenates and uglifies JS files to script.js
gulp.task('js', function(){
  return gulp.src(['js/plugins/*.js', 'js/*.js'])
    .pipe(concat('all.js'))
    .pipe(uglify())
    .pipe(gulp.dest('js'));
});

// Watch task: watch SCSS and JS files for changes
gulp.task('watch', function(){
  gulp.watch('sass/**/*.sass', gulp.series('sass'));
  gulp.watch('js/**/*.js', gulp.series('js'));
});

// Default task
gulp.task('default', gulp.series('sass', 'js', 'svgmin', 'svgstore', 'watch'));

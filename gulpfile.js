const template = 'website/navigator/template/';
let gulp = require('gulp');
let jsMinify = require('gulp-minify');
let concat = require('gulp-concat');
let cssNano = require('gulp-cssnano');
let removeFiles = require('gulp-remove-files');
//let imagemin = require('gulp-imagemin');

function js() {
    return gulp.src([
        template+'js/**/*.js',
    ])
    .pipe(jsMinify({
        ext:{
            src:'-debug.js',
            min:'.js'
        },
    }))
    .pipe(gulp.dest(template+'dist/js/', { sourcemaps: true }));
}

function cssLibs() {
    return gulp.src([
        template+'css/libs/*.css',
    ])
    .pipe(concat('libs.css'))
    .pipe(cssNano({
        zindex: false,
        autoprefixer: false,
    }))
    .pipe(gulp.dest(template+'dist/css/'))
}

function css(){
    return gulp.src([
        template+'css/source/*.css',
    ])
    .pipe(cssNano({
        zindex: false,
        autoprefixer: false,
    }))
    .pipe(gulp.dest(template+'dist/css/'))
}

function removeDebug(){
    return gulp.src(template+'dist/**/*-debug.*')
        .pipe(removeFiles());
}

function clean(){
    return gulp.src(template+'dist/**/*.*')
        .pipe(removeFiles());
}

exports.build = gulp.series(
    gulp.parallel(
        clean,
    ),
    gulp.parallel(
        js,
        css,
        cssLibs,
        /*images,*/
    ),
    gulp.parallel(
        removeDebug
    )
);

/*function jsLibs() {
    /!*return gulp.src([
        template+'js/libs/!**!/!*.js',
    ])
        .pipe(concat('libs.min.js'))
        .pipe(jsMinify({
            ext:{
                src:'-debug.js',
                min:'.js'
            },
        }))
        .pipe(gulp.dest(template+'dist/js/'));*!/

    return gulp.src([
        template+'js/bootstrap.min.js',
        template+'js/bootstrap-select.js',
        template+'js/jquery.countdown.min.js',
        template+'js/owl.carousel.min.js',
        template+'js/moment.js',
        template+'js/moment-timezone-with-data.js',
        template+'js/jquery.mask.js',
        template+'js/jquery.iframetracker.min.js',
        template+'js/linkify/linkify.min.js',
        template+'js/linkify/linkify-jquery.min.js'
    ])
        .pipe(concat('libs.min.js'))
        .pipe(gulp.dest(template+'dist/js/', { sourcemaps: true }));

    /!*return gulp.src([
        template+'js/libs/!*.js',
        template+'js/libs/!*!/!*.js',
    ])
    .pipe(concat('libs.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest(template+'dist/js/'));*!/
}*/

/*function images() {
    return gulp.src([
        template+'img/!**!/!*',
    ])
    .pipe(imagemin())
    .pipe(gulp.dest(template+'dist/img/', { sourcemaps: true }))
}*/
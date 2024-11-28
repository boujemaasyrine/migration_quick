var gulp = require('gulp'),
    less = require('gulp-less'),
    clean = require('gulp-clean'),
    concatJs = require('gulp-concat'),
    concatCss = require('gulp-concat-css'),
    cleanCSS = require('gulp-clean-css'),
    minifyJs = require('gulp-uglify');

var dev = false;

// CSS
gulp.task('lib-css', function () {
    var pages = gulp.src(['web/plugins/simple-line-icons/css/simple-line-icons.css',
            'web/plugins/font-awesome/css/font-awesome.css',
            'web/plugins/jquery-ui/themes/base/jquery-ui.min.css',
            'web/bundles/app/lib/quick-theme/jquery-ui-1.9.2.custom.min.css',
            'web/plugins/bootstrap/dist/css/bootstrap.min.css',
            'web/plugins/Materialize/dist/css/materialize.min.css',
            'web/bundles/app/lib/flags/flags.css',
            'web/plugins/superfish/dist/css/superfish.css',
            'web/plugins/superfish/dist/css/superfish-vertical.css',
            'web/plugins/datatables/media/css/dataTables.bootstrap.min.css',
            'web/bundles/app/lib/rowReorder.dataTables.min.css',
            'web/bundles/app/lib/progress-wizard.min.css',
            'web/bundles/app/css/multi_select_modified.css',
            'web/plugins/selectize/dist/css/selectize.css'])
        .pipe(concatCss("libs.css"));
    if (!dev) {
        pages = pages.pipe(cleanCSS({compatibility: 'ie8'}));
    }
    pages = pages.pipe(gulp.dest('web/src/css/'));
    return pages;
});

// LESS
gulp.task('less', function () {
    return gulp.src(['web/bundles/app/less/*.less'])
        .pipe(less({compress: true}))
        .pipe(gulp.dest('web/src/css/'));
});

gulp.task('less-supervision', function () {
    return gulp.src(['web/bundles/app/less/Supervision/*.less'])
        .pipe(less({compress: true}))
        .pipe(gulp.dest('web/src/css/'));
});

// IMAGES
gulp.task('images', function () {
    return gulp.src([
            'web/bundles/app/images/**/*',
            'web/bundles/app/lib/flags/flags.png'
        ])
        .pipe(gulp.dest('web/src/images/'))
});

// FONTS
gulp.task('fonts', function () {
    return gulp.src(['web/plugins/bootstrap/fonts/*',
            'web/bundles/app/fonts/*',
            'web/plugins/simple-line-icons/fonts/*'])
        .pipe(gulp.dest('web/src/fonts/'))
});
gulp.task('font-awesome', function () {
    return gulp.src(['web/plugins/font-awesome/fonts/*'])
        .pipe(gulp.dest('web/font-awesome/fonts/'))
});
gulp.task('font-glyphicon', function () {
    return gulp.src(['web/plugins/bootstrap/dist/fonts/*'])
        .pipe(gulp.dest('web/bootstrap/dist/fonts/'))
});

// JS
gulp.task('lib-js', function () {
    return gulp.src([
            'web/plugins/jquery/dist/jquery.min.js',
            'web/plugins/jquery-ui/jquery-ui.js',
            'web/bundles/app/js/slider.js',
            'web/plugins/bootstrap/dist/js/bootstrap.min.js',
            'web/bundles/app/js/tooltipBootstrap.js',
            'web/plugins/moment/min/moment.min.js',
            'web/plugins/moment/locale/fr.js',
            'web/plugins/Materialize/dist/js/materialize.min.js',
            'web/plugins/superfish/dist/js/superfish.js',
            'web/plugins/datatables/media/js/jquery.dataTables.min.js',
            'web/bundles/app/lib/dataTables.rowReorder.min.js',
            'web/plugins/datatables/media/js/dataTables.bootstrap.min.js',
            'web/bundles/app/lib/jquery.uix.multiselect-master/js/jquery.uix.multiselect.js',
            'web/bundles/app/lib/jquery.uix.multiselect-master/js/locales/jquery.uix.multiselect_fr.js',
            'web/bundles/app/lib/jquery.uix.multiselect-master/js/locales/jquery.uix.multiselect_nl.js',
            'web/plugins/blockUI/jquery.blockUI.js',
            'web/plugins/noty/js/noty/packaged/jquery.noty.packaged.min.js',
            'web/plugins/noty/js/noty/themes/bootstrap.js',
            'web/bundles/bazingajstranslation/js/translator.min.js',
            'web/plugins/bootbox.js/bootbox.js',
            'web/plugins/selectize/dist/js/standalone/selectize.js',
            'web/plugins/jquery-validation/dist/jquery.validate.min.js'
        ])
        .pipe(concatJs('app.js'))
        .pipe(minifyJs())
        .pipe(gulp.dest('web/src/js/'));
});
    
gulp.task('common-js', function () {
    var pages = gulp.src([
        'web/bundles/app/js/Module/*.js'
    ]).pipe(concatJs('common.js'));
    if (!dev) {
        pages = pages.pipe(minifyJs());
    }
    return pages.pipe(gulp.dest('web/src/js/'));
});


gulp.task('common-supervision-js', function () {
    var pages = gulp.src([
        'web/bundles/app/js/Supervision/Module/*.js'
    ]).pipe(concatJs('common.js'));
    if (!dev) {
        pages = pages.pipe(minifyJs());
    }
    return pages.pipe(gulp.dest('web/src/js/Supervision'));
});


gulp.task('pages-js', function () {
    var pages = gulp.src([
        'web/bundles/app/js/**/*.js'
    ]);
    if (!dev) {
        pages = pages.pipe(minifyJs());
    }
    return pages.pipe(gulp.dest('web/src/js/'));
});

gulp.task('clean', function () {
    return gulp.src(['web/src/css/*', 'web/src/js/*', 'web/src/images/*', 'web/src/fonts/*'])
        .pipe(clean());
});

gulp.task('default', ['clean'], function () {
    var tasks = ['images','font-awesome', 'font-glyphicon', 'fonts', 'lib-css', 'less', 'less-supervision', 'lib-js', 'common-js', 'common-supervision-js', 'pages-js'];
    tasks.forEach(function (val) {
        gulp.start(val);
    });
});

gulp.task('watch', function () {
    var less = gulp.watch('web/bundles/app/less/**/*.less', ['less']),
        js = gulp.watch('web/bundles/app/js/**/*.js', ['pages-js']);
});
//
// Modules
// ======================================================
var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var $ = require('gulp-load-plugins')();
var path = require('path');
var gutil = require('gulp-util');

var chalk = require('chalk');
var log = console.log;



//
// Variables
// ======================================================
var HOST_URL = 'hackathon2017.dev';   


//
// Serve
// ======================================================
gulp.task('serve', function (done) {
    browserSync.init({
        notify: true,
        proxy: HOST_URL
    });

    // add browserSync.reload to the tasks array to make
    // all browsers reload after tasks are complete.
    done();
});

// Initialization

gulp.task('default');

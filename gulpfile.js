var pkg = require('./package.json')
var gulp = require('gulp')
var replace = require('gulp-replace')
var sort = require('gulp-sort')
var zip = require('gulp-zip')
var wpPot = require('gulp-wp-pot')

var pluginSlug = 'password-policy-manager'
var pluginDomain = 'ppm-wp'
var pluginName = 'WPassword'
var teamEmail = 'info@wpwhitesecurity.com'
var teamName = 'WP White Security'
var teamWebsite = 'https://www.wpwhitesecurity.com'

/**
 * Generate translations.
 */
gulp.task('translate', function () {
	return gulp.src([
		'admin/**/*.php',
		'app/**/*.php',
		'sdk/**/*.php',
		'password-policy-manager.php'
	])
		.pipe(sort())
		.pipe(wpPot({
			domain: pluginDomain,
			destFile: pluginSlug + '.pot',
			package: pluginSlug,
			bugReport: teamWebsite,
			lastTranslator: teamName + ' <' + teamEmail + '>',
			team: teamName + ' <' + teamEmail + '>'
		}))
		.pipe(gulp.dest('./languages/' + pluginDomain + '.pot'))
})

/**
 * Build Plugin Zip
 */
gulp.task( 'zip', function() {
	return gulp.src([

		// Include
		'./**/*',

		// Exclude
		'!./composer.*',
		'!./gulpfile.js',
		'!./node_modules',
		'!./node_modules/**/*',
		'!./package*.json',
		'!./tests',
		'!./tests/**/*',
		'!./phpcs.xml',
		'!./README.md'
	])
		.pipe(zip(pkg.name + '.zip'))
		.pipe(gulp.dest('../'))
});

/**
 * Removes comment annotations
 */
gulp.task('substitute-year', function () {
	return gulp.src('./**/*.php')
		.pipe(replace('%%YEAR%%', new Date().getFullYear() ))
		.pipe(gulp.dest('.'))
})

/**
 * Replaces version number placeholder in case with the actual version number. The version number is read from package.json.
 */
gulp.task('replace-latest-version-numbers', function () {
	return gulp.src('./**/*.php')
		.pipe(replace(/@since\s+latest/g, '@since ' + pkg.version))
		.pipe(gulp.dest('.'))
})
